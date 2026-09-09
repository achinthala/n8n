<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\MagentoDataServices\Plugin;

use Magento\DataServices\Block\Context;

class AddStorefrontTemplate
{
    /**
     * Adding in the optional property storefrontTemplate to the getStorefrontInstanceContext() Method.
     *
     * @param Context $subject
     * @param $result
     * @return string
     */
    public function afterGetStorefrontInstanceContext(Context $subject, $result): string
    {
        // Convert the JSON result back to an array
        $contextData = json_decode($result, true);

        // Adding in storefrontTemplate data to the array
        $contextData['storefrontTemplate'] = 'Hyva';

        // Convert the modified array back to JSON and return it
        return json_encode($contextData);
    }
}
