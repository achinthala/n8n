<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Block\Account;

use Dcw\ShareCart\Helper\ContactFields;
use Dcw\ShareCart\Model\Config\Source\LinkStatus;
use Dcw\ShareCart\Model\ResourceModel\ShareCart\Collection;
use Dcw\ShareCart\Model\ResourceModel\ShareCart\CollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

class ShareHistory extends Template
{
    private ?Collection $collection = null;

    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CustomerSession $customerSession,
        private readonly LinkStatus $linkStatusSource,
        private readonly ContactFields $contactFields,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getShareCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->collectionFactory->create();
            $this->collection->addFieldToFilter(
                'shared_by_customer_id',
                (int) $this->customerSession->getCustomerId()
            );
            $this->collection->setOrder('share_id', 'DESC');
        }

        return $this->collection;
    }

    public function getYourName(DataObject $share): string
    {
        return $this->contactFields->getYourName($share);
    }

    public function getRecipientName(DataObject $share): string
    {
        return $this->contactFields->getRecipientName($share);
    }

    public function getRecipientEmail(DataObject $share): string
    {
        return $this->contactFields->getRecipientEmail($share);
    }

    public function getRecipientPhone(DataObject $share): string
    {
        return $this->contactFields->getRecipientPhone($share);
    }

    public function getLinkStatusLabel(string $status): string
    {
        foreach ($this->linkStatusSource->toOptionArray() as $option) {
            if ($option['value'] === $status) {
                return (string) $option['label'];
            }
        }

        return $status;
    }

    public function getSmsConsentLabel(mixed $value): string
    {
        return (int) $value === 1
            ? (string) __('Yes')
            : (string) __('No');
    }
}
