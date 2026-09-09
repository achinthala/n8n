<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Block\Info;

use Dcw\PurchaseOrderReview\Model\PaymentMethod;
use Magento\Payment\Block\Info;

/**
 * Renders method title + confirmed contact details using core payment info layout (admin, emails, PDFs).
 */
class PoGateway extends Info
{
    protected $_template = 'Magento_Payment::info/default.phtml';

    /**
     * @inheritdoc
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $info = $this->getInfo();

        $name = $info->getAdditionalInformation(PaymentMethod::INFO_CONTACT_NAME);
        if ($name !== null && trim((string) $name) !== '') {
            $transport->setData(
                (string) __('Name'),
                trim((string) $name)
            );
        }

        $phone = $info->getAdditionalInformation(PaymentMethod::INFO_CONTACT_PHONE);
        if ($phone !== null && trim((string) $phone) !== '') {
            $transport->setData(
                (string) __('Phone number'),
                trim((string) $phone)
            );
        }

        $email = $info->getAdditionalInformation(PaymentMethod::INFO_CONTACT_EMAIL);
        if ($email !== null && trim((string) $email) !== '') {
            $transport->setData(
                (string) __('Email'),
                trim((string) $email)
            );
        }

        return $transport;
    }
}
