<?php
declare(strict_types=1);

namespace Dcw\OrderPendingReview\Observer\Adminhtml;

use Magento\Framework\Data\Form;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

/**
 * Adds "Rule outcome" dropdown to Dotcomweavers Order Restrictions rule edit (General tab).
 */
class AddOrderRestrictionApplyTypeField implements ObserverInterface
{
    public function __construct(
        private readonly Registry $registry
    ) {
    }

    public function execute(Observer $observer): void
    {
        $form = $observer->getEvent()->getForm();
        if (!$form instanceof Form) {
            return;
        }
        $fieldset = $form->getElement('base_fieldset');
        if (!$fieldset) {
            return;
        }
        $model = $this->registry->registry('current_rule');
        if ($model === null) {
            return;
        }

        $fieldset->addField(
            'apply_type',
            'select',
            [
                'name' => 'apply_type',
                'label' => __('Rule outcome'),
                'title' => __('Rule outcome'),
                'options' => [
                    'restriction' => __('Block checkout (order restriction)'),
                    'pending_review' => __('Pending review only (do not block checkout)'),
                ],
            ]
        );

        $value = $model->getData('apply_type');
        if ($value === null || $value === '') {
            $value = 'restriction';
        }
        $element = $form->getElement('apply_type');
        if ($element !== null) {
            $element->setValue((string) $value);
        }
    }
}
