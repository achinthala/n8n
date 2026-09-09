<?php
/**
 * Plugin to add CAPTCHA validation for guest FAQ rating requests
 *
 * @author DCW
 * @package Dcw_Faq
 */

declare(strict_types=1);

namespace Dcw\Faq\Plugin\GraphQl;

use Amasty\FaqGraphQl\Model\Resolver\Question\RateQuestion;
use Amasty\InvisibleCaptcha\Model\Captcha as CaptchaModel;
use Amasty\InvisibleCaptcha\Model\ConfigProvider as CaptchaConfigProvider;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Authorization\Model\UserContextInterface;
use Psr\Log\LoggerInterface;

class RateQuestionPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CaptchaModel
     */
    private $captchaModel;

    /**
     * @var CaptchaConfigProvider
     */
    private $captchaConfigProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param CustomerSession $customerSession
     * @param CaptchaModel $captchaModel
     * @param CaptchaConfigProvider $captchaConfigProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        CustomerSession $customerSession,
        CaptchaModel $captchaModel,
        CaptchaConfigProvider $captchaConfigProvider,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->captchaModel = $captchaModel;
        $this->captchaConfigProvider = $captchaConfigProvider;
        $this->logger = $logger;
    }

    /**
     * Validate CAPTCHA token for guest users before processing rating
     *
     * @param RateQuestion $subject
     * @param mixed $field
     * @param mixed $context
     * @param mixed $info
     * @param array|null $value
     * @param array|null $args
     * @return void
     * @throws GraphQlAuthorizationException
     */
    public function beforeResolve(
        ResolverInterface $subject,
        $field,
        $context,
        $info,
        array $value = null,
        array $args = null
    ) {
        // Only process if this is the RateQuestion resolver
        if (!($subject instanceof RateQuestion)) {
            return;
        }
        
        // Log plugin execution for debugging
        $this->logger->info('FAQ Rating Plugin: Plugin executed', [
            'context_user_id' => $context->getUserId(),
            'context_user_type' => $context->getUserType(),
            'session_logged_in' => $this->customerSession->isLoggedIn()
        ]);

        // Check if user is guest using GraphQL context (more reliable than session)
        $isGuest = $context->getUserType() === UserContextInterface::USER_TYPE_GUEST 
                   || ($context->getUserId() === null || $context->getUserId() == 0);

        $this->logger->info('FAQ Rating Plugin: Guest check', [
            'isGuest' => $isGuest,
            'userType' => $context->getUserType(),
            'userId' => $context->getUserId()
        ]);

        // Only validate CAPTCHA for guest users
        if ($isGuest) {
            // Check if CAPTCHA is enabled
            $captchaEnabled = $this->captchaConfigProvider->isEnabled();
            $captchaConfigured = $this->captchaConfigProvider->isConfigured();
            
            $this->logger->info('FAQ Rating Plugin: CAPTCHA config check', [
                'enabled' => $captchaEnabled,
                'configured' => $captchaConfigured
            ]);

            if ($captchaEnabled && $captchaConfigured) {
                // Get CAPTCHA token from request header
                // GraphQL requests send headers in HTTP request
                $captchaToken = $this->request->getHeader('X-ReCaptcha');
                if (empty($captchaToken)) {
                    // Try alternative header name (some servers normalize headers)
                    $captchaToken = $this->request->getHeader('x-recaptcha');
                }

                $this->logger->info('FAQ Rating Plugin: Token check', [
                    'token_present' => !empty($captchaToken),
                    'token_length' => $captchaToken ? strlen($captchaToken) : 0
                ]);

                if (empty($captchaToken)) {
                    $this->logger->error('FAQ Rating: Missing CAPTCHA token - BLOCKING REQUEST');
                    $this->logger->error('FAQ Rating: Request headers', [
                        'all_headers' => $this->request->getHeaders()->toArray(),
                        'x-recaptcha_header' => $this->request->getHeader('X-ReCaptcha'),
                        'x-recaptcha_lower' => $this->request->getHeader('x-recaptcha')
                    ]);
                    throw new GraphQlAuthorizationException(
                        __('CAPTCHA verification is required. Please refresh the page and try again.')
                    );
                }

                // Validate token with Google
                $verification = $this->captchaModel->verify($captchaToken);

                $this->logger->info('FAQ Rating Plugin: Verification result', [
                    'success' => $verification['success'] ?? false,
                    'error' => $verification['error'] ?? 'none'
                ]);

                if (!$verification['success']) {
                    $errorMessage = $verification['error'] ?? __('CAPTCHA verification failed.');
                    $this->logger->warning(
                        'FAQ Rating: CAPTCHA validation failed for guest user',
                        ['error' => $errorMessage, 'verification' => $verification]
                    );
                    throw new GraphQlAuthorizationException($errorMessage);
                }

                // CAPTCHA validated successfully - log for debugging (optional)
                $this->logger->debug('FAQ Rating: CAPTCHA validated successfully for guest user');
            } else {
                $this->logger->info('FAQ Rating Plugin: CAPTCHA validation skipped - not enabled or configured');
            }
        } else {
            $this->logger->info('FAQ Rating Plugin: CAPTCHA validation skipped - user is logged in');
        }
        
        // beforeResolve doesn't return anything - if we reach here, validation passed
    }
}
