<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\Export;

use Dcw\ShipRegionAvailability\Model\ResourceModel\Region;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip\CollectionFactory as ZipCollectionFactory;

class ZipCsvExporter
{
    /** @var list<list<string>> */
    private const SAMPLE_ROWS = [
        ['zipcode', 'region'],
        ['60601', 'midwest'],
        ['10001', 'northeast'],
        ['30301', 'south'],
        ['90210', 'west'],
        ['75201', 'south'],
    ];

    public function __construct(
        private readonly ZipCollectionFactory $zipCollectionFactory
    ) {
    }

    public function getSampleCsvContent(): string
    {
        return $this->rowsToCsv(self::SAMPLE_ROWS);
    }

    public function getExportCsvContent(): string
    {
        return $this->rowsToCsv($this->buildExportRows());
    }

    /**
     * @return list<list<string>>
     */
    private function buildExportRows(): array
    {
        $collection = $this->zipCollectionFactory->create();
        $collection->getSelect()->joinLeft(
            ['region_table' => $collection->getTable(Region::TABLE_NAME)],
            'main_table.region_id = region_table.region_id',
            ['region_code' => 'code']
        );
        $collection->setOrder('zip_code', 'ASC');

        $rows = [['zipcode', 'region']];
        foreach ($collection as $zip) {
            $zipCode = (string) $zip->getZipCode();
            $regionCode = (string) ($zip->getData('region_code') ?? '');
            if ($zipCode === '') {
                continue;
            }
            $rows[] = [$zipCode, $regionCode];
        }

        return $rows;
    }

    /**
     * @param list<list<string>> $rows
     */
    public function rowsToCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}
