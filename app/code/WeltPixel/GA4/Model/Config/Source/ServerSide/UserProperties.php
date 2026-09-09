<?php

namespace WeltPixel\GA4\Model\Config\Source\ServerSide;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class UserProperties
 * @package WeltPixel\GA4\Model\Config\Source\ServerSide
 */
class UserProperties implements ArrayInterface
{

    const USER_PROPERTY_BROWSER = 'browser';
    const USER_PROPERTY_BROWSER_VERSION = 'browser_version';
    const USER_PROPERTY_PLATFORM = 'platform';

    /**
     * Return list of Id Options
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::USER_PROPERTY_BROWSER,
                'label' => __('Browser')
            ),
            array(
                'value' => self::USER_PROPERTY_BROWSER_VERSION,
                'label' => __('Browser Version')
            ),
            array(
                'value' => self::USER_PROPERTY_PLATFORM,
                'label' => __('Platform (Operating System)')
            )
        );
    }
}
