<?php
/**
 * Copyright © Dotcom Weavers. All rights reserved.
 * Plugin to override guest order lookup - Order ID + Email only.
 */

declare(strict_types=1);

namespace Dcw\TrackOrder\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Sales\Helper\Guest as GuestHelper;
use Magento\Sales\Model\Order;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class GuestPlugin
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->customerSession = $customerSession;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Around plugin for loadValidOrder - Order ID + Email only
     *
     * @param GuestHelper $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return \Magento\Framework\Controller\Result\Redirect|bool
     * @throws InputException
     */
    public function aroundLoadValidOrder(
        GuestHelper $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        if ($this->customerSession->isLoggedIn()) {
            return $this->resultRedirectFactory->create()->setPath('sales/order/history');
        }
        $post = $request->getPostValue();
        $post = is_array($post) ? array_map(function ($v) {
            return is_string($v) ? trim($v) : $v;
        }, $post) : [];
        $fromCookie = $this->cookieManager->getCookie(GuestHelper::COOKIE_NAME);
        if (empty($post) && !$fromCookie) {
            return $this->resultRedirectFactory->create()->setPath('sales/guest/form');
        }
        try {
            $order = (!empty($post)
                && isset($post['oar_order_id'], $post['oar_email'])
                && !$this->hasPostDataEmptyFields($post))
                ? $this->loadFromPost($post) : $this->loadFromCookie($fromCookie);
            $this->coreRegistry->register('current_order', $order);
            return true;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('sales/guest/form');
        }
    }

    /**
     * @param string|null $fromCookie
     * @return Order
     * @throws InputException
     */
    private function loadFromCookie($fromCookie)
    {
        $inputExceptionMessage = 'You entered incorrect data. Please try again.';
        if (!is_string($fromCookie) || $fromCookie === '') {
            throw new InputException(__($inputExceptionMessage));
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $cookieData = explode(':', base64_decode($fromCookie));
        $protectCode = $cookieData[0] ?? null;
        $incrementId = $cookieData[1] ?? null;
        if ($protectCode && $incrementId) {
            $order = $this->getOrderRecord($incrementId);
            if (hash_equals((string)$order->getProtectCode(), $protectCode)) {
                $this->setGuestViewCookie($fromCookie);
                return $order;
            }
        }
        throw new InputException(__($inputExceptionMessage));
    }

    /**
     * @param array $postData
     * @return Order
     * @throws InputException
     */
    private function loadFromPost(array $postData)
    {
        $order = $this->getOrderRecord($postData['oar_order_id']);
        if (!$this->compareStoredBillingDataWithInput($order, $postData)) {
            throw new InputException(__('Order ID and Email do not match. Please try again.'));
        }
        $toCookie = base64_encode($order->getProtectCode() . ':' . $postData['oar_order_id']);
        $this->setGuestViewCookie($toCookie);
        return $order;
    }

    /**
     * @param Order $order
     * @param array $postData
     * @return bool
     */
    private function compareStoredBillingDataWithInput(Order $order, array $postData): bool
    {
        $email = $postData['oar_email'] ?? '';
        $billingAddress = $order->getBillingAddress();
        if (!$billingAddress) {
            return false;
        }
        return $this->normalizeStr((string)$email) === $this->normalizeStr((string)$billingAddress->getEmail());
    }

    /**
     * @param string $str
     * @return string
     */
    private function normalizeStr(string $str): string
    {
        return trim(strtolower($str));
    }

    /**
     * @param array $postData
     * @return bool
     */
    private function hasPostDataEmptyFields(array $postData): bool
    {
        return empty($postData['oar_order_id'])
            || empty($postData['oar_email'])
            || empty($this->storeManager->getStore()->getId());
    }

    /**
     * @param string $cookieValue
     * @return void
     */
    private function setGuestViewCookie(string $cookieValue): void
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath(GuestHelper::COOKIE_PATH)
            ->setHttpOnly(true)
            ->setSameSite('Lax');
        $this->cookieManager->setPublicCookie(GuestHelper::COOKIE_NAME, $cookieValue, $metadata);
    }

    /**
     * @param string $incrementId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws InputException
     */
    private function getOrderRecord($incrementId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $incrementId)
            ->addFilter('store_id', $this->storeManager->getStore()->getId())
            ->create();
        $records = $this->orderRepository->getList($searchCriteria);
        $items = $records->getItems();
        if (empty($items)) {
            throw new InputException(__('You entered incorrect data. Please try again.'));
        }
        return array_shift($items);
    }
}
