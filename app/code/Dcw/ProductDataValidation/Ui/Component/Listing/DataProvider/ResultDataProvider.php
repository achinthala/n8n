<?php
declare(strict_types=1);

namespace Dcw\ProductDataValidation\Ui\Component\Listing\DataProvider;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;

/**
 * Applies job_id request param as a filter for the results grid.
 */
class ResultDataProvider extends DataProvider
{
    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $jobId = (int) $this->request->getParam('job_id');
        if ($jobId > 0) {
            $this->addFilter(
                $this->filterBuilder
                    ->setField('job_id')
                    ->setValue((string) $jobId)
                    ->setConditionType('eq')
                    ->create()
            );
        }

        return parent::getData();
    }
}
