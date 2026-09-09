<?php
declare(strict_types=1);

namespace Dcw\ShipRegionAvailability\Ui\DataProvider\Zip\Form;

use Dcw\ShipRegionAvailability\Api\Data\ZipInterface;
use Dcw\ShipRegionAvailability\Model\Config;
use Dcw\ShipRegionAvailability\Model\ResourceModel\Zip\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int|string, array<string, mixed>>|null
     */
    protected ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];

        $id = (int) $this->request->getParam($this->getRequestFieldName());
        if ($id) {
            $this->collection->addFieldToFilter($this->getPrimaryFieldName(), $id);
        }

        foreach ($this->collection->getItems() as $zip) {
            $this->loadedData[(int) $zip->getZipId()] = $zip->getData();
        }

        $persisted = $this->dataPersistor->get('dcw_ship_region_zip');
        if (!empty($persisted)) {
            $this->loadedData[''] = $persisted;
            $this->dataPersistor->clear('dcw_ship_region_zip');
        } elseif ($id === 0) {
            $this->loadedData[''] = [
                ZipInterface::STATUS => Config::STATUS_ENABLED,
            ];
        }

        return $this->loadedData;
    }
}
