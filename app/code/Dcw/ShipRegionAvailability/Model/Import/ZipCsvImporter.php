<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Model\Import;

use Dcw\ShipRegionAvailability\Api\RegionLookupInterface;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\Config\Source\ZipImportMode;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip as ZipResource;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as CsvReader;

class ZipCsvImporter
{
    private const CACHE_TAG = 'dcw_ship_region_zip';
    private const BATCH_SIZE = 500;
    private const MAX_ERRORS_REPORTED = 50;

    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly RegionLookupInterface $regionLookup,
        private readonly RegionCollectionFactory $regionCollectionFactory,
        private readonly ZipResource $zipResource,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Import client ZIP sheet (e.g. zip-to-region.csv: zip, region, ...).
     * Region labels such as "Midwest" are normalized to region codes (e.g. midwest).
     *
     * @param array<string, mixed>|null $file
     * @param string $mode ZipImportMode::MODE_MERGE or MODE_REPLACE_ALL
     * @return array{imported: int, skipped: int, deleted: int, errors: string[], mode: string}
     * @throws LocalizedException
     */
    public function import(?array $file, string $mode = ZipImportMode::MODE_MERGE): array
    {
        if (empty($file['tmp_name'])) {
            throw new LocalizedException(__('Please upload a CSV file.'));
        }

        if (!in_array($mode, [ZipImportMode::MODE_MERGE, ZipImportMode::MODE_REPLACE_ALL], true)) {
            throw new LocalizedException(__('Invalid import mode selected.'));
        }

        $rows = $this->csvReader->getData($file['tmp_name']);
        if (count($rows) < 2) {
            throw new LocalizedException(__('CSV file is empty or has no data rows.'));
        }

        $header = $this->normalizeHeaderRow($rows[0]);
        $zipIndex = $this->findColumnIndex($header, ['zipcode', 'zip', 'zip_code', 'postcode', 'postal_code']);
        $regionIndex = $this->findColumnIndex($header, ['region', 'region_code']);

        if ($zipIndex === null || $regionIndex === null) {
            throw new LocalizedException(
                __('CSV must include "zip" and "region" columns (client sheet format).')
            );
        }

        $regionIdByCode = $this->loadRegionIdByCodeMap();
        $connection = $this->zipResource->getConnection();
        $table = $this->zipResource->getMainTable();

        $deleted = 0;
        if ($mode === ZipImportMode::MODE_REPLACE_ALL) {
            $deleted = $this->deleteAllZipMappings($connection, $table);
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $batch = [];

        for ($i = 1, $iMax = count($rows); $i < $iMax; $i++) {
            $row = $rows[$i];
            $zipRaw = trim((string) ($row[$zipIndex] ?? ''));
            $regionRaw = trim((string) ($row[$regionIndex] ?? ''));

            if ($zipRaw === '' && $regionRaw === '') {
                continue;
            }

            if (strtolower($regionRaw) === 'region') {
                continue;
            }

            $zipCode = $this->regionLookup->normalizeZipCode($zipRaw);
            if ($zipCode === null) {
                $skipped++;
                $this->addError($errors, (string) __('Row %1: invalid ZIP "%2"', $i + 1, $zipRaw));
                continue;
            }

            $regionCode = $this->normalizeRegionCode($regionRaw);
            if ($regionCode === '') {
                $skipped++;
                $this->addError($errors, (string) __('Row %1: empty region for ZIP %2', $i + 1, $zipCode));
                continue;
            }

            if (!isset($regionIdByCode[$regionCode])) {
                $skipped++;
                $this->addError(
                    $errors,
                    (string) __(
                        'Row %1: region "%2" (code "%3") not found in Ship Regions admin. Create it first.',
                        $i + 1,
                        $regionRaw,
                        $regionCode
                    )
                );
                continue;
            }

            $batch[] = [
                'zip_code' => $zipCode,
                'region_id' => $regionIdByCode[$regionCode],
                'status' => Config::STATUS_ENABLED,
            ];

            if (count($batch) >= self::BATCH_SIZE) {
                $imported += $this->flushBatch($connection, $table, $batch);
            }
        }

        if ($batch !== []) {
            $imported += $this->flushBatch($connection, $table, $batch);
        }

        $this->cache->clean([self::CACHE_TAG]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'deleted' => $deleted,
            'errors' => $errors,
            'mode' => $mode,
        ];
    }

    private function deleteAllZipMappings(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $table
    ): int {
        $count = (int) $connection->fetchOne(
            $connection->select()->from($table, [new Expression('COUNT(*)')])
        );
        $connection->delete($table);

        return $count;
    }

    /**
     * Client sheet uses display names (Midwest, Northeast). Store as lowercase region codes.
     */
    public function normalizeRegionCode(string $region): string
    {
        return strtolower(trim($region));
    }

    /**
     * @param list<string> $headerRow
     * @return list<string>
     */
    private function normalizeHeaderRow(array $headerRow): array
    {
        $header = array_map(static fn ($value) => strtolower(trim((string) $value)), $headerRow);
        if (isset($header[0])) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }

        return $header;
    }

    /**
     * @return array<string, int>
     */
    private function loadRegionIdByCodeMap(): array
    {
        $map = [];
        $collection = $this->regionCollectionFactory->create();
        foreach ($collection as $region) {
            $code = $this->normalizeRegionCode((string) $region->getCode());
            if ($code !== '') {
                $map[$code] = (int) $region->getRegionId();
            }
        }

        return $map;
    }

    /**
     * @param list<array{zip_code: string, region_id: int, status: int}> $batch
     */
    private function flushBatch(
        \Magento\Framework\DB\Adapter\AdapterInterface $connection,
        string $table,
        array &$batch
    ): int {
        $count = count($batch);
        $connection->insertOnDuplicate(
            $table,
            $batch,
            ['region_id', 'status']
        );
        $batch = [];

        return $count;
    }

    /**
     * @param string[] $header
     * @param string[] $candidates
     */
    private function findColumnIndex(array $header, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $header, true);
            if ($index !== false) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @param string[] $errors
     */
    private function addError(array &$errors, string $message): void
    {
        if (count($errors) < self::MAX_ERRORS_REPORTED) {
            $errors[] = $message;
        }
    }
}
