<?php
declare(strict_types=1);

namespace Dcw\Localization\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class ShipsFromOptions extends AbstractSource
{
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $this->_options = [
            ['label' => '', 'value' => '0'],
            ['label' => 'East Coast', 'value' => '1'],
            ['label' => 'West Coast', 'value' => '2'],
            ['label' => 'Midwest', 'value' => '3'],
            ['label' => 'South', 'value' => '4']
        ];
        return $this->_options;
    }
}