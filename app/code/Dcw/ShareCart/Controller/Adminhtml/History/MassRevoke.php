<?php
declare(strict_types=1);

namespace Dcw\ShareCart\Controller\Adminhtml\History;

use Dcw\ShareCart\Api\Data\ShareCartInterface;
use Dcw\ShareCart\Model\ResourceModel\ShareCart\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassRevoke extends Action
{
    public const ADMIN_RESOURCE = 'Dcw_ShareCart::history';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;

        foreach ($collection as $shareCart) {
            if ($shareCart->getLinkStatus() === ShareCartInterface::LINK_STATUS_ACTIVE) {
                $shareCart->setLinkStatus(ShareCartInterface::LINK_STATUS_REVOKED);
                $shareCart->save();

                $this->logger->info('ShareCart link revoked', [
                    'share_id' => $shareCart->getShareId(),
                ]);

                $count++;
            }
        }

        if ($count) {
            $this->messageManager->addSuccessMessage(__('Revoked %1 share cart link(s).', $count));
        } else {
            $this->messageManager->addNoticeMessage(__('No active links were selected.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
