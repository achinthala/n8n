<?php declare(strict_types=1);

namespace Dotcomweavers\OrderRestrictions\Model;

use Dotcomweavers\OrderRestrictions\Api\ShippingAddressManagementInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\LocalizedException;

class ShippingAddressManagement implements ShippingAddressManagementInterface
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;

    /**
     * @var DataObjectProcessor
     */
    private $dataProcessor;

    /**
     * @var RegionInterfaceFactory
     */
    private $regionDataFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param DataObjectProcessor $dataProcessor
     * @param RegionInterfaceFactory $regionDataFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        DataObjectProcessor $dataProcessor,
        RegionInterfaceFactory $regionDataFactory,
        DataObjectHelper $dataObjectHelper,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->dataProcessor = $dataProcessor;
        $this->regionDataFactory = $regionDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->customerRepository = $customerRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function saveAddress(
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $address = $addressInformation->getShippingAddress();
        if ($address->getCustomerAddressId() && $address->getCustomerId()) {
            $this->updateCustomerAddress($address);
        }

        return $address->getCustomerId();
    }

    public function deleteAddress(
        $cartId,
        ShippingInformationInterface $addressInformation)
    {
        $address = $addressInformation->getShippingAddress();
        $customerId = $address->getCustomerId();

        if ($address->getCustomerAddressId() && $address->getCustomerId()) {
            $addressId = $address->getCustomerAddressId();
            /** @var \Magento\Customer\Model\Data\Customer $customer */
            $customer = $this->customerRepository->getById($customerId);
            $defaultId = $customer->getDefaultShipping();
            if ($defaultId == $addressId) {
                throw new LocalizedException(__('You cannot delete default address.'));
            }
            $this->addressRepository->deleteById($address->getCustomerAddressId());
        }

        return $customerId;
    }

    /**
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return AddressInterface
     */
    private function updateCustomerAddress($address)
    {
        $addressData = $address->getData();
		unset($addressData['extension_attributes']);
        $street = explode('\n', $addressData['street']);
        $addressData['street'] = $street;
        $existingAddress = $this->addressRepository->getById($address->getCustomerAddressId());
        $existingAddressData = $this->dataProcessor->buildOutputDataArray(
            $existingAddress,
            AddressInterface::class
        );
        $existingAddressData['region_code'] = $existingAddress->getRegion()->getRegionCode();
        $existingAddressData['region'] = $existingAddress->getRegion()->getRegion();

        $this->updateRegionData($addressData);
        /** @var AddressInterface $addressDataObject */
        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($existingAddressData, $addressData),
            AddressInterface::class
        );

        if (isset($addressData['customer_id']) && $addressData['customer_id']) {
            $addressDataObject->setCustomerId($addressData['customer_id']);
        }

        $addressRepository =  $this->addressRepository->save($addressDataObject);
        return $addressRepository;
    }

    /**
     * Update region data.
     *
     * @param array $attributeValues
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function updateRegionData(&$attributeValues)
    {
        $regionData = [
            RegionInterface::REGION_ID => !empty($attributeValues['region_id']) ? $attributeValues['region_id'] : null,
            RegionInterface::REGION => !empty($attributeValues['region']) ? $attributeValues['region'] : null,
            RegionInterface::REGION_CODE => !empty($attributeValues['region_code']) ? $attributeValues['region_code'] : null,
        ];

        $region = $this->regionDataFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $region,
            $regionData,
            '\Magento\Customer\Api\Data\RegionInterface'
        );

        $attributeValues['region'] = $region;
    }
}
