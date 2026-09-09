<?php

namespace Narvar\Accord\Model;

use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory as SearchResultFactory;
use Narvar\Accord\Helper\Util;
use Narvar\Accord\Helper\CustomLogger;
use Narvar\Accord\Helper\AccordException;

class NarvarOrderManagement implements \Narvar\Accord\Api\NarvarOrderManagementInterface
{
    private $logger;
    private $searchResultFactory;
    private $collectionProcessor;
    private $extensionAttributesJoinProcessor;
    private $util;

    public function __construct(
        CustomLogger $logger,
        SearchResultFactory $searchResultFactory,
        Util $util,
        CollectionProcessorInterface $collectionProcessor = null,
        JoinProcessorInterface $extensionAttributesJoinProcessor = null
    ) {
        $this->logger = $logger;
        $this->searchResultFactory = $searchResultFactory;
        $this->util = $util;
        $this->collectionProcessor = $collectionProcessor ?: ObjectManager::getInstance()
            ->get(\Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class);
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor
            ?: ObjectManager::getInstance()->get(JoinProcessorInterface::class);
    }
    
    public function getOrders(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        try {
            $this->logger->info('historical sync started');
            $this->checkPageSize($searchCriteria);
            $searchResult = $this->searchResultFactory->create();
            $this->extensionAttributesJoinProcessor->process($searchResult);
            $this->collectionProcessor->process($searchCriteria, $searchResult);
            $searchResult->setSearchCriteria($searchCriteria);
            $orders = $this->getNarvarOrders($searchResult);
            $this->logger->info('historical sync finished');
            return $orders;
        } catch (\Exception $ex) {
            $message = 'Exception : ' . $ex->getMessage() . ' occured in ' . __METHOD__;
            $errorResponse = [];
            $errorResponse['code'] = '500';
            $errorResponse['message'] = $message;
            $this->logger->error(json_encode($errorResponse));
            return $errorResponse;
        }
    }

    private function getNarvarOrders($searchResult)
    {
        $orders = [];
        $eventName = 'historical_pull';
        foreach ($searchResult->getItems() as $order) {
            try {
                $this->logger->debug(
                    'Started Constructing Narvar Order Payload for OrderId: ' . $order->getIncrementId(),
                    $order->getStoreId()
                );
                $narvarOrderObject = $this->util->getNarvarOrderObject(
                    $order,
                    $eventName
                );
                $narvarOrderObject['shipments'] = $this->getNarvarShipments($order);
                $narvarOrderObject['invoices'] = $this->getNarvarInvoices($order);
                $narvarOrderObjectUtf8 = $this->util->utf8ize($narvarOrderObject);
                $this->logger->debug(
                    'Constructed Narvar Order Payload: ' . json_encode($narvarOrderObjectUtf8),
                    $order->getStoreId()
                );
                $orders[] = $narvarOrderObjectUtf8;
            } catch (\Exception $ex) {
                $this->logger->error('Error Constructing Narvar Order Payload for OrderId: '
                . $order->getIncrementId() . ' Exception : ' . $ex->getMessage());
            }
        }
        
        return $orders;
    }

    private function getNarvarShipments($order)
    {
        $narvarShipments = [];
        $shipments = $this->util->getOrderShipments($order);
        foreach ($shipments as $shipment) {
            $narvarShipments[] = $this->util->getShipmentData($shipment, $order->getStoreId());
        }
        return $narvarShipments;
    }

    private function getNarvarInvoices($order)
    {
        $narvarInvoices = [];
        $invoices = $this->util->getOrderInvoices($order);
        foreach ($invoices as $invoice) {
            $narvarInvoices[] = $this->util->getInvoiceData($invoice, $order->getStoreId());
        }
        return $narvarInvoices;
    }

    private function checkPageSize($searchCriteria)
    {
        $maxPageSize = 250;
        if (is_null($searchCriteria->getPageSize())) {
            $this->logger->info('Page Size not given, setting as max page size');
            $searchCriteria->setPageSize($maxPageSize);
        } elseif ($searchCriteria->getPageSize() > $maxPageSize) {
            $errorMessage = 'Page Size: ' . $searchCriteria->getPageSize() .
            ' greater than Maximum Limit: ' . $maxPageSize;
            $this->logger->error($errorMessage);
            throw new AccordException($errorMessage);
        }
    }
}
