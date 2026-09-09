<?php
declare(strict_types=1);

namespace Dcw\ProductEnquiry\Model;

use Dcw\ProductEnquiry\Model\Email\EnquiryEmailSender;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class EnquiryManager
{
    public function __construct(
        private readonly EnquiryFactory $enquiryFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerSession $customerSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly EnquiryEmailSender $emailSender,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     message?: string,
     *     product_id?: int|string|null
     * } $data
     * @throws LocalizedException
     */
    public function createEnquiry(array $data): Enquiry
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $productId = (int) ($data['product_id'] ?? 0);

        if ($name === '') {
            throw new LocalizedException(__('Name is required.'));
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }

        if ($message === '') {
            throw new LocalizedException(__('Message is required.'));
        }

        if ($productId <= 0) {
            throw new LocalizedException(__('Product is required.'));
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The requested product could not be found.'));
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;

        /** @var Enquiry $enquiry */
        $enquiry = $this->enquiryFactory->create();
        $enquiry->setData([
            'store_id' => $storeId,
            'product_id' => (int) $product->getId(),
            'product_sku' => (string) $product->getSku(),
            'product_name' => (string) $product->getName(),
            'customer_id' => $customerId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'message' => $message,
            'status' => Enquiry::STATUS_NEW,
            'remote_ip' => $this->remoteAddress->getRemoteAddress() ?: null,
        ]);
        $enquiry->save();

        try {
            $this->emailSender->sendStoreNotification($enquiry);
            $this->emailSender->sendCustomerAcknowledgement($enquiry);
        } catch (\Throwable $e) {
            $this->logger->error('ProductEnquiry email failed after save: ' . $e->getMessage(), [
                'enquiry_id' => $enquiry->getEnquiryId(),
            ]);
        }

        return $enquiry;
    }
}
