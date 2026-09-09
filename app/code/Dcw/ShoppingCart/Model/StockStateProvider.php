<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Dcw\ShoppingCart\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Math\Division as MathDivision;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Provider stocks state
 */
class StockStateProvider extends \Magento\CatalogInventory\Model\StockStateProvider
{
    protected $productRepository;

    /**
     * @param MathDivision $mathDivision
     * @param FormatInterface $localeFormat
     * @param ObjectFactory $objectFactory
     * @param ProductFactory $productFactory
     * @param bool $qtyCheckApplicable
     */
    public function __construct(
        MathDivision $mathDivision,
        FormatInterface $localeFormat,
        ObjectFactory $objectFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        $qtyCheckApplicable = true
    ) {
        parent::__construct(
            $mathDivision,
            $localeFormat,
            $objectFactory,
            $productFactory,
            $qtyCheckApplicable
        );

        $this->productRepository = $productRepository;
    }



    /**
     * Validate quote qty
     *
     * @param StockItemInterface $stockItem
     * @param int|float $qty
     * @param int|float $summaryQty
     * @param int|float $origQty
     * @return \Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkQuoteItemQty(StockItemInterface $stockItem, $qty, $summaryQty, $origQty = 0)
    {
        $loadProduct = $this->productRepository->getById($stockItem->getProductId());
        $promiseDate = $loadProduct->getIncstoresPimPromiseDate();
        $promiseDateText = "";

        if ($promiseDate) {
            $promiseDateText = '[Estimated ship date: '.$promiseDate.']';
        }

        $result = $this->objectFactory->create();
        $result->setHasError(false);
        $qty = $this->getNumber($qty);
        $quoteMessage = __('Please correct the quantity for some products.');

        if ($stockItem->getMinSaleQty() && $qty < $stockItem->getMinSaleQty()) {
            $result->setHasError(true)
                ->setMessage(__('The fewest you may purchase is %1.', $stockItem->getMinSaleQty() * 1))
                ->setErrorCode('qty_min')
                ->setQuoteMessage($quoteMessage)
                ->setQuoteMessageIndex('qty');
            return $result;
        }

        if ($stockItem->getMaxSaleQty() && $qty > $stockItem->getMaxSaleQty()) {
            $result->setHasError(true)
                ->setMessage(__('The requested qty exceeds the maximum qty allowed in shopping cart'))
                ->setErrorCode('qty_max')
                ->setQuoteMessage($quoteMessage)
                ->setQuoteMessageIndex('qty');
            return $result;
        }

        $result->addData($this->checkQtyIncrements($stockItem, $qty)->getData());

        $result->setItemIsQtyDecimal($stockItem->getIsQtyDecimal());
        if (!$stockItem->getIsQtyDecimal() && (floor($qty) !== (float) $qty)) {
            $result->setHasError(true)
                ->setMessage(__('You cannot use decimal quantity for this product.'))
                ->setErrorCode('qty_decimal')
                ->setQuoteMessage($quoteMessage)
                ->setQuoteMessageIndex('qty');

            return $result;
        }

        if ($result->getHasError()) {
            return $result;
        }

        if (!$stockItem->getManageStock()) {
            return $result;
        }

        if (!$stockItem->getIsInStock()) {
            $result->setHasError(true)
                ->setErrorCode('out_stock')
                ->setMessage(__('This product is out of stock.'))
                ->setQuoteMessage(__('Some of the products are out of stock.'))
                ->setQuoteMessageIndex('stock');
            $result->setItemUseOldQty(true);
            return $result;
        }

        $qtyAvailable = 0;

        if (!$this->checkQty($stockItem, $summaryQty) || !$this->checkQty($stockItem, $qty)) {
            $message = __('The requested qty is not available');
            $result->setHasError(true)
                ->setErrorCode('qty_available')
                ->setMessage($message)
                ->setQuoteMessage($message)
                ->setQuoteMessageIndex('qty');
            return $result;
        } else {
            if ($stockItem->getQty() - $summaryQty < 0) {
                if ($stockItem->getProductName()) {
                    if ($stockItem->getIsChildItem()) {
                        $backOrderQty = $stockItem->getQty() > 0 ? ($summaryQty - $stockItem->getQty()) * 1 : $qty * 1;
                        if ($backOrderQty > $qty) {
                            $backOrderQty = $qty;
                        }

                        $qtyAvailable = ($stockItem->getQty() - ($summaryQty - $qty)) * 1;

                        $result->setItemBackorders($backOrderQty);
                    } else {
                        $orderedItems = (int)$stockItem->getOrderedItems();

                        // Available item qty in stock excluding item qty in other quotes
                        $qtyAvailable = ($stockItem->getQty() - ($summaryQty - $qty)) * 1;
                        if ($qtyAvailable > 0) {
                            $backOrderQty = $qty * 1 - $qtyAvailable;
                        } else {
                            $backOrderQty = $qty * 1;
                        }

                        if ($backOrderQty > 0) {
                            $result->setItemBackorders($backOrderQty);
                        }
                        $stockItem->setOrderedItems($orderedItems + $qty);
                    }

                    if ($stockItem->getBackorders() == \Magento\CatalogInventory\Model\Stock::BACKORDERS_YES_NOTIFY) {
                        if (!$stockItem->getIsChildItem()) {
                            $result->setMessage(
                                __(
                                    'We have %1 in stock, and pre-orders will ship soon. Contact us at
                                    866-416-6388 with any questions! %2',
                                    $qtyAvailable,
                                    $promiseDateText
                                )
                            );
                        } else {
                            $result->setMessage(
                                __(
                                    'We have %1 in stock, and pre-orders will ship soon. Contact us at
                                    866-416-6388 with any questions! %2',
                                    $qtyAvailable,
                                    $promiseDateText
                                )
                            );
                        }
                    } elseif ($stockItem->getShowDefaultNotificationMessage()) {
                        $result->setMessage(
                            __('The requested qty is not available')
                        );
                    }
                }
            } else {
                if (!$stockItem->getIsChildItem()) {
                    $stockItem->setOrderedItems($qty + (int)$stockItem->getOrderedItems());
                }
            }
        }
        return $result;
    }
}
