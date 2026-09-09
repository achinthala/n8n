<?php
declare(strict_types=1);

namespace Dcw\AiContentCreator\Controller\Adminhtml\Generate;

use Dcw\AiContentCreator\Model\Config;
use Dcw\AiContentCreator\Model\Prompt\Builder as PromptBuilder;
use Dcw\AiContentCreator\Model\Service\ContentGenerator;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * AJAX endpoint: generate AI draft for an allow-listed product attribute.
 * Does not persist product data — Apply Draft + Product Save remain human-in-the-loop.
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_AiContentCreator::generate';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly ContentGenerator $contentGenerator
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $productId = (int) $this->getRequest()->getParam('product_id');
            $attributeCode = (string) $this->getRequest()->getParam('attribute_code');
            $mode = (string) $this->getRequest()->getParam('mode', PromptBuilder::MODE_GENERATE);
            $providerCode = (string) $this->getRequest()->getParam('provider', '');
            $extraInstructions = (string) $this->getRequest()->getParam('extra_instructions', '');
            $storeIdParam = $this->getRequest()->getParam('store_id');
            $storeId = $storeIdParam !== null && $storeIdParam !== '' ? (int) $storeIdParam : null;

            $currentValuesRaw = $this->getRequest()->getParam('current_values', []);
            $currentValues = [];
            if (is_string($currentValuesRaw) && $currentValuesRaw !== '') {
                $decoded = json_decode($currentValuesRaw, true);
                $currentValues = is_array($decoded) ? $decoded : [];
            } elseif (is_array($currentValuesRaw)) {
                $currentValues = $currentValuesRaw;
            }

            // Normalize current values to strings for allow-listed keys only.
            $normalized = [];
            foreach (Config::ALLOWED_ATTRIBUTES as $code) {
                if (isset($currentValues[$code])) {
                    $normalized[$code] = (string) $currentValues[$code];
                }
            }

            if ($productId <= 0) {
                throw new LocalizedException(__('Product ID is required.'));
            }
            if ($attributeCode === '') {
                throw new LocalizedException(__('Attribute code is required.'));
            }

            $draft = $this->contentGenerator->generateDraft(
                $productId,
                $attributeCode,
                $mode === PromptBuilder::MODE_REWRITE
                    ? PromptBuilder::MODE_REWRITE
                    : PromptBuilder::MODE_GENERATE,
                $providerCode !== '' ? $providerCode : null,
                $normalized,
                $extraInstructions,
                $storeId
            );

            return $result->setData([
                'success' => true,
                'content' => $draft['content'],
                'provider' => $draft['provider'],
                'attribute' => $draft['attribute'],
                'mode' => $draft['mode'],
                'message' => (string) __('Draft generated. Review and edit, then Apply Draft. Product Save is still required to persist.'),
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => (string) __('An unexpected error occurred while generating content.'),
            ]);
        }
    }
}
