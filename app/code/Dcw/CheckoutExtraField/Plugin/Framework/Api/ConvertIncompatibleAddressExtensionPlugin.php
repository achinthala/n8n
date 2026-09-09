<?php
declare(strict_types=1);

namespace Dcw\CheckoutExtraField\Plugin\Framework\Api;

use Magento\Customer\Api\Data\AddressExtensionInterface as CustomerAddressExtensionInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Magento copies quote address getData() onto a customer address, including extension_attributes.
 * Quote AddressExtension is not a Customer AddressExtension, so setExtensionAttributes() TypeErrors.
 */
class ConvertIncompatibleAddressExtensionPlugin
{
    /**
     * @param mixed $dataObject
     * @param string $interfaceName
     * @return array{0: mixed, 1: array, 2: string}
     */
    public function beforePopulateWithArray(
        DataObjectHelper $subject,
        $dataObject,
        array $data,
        $interfaceName
    ): array {
        if (ltrim((string) $interfaceName, '\\') !== CustomerAddressInterface::class) {
            return [$dataObject, $data, $interfaceName];
        }

        $extension = $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY] ?? null;
        if (!is_object($extension) || $extension instanceof CustomerAddressExtensionInterface) {
            return [$dataObject, $data, $interfaceName];
        }

        $asArray = method_exists($extension, '__toArray') ? $extension->__toArray() : [];
        // Quote AddressExtension also holds Magento gift-registry / pickup attributes that
        // Customer AddressExtensionInterface does not declare. Copy only phone_ext.
        $data[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY] = array_intersect_key(
            is_array($asArray) ? $asArray : [],
            ['phone_ext' => true]
        );

        return [$dataObject, $data, $interfaceName];
    }
}
