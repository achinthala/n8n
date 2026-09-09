<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model;

use Dcw\ProductEnquiry\Model\Config\Source\Status;
use Magento\Framework\Model\AbstractModel;

class Enquiry extends AbstractModel
{
    public const STATUS_NEW = Status::STATUS_NEW;

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Enquiry::class);
    }

    public function getEnquiryId(): ?int
    {
        $id = $this->getData('enquiry_id');

        return $id !== null ? (int) $id : null;
    }

    public function getEmail(): string
    {
        return (string) $this->getData('email');
    }

    public function getName(): string
    {
        return (string) $this->getData('name');
    }
}
