<?php
declare(strict_types=1);

namespace Dcw\FlooringCalculation\Helper;

use Dcw\CustomAttribute\Model\UsHolidays;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly TimezoneInterface $timezoneInterface,
        private readonly StoreManagerInterface $storeManager,
        private readonly Http $request,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly UsHolidays $usHolidays
    ) {
        parent::__construct($context);
    }

    public function getExpectedShipDate(ProductInterface $product)
    {
        $min_expected_days_ship = $product->getIncstoresPimEsdMin();
        $max_expected_days_ship = $product->getIncstoresPimEsdMax();

        $weekend = $this->scopeConfig->getValue('general/locale/weekend', ScopeInterface::SCOPE_STORE);
        $weekendArr = explode(',', $weekend);

        // Holiday list into an array of dates
        $holidays = $this->usHolidays->getArrayValidated();

        $holidayDates = [];
        foreach ($holidays as $holiday) {
            if ($holiday) {
                $holidayDates[] = date('Y-m-d', strtotime($holiday)); // Convert holiday to Y-m-d format
            }
        }

        // Backorder checking
        $productStock = $product->getExtensionAttributes()?->getStockItem();
        $backorders = $productStock?->getBackorders();
        $promisedate = $product->getPromiseDate();
        $pdate = "";
        if ($promisedate != "") {
            $pdate = date('Y-m-d', strtotime($promisedate));
        }

        $current_date = $this->timezoneInterface->date()->format('Y-m-d');

        if ($backorders != 0 && $pdate != "" && $pdate > $current_date) {
            $current_date = $pdate;
        } else {
            $current_date = $current_date;
        }

        /* ------------------ expected Days Ship From START ----------------------*/
        $d = new \DateTime($current_date);
        $t = $d->getTimestamp();
        for ($i = 0; $i < $min_expected_days_ship; $i++) {
            // add 1 day to timestamp
            $addDay = 86400;

            // get what day it is next day
            $nextDay = date('w', ($t + $addDay));
            $nextDate = date('Y-m-d', ($t + $addDay)); // Get the full date for comparison

            // Check if it's a weekend or holiday
            if (in_array($nextDay, $weekendArr) || in_array($nextDate, $holidayDates)) {
                $i--; // Skip this day if it's a weekend or holiday
            }

            // modify timestamp, add 1 day
            $t = $t + $addDay;
        }
        $d->setTimestamp($t);
        $expectedDaysShipFrom = $d->format('M j');
        /* ------------------ expected Days Ship From END ----------------------*/

        /* ------------------ expected Days Ship To START ----------------------*/
        $d = new \DateTime($current_date);
        $t = $d->getTimestamp();
        for ($i = 0; $i < $max_expected_days_ship; $i++) {
            // add 1 day to timestamp
            $addDay = 86400;

            // get what day it is next day
            $nextDay = date('w', ($t + $addDay));
            $nextDate = date('Y-m-d', ($t + $addDay)); // Get the full date for comparison

            // Check if it's a weekend or holiday
            if (in_array($nextDay, $weekendArr) || in_array($nextDate, $holidayDates)) {
                $i--; // Skip this day if it's a weekend or holiday
            }

            // modify timestamp, add 1 day
            $t = $t + $addDay;
        }
        $d->setTimestamp($t);
        $expectedDaysShipTo = $d->format('M j');
        /* ------------------ expected Days Ship To END ----------------------*/

        if (empty($min_expected_days_ship) && !empty($max_expected_days_ship)) {
            $shipBetweenHtml = 'Ship by ' . $expectedDaysShipTo;
        } elseif (!empty($min_expected_days_ship) && empty($max_expected_days_ship)) {
            $shipBetweenHtml = 'Ship after ' . $expectedDaysShipFrom;
        } elseif (!empty($min_expected_days_ship) && !empty($max_expected_days_ship)) {
            $shipBetweenHtml = 'Ships between ' . $expectedDaysShipFrom . ' - ' . $expectedDaysShipTo;
        } else {
            $shipBetweenHtml = '';
        }

        return $shipBetweenHtml;
    }
	
	public function getExpectedShipDateOOSYes(ProductInterface $product)
    {
		$shipBetweenHtml = 'Shipping soon';

		try {
			$value = $product->getIncstoresPimPromiseDate();
			$promisedate = trim(is_string($value) ? $value : '');

            if (preg_match('/^\d+$/', $promisedate)) {
                return $shipBetweenHtml;
            }

			if (!empty($promisedate)) {
				// Normalize any non-standard dashes (en dash, em dash)
				$promisedate = str_replace(['–', '—'], '-', $promisedate);

				$currentDate = $this->timezoneInterface->date()->format('Y-m-d');
				$isNotPastDate = false;

				if (strpos($promisedate, '-') !== false) {
					// Date range
					list($start, $end) = explode('-', $promisedate);

					// strtotime() returns false on parse failure; date() requires ?int in PHP 8+, so validate before use
					$endTimestamp = strtotime(trim($end));
					if ($endTimestamp !== false) {
						$endDate = date('Y-m-d', $endTimestamp);
						if ($currentDate <= $endDate) {
							$isNotPastDate = true;
						}
					}
				} else {
					// Single date - strtotime() returns false on parse failure; date() requires ?int in PHP 8+
					$pdateTimestamp = strtotime($promisedate);
					if ($pdateTimestamp !== false) {
						$pdate = date('Y-m-d', $pdateTimestamp);
						if ($pdate >= $currentDate) {
							$isNotPastDate = true;
						}
					}
				}

				if ($isNotPastDate) {
					if (strpos($promisedate, '-') !== false) {
						$shipBetweenHtml = 'Ships between ' . $promisedate;
					} else {
						$shipBetweenHtml = 'Ship by ' . $promisedate;
					}
				}
			}
		} catch (\Exception $e) {
			$shipBetweenHtml = 'Shipping soon';
		}


        return $shipBetweenHtml;
    }

    public function isCartConfigurePdp()
    {
        $moduleName = $this->request->getModuleName();// checkout
        $controller = $this->request->getControllerName();// cart
        $action = $this->request->getActionName();// configure
        $route = $this->request->getRouteName();// checkout

        if (($moduleName == 'checkout') && ($controller == 'cart') &&
            ($action == 'configure') && ($route == 'checkout')) {
            return true;
        }

        return false;
    }

    public function getCategoryCollection($attributecode)
    {
        $categories = $this->categoryCollectionFactory->create();
        $categories->addAttributeToSelect('*')?->addAttributeToFilter($attributecode, 1)
            ?->addAttributeToSort('sort_by_featured_partner', 'ASC');

        return $categories;
    }

    public function getPlaceHolderImage()
    {
        return $this->scopeConfig->getValue('catalog/placeholder/thumbnail_placeholder', ScopeInterface::SCOPE_STORE);
    }

    public function getMobileLimitFeature()
    {
        return $this->scopeConfig->getValue('dcw_home_page/featured_partner/mobile_limit', ScopeInterface::SCOPE_STORE);
    }

    public function getDesktopLimitFeature()
    {
        return $this->scopeConfig->getValue('dcw_home_page/featured_partner/desktop_limit', ScopeInterface::SCOPE_STORE);
    }

    public function getMobileLimitCategory()
    {
        return $this->scopeConfig->getValue('dcw_home_page/shopbycategory/mobile_limit', ScopeInterface::SCOPE_STORE);
    }

    public function getDesktopLimitCategory()
    {
        return $this->scopeConfig->getValue('dcw_home_page/shopbycategory/desktop_limit', ScopeInterface::SCOPE_STORE);
    }

    public function getImageUrl($image)
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'catalog/product/' . $image;

    }

    public function getPDPShippingProgramText(ProductInterface $product)
    {
        $shippingProgram = $product->getAttributeText('incstores_pim_shipping_program');
        if (!empty($product->getIncstoresPimShippingProgram()) && $shippingProgram != 'none') {
            $pdp_quick_ship = $shippingProgram;
        } else {
            $pdp_quick_ship = '';
        }
        return $pdp_quick_ship;
    }

    public function getCalculatorTypeText(ProductInterface $product)
    {
        $calculatorType = $product->getAttributeText('incstores_pim_calculator_type');
        if (!empty($product->getIncstoresPimCalculatorType()) && $calculatorType != 'none') {
            $calculator_type = $calculatorType;
        } else {
            $calculator_type = 'none';
        }
        return $calculator_type;
    }

    public function getShippingProgram(ProductInterface $product)
    {
        $adminStoreId = Store::ADMIN_CODE;
        $this->storeManager->setCurrentStore($adminStoreId);
        $shippingProgramText = (string)$product->getAttributeText('incstores_pim_shipping_program');
        $shippingProgramparts = explode('|', $shippingProgramText);
        if (count($shippingProgramparts) >= 2) {
            $shippingProgram = $shippingProgramparts[0];
        } else {
            $shippingProgram = '';
        }
        if (!empty($product->getIncstoresPimShippingProgram()) && $shippingProgram != 'none' &&
            $shippingProgram != 'free_ship') {
            $pdp_quick_ship = 1;
        } else {
            $pdp_quick_ship = '';
        }
        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        $this->storeManager->setCurrentStore($storeId);
        return $pdp_quick_ship;
    }
}
