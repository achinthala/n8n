<?php
declare(strict_types=1);

namespace Dcw\PurchaseOrderReview\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Custom PO gateway: no capture; order pending review (no core offline methods required).
 */
class PaymentMethod extends AbstractMethod
{
    public const METHOD_CODE = 'dcw_po_gateway';

    public const INFO_CONTACT_NAME = 'po_contact_name';

    public const INFO_CONTACT_PHONE = 'po_contact_phone';

    public const INFO_CONTACT_EMAIL = 'po_contact_email';

    protected $_code = self::METHOD_CODE;

    protected $_isOffline = true;

    protected $_canAuthorize = false;

    protected $_canCapture = false;

    protected $_canRefund = false;

    protected $_canVoid = false;

    protected $_canUseInternal = true;

    protected $_canUseCheckout = true;

    protected $_infoBlockType = \Dcw\PurchaseOrderReview\Block\Info\PoGateway::class;

    /**
     * Admin order create ({@see \Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method\Form}) loads this block
     * so custom fields render under the payment radio.
     */
    protected $_formBlockType = \Dcw\PurchaseOrderReview\Block\Form\PoGateway::class;

    public function assignData(DataObject $data)
    {
        parent::assignData($data);
        $additionalData = $data->getAdditionalData();
        if (!is_array($additionalData)) {
            $additionalData = [];
        }

        $info = $this->getInfoInstance();
        foreach ([
            self::INFO_CONTACT_NAME,
            self::INFO_CONTACT_PHONE,
            self::INFO_CONTACT_EMAIL,
        ] as $key) {
            if (array_key_exists($key, $additionalData)) {
                $info->setAdditionalInformation($key, (string) $additionalData[$key]);
            }
        }

        return $this;
    }

    /**
     * Called from {@see \Magento\Quote\Model\Quote\Payment::importData} on every payment save (incl. REST
     * set-payment-information with method only). Allow empty contact fields until order submit; if any field
     * is sent, require a complete valid set so we never persist a half-filled state.
     *
     * @throws LocalizedException
     */
    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();
        $name = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_NAME));
        $phone = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_PHONE));
        $email = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_EMAIL));

        if ($name === '' && $phone === '' && $email === '') {
            return $this;
        }

        $this->assertContactDetailsComplete($name, $phone, $email);

        return $this;
    }

    /**
     * Always require full contact details before the quote is converted to an order.
     *
     * @throws LocalizedException
     */
    public function validateForOrderSubmit(): void
    {
        $info = $this->getInfoInstance();
        $name = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_NAME));
        $phone = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_PHONE));
        $email = trim((string) $info->getAdditionalInformation(self::INFO_CONTACT_EMAIL));
        $this->assertContactDetailsComplete($name, $phone, $email);
    }

    /**
     * @throws LocalizedException
     */
    private function assertContactDetailsComplete(string $name, string $phone, string $email): void
    {
        if ($name === '') {
            throw new LocalizedException(__('Please enter your name.'));
        }
        if ($phone === '' || !preg_match('/^\d{10}$/', $phone)) {
            throw new LocalizedException(__('Please enter a valid 10-digit phone number.'));
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }
    }
}
