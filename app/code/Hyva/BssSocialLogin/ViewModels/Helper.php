<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Hyva_BssSocialLogin
 * @author     Extension Team
 * @copyright  Copyright (c) 2022 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Hyva\BssSocialLogin\ViewModels;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Bss\SocialLogin\Helper\Data as BssSocialLogin;

/**
 * Class Helper
 * @package Hyva\BssSocialLogin\ViewModels
 */
class Helper implements ArgumentInterface
{
    private BssSocialLogin $helper;

    /**
     * @param BssSocialLogin $data
     */
    public function __construct(
        BssSocialLogin $data
    ) {
        $this->helper = $data;
    }

    /**
     * @return BssSocialLogin
     */
    public function getHelper(): BssSocialLogin
    {
        return $this->helper;
    }

    /**
     * @return bool
     */
    public function checkButtonLogin() {
        if ($this->getHelper()->displayPopup()  == 'login' || $this->getHelper()->displayPopup()  == 'both') {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function checkButtonRegister() {
        if ($this->getHelper()->displayPopup()  == 'register' || $this->getHelper()->displayPopup()  == 'both') {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function checkReCaptcha() {
        if (empty($this->getHelper()->getSiteKey()) || empty($this->getHelper()->getSecretKey())) {
            return false;
        }
        return true;
    }
}
