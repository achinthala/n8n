<?php
namespace Dcw\CircularDesignerTool\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CdtAttributeData implements DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Dcw\CircularDesignerTool\Helper\Data $circularDesignerHelper
    ) {

        $this->moduleDataSetup = $moduleDataSetup;
        $this->circularDesignerHelper = $circularDesignerHelper;
    }
    public function apply()
    {

        $this->moduleDataSetup->startSetup();
        $setup = $this->moduleDataSetup;

        $dataTab[] = ['cdt_tab_title' => 'Size', 'cdt_tab_sort_order' => 1];
        $dataTab[] = ['cdt_tab_title' => 'Circle', 'cdt_tab_sort_order' => 2];
        $dataTab[] = ['cdt_tab_title' => 'Lettering', 'cdt_tab_sort_order' => 3];
        $dataTab[] = ['cdt_tab_title' => 'Logos', 'cdt_tab_sort_order' => 4];

         $this->moduleDataSetup->getConnection()->insertArray(
             $this->moduleDataSetup->getTable('cdt_tab'),
             ['cdt_tab_title', 'cdt_tab_sort_order'],
             $dataTab
         );
        $this->moduleDataSetup->endSetup();

        $this->moduleDataSetup->startSetup();
        $setup = $this->moduleDataSetup;

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Size',
                    'cdt_attribute_value_display' => "42' x 42'",
                    'cdt_attribute_value' => '504x504',
                    'description' => "Seven sections @ 6' x 42' each",
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '72x504',
                     'validation' => '',
                     'validation' => '',
                     'image' => ''
                    ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Size',
                    'cdt_attribute_value_display' => "42' x 40'",
                    'cdt_attribute_value' => '504x480',
                    'description' => "Seven sections @ 6' x 40' each",
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '72x480',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Size',
                    'cdt_attribute_value_display' => "42' x 38'",
                    'cdt_attribute_value' => '504x456',
                    'description' => "Seven sections @ 6' x 38' each; 28' competition circle",
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '72x456',
                    'validation' => '336',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Size',
                    'cdt_attribute_value_display' => "36' x 36'",
                    'cdt_attribute_value' => '432x432',
                    'description' => "Six sections @ 6' x 36' each; 28' competition circle",
                    'cdt_attribute_sort_order' => 4,
                    'sections' => '72x432',
                    'validation' => '336',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Thickness',
                    'cdt_attribute_value_display' => '1-5/8"',
                    'cdt_attribute_value' => '0.44',
                    'description' => "Standard for high school and college competition",
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Thickness',
                    'cdt_attribute_value_display' => '2"',
                    'cdt_attribute_value' => '2',
                    'description' => "Increased protection; required for international competition",
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Royal Blue',
                    'cdt_attribute_value' => '#0047c1',
                    'description' => "",
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Black',
                    'cdt_attribute_value' => '#000000',
                    'description' => "",
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Gold',
                    'cdt_attribute_value' => '#ffc000',
                    'description' => "",
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Gray',
                    'cdt_attribute_value' => '#bdbdbd',
                    'description' => "",
                    'cdt_attribute_sort_order' => 4,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Green',
                    'cdt_attribute_value' => '#024933',
                    'description' => "",
                    'cdt_attribute_sort_order' => 5,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Maroon',
                    'cdt_attribute_value' => '#802f4d',
                    'description' => "",
                    'cdt_attribute_sort_order' => 6,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Navy Blue',
                    'cdt_attribute_value' => '#013666',
                    'description' => "",
                    'cdt_attribute_sort_order' => 7,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Purple',
                    'cdt_attribute_value' => '#56409a',
                    'description' => "",
                    'cdt_attribute_sort_order' => 8,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Size'),
                    'cdt_attribute_title' => 'Select Mat Color',
                    'cdt_attribute_value_display' => 'Red',
                    'cdt_attribute_value' => '#f63336',
                    'description' => "",
                    'cdt_attribute_sort_order' => 9,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Competition Circle Size',
                    'cdt_attribute_value_display' => "28'",
                    'cdt_attribute_value' => '336',
                    'description' => "Required for 36' x 36' and 42' x 38' mats",
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '504x504|504x480|504x456|432x432',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Competition Circle Size',
                    'cdt_attribute_value_display' => "30'",
                    'cdt_attribute_value' => '360',
                    'description' => "Standard for 42' x 42' and 42' x 40' mats",
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '504x504|504x480',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Competition Circle Size',
                    'cdt_attribute_value_display' => "32'",
                    'cdt_attribute_value' => '384',
                    'description' => "Required for NCAA competition",
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '',
                    'validation' => '504x504',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Starting Lines',
                    'cdt_attribute_value_display' => "Yes / No",
                    'cdt_attribute_value' => 1,
                    'description' => "",
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Circle/Fill Colors',
                    'cdt_attribute_value_display' => 'Circle Lines',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|Gold:#fcba28|
                    Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|Royal Blue:#0a2473|
                    Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '<div class="circlethumb"></div>',
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Circle/Fill Colors',
                    'cdt_attribute_value_display' => '10\' Circle Fill 
                    <span style="color:#D93C22; font-size:10px;">($)</span>',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|
                    Gold:#fcba28|Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|
                    Royal Blue:#0a2473|Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|
                    Forest Green:#154724',
                    'description' => '<div class="circlethumb"></div>',
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Circle/Fill Colors',
                    'cdt_attribute_value_display' => 'Wrestling Area Fill 
                    <span style="color:#D93C22; font-size:10px;">($)</span>',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|Gold:#fcba28|
                    Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|Royal Blue:#0a2473|
                    Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '<div class="circlethumb"></div>',
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
       
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Circle/Fill Colors',
                    'cdt_attribute_value_display' => 'Safety Area Fill 
                    <span style="color:#D93C22; font-size:10px;">($)</span>',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|Gold:#fcba28|
                    Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|Royal Blue:#0a2473|
                    Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '<div class="circlethumb"></div>',
                    'cdt_attribute_sort_order' => 4,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
        
        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Circle'),
                    'cdt_attribute_title' => 'Circle/Fill Colors',
                    'cdt_attribute_value_display' => 'Practice Circles 
                    <span style="color:#D93C22; font-size:10px;">($)</span>',
                    'cdt_attribute_value' => 'Mat (Lighter):lighter|Mat (Darker):darker|White:#ffffff|
                    Light Gray:#b3b3b3|Dark Gray:#6d7370|Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|
                    Vegas Gold B:#9f7d23|Gold:#fcba28|Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|
                    Royal Blue:#0a2473|Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '<div class="circlethumb"></div>',
                    'cdt_attribute_sort_order' => 5,
                    'sections' => '',
                    'validation' =>
                    '504x504|504x480',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Lettering'),
                    'cdt_attribute_title' => 'Select a Font',
                    'cdt_attribute_value_display' => 'Select a Font',
                    'cdt_attribute_value' => "ARIAL:Arial|CONDENSED:'Roboto Condensed', sans-serif|
                    ROCKWELL:rockwell_stdbold|QUANTICO:'Quantico', sans-serif|YEARBOOK:Yearbook Solid|
                    TIMES NEW ROMAN:Times New Roman|MACHINE:Machine|IMPACT:Impact, Charcoal, sans-serif|
                    PRINCETOWN:PrincetownSHOP-Regular",
                    'description' => '',
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Lettering'),
                    'cdt_attribute_title' => 'Outline Text',
                    'cdt_attribute_value_display' => 'Yes / No',
                    'cdt_attribute_value' => 0,
                    'description' => '',
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Lettering'),
                    'cdt_attribute_title' => 'Select a Color',
                    'cdt_attribute_value_display' => 'Select a Color',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|Gold:#fcba28|
                    Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|Royal Blue:#0a2473|
                    Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '',
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'None',
                    'cdt_attribute_value' => 'None',
                    'description' => '',
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'none.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Generic',
                    'cdt_attribute_value' => 'Generic',
                    'description' => '',
                    'cdt_attribute_sort_order' => 2,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'generic.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Arrow',
                    'cdt_attribute_value' => 'Arrow',
                    'description' => '',
                    'cdt_attribute_sort_order' => 3,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'arrow.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bear1',
                    'cdt_attribute_value' => 'Bear1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 4,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bear1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bear2',
                    'cdt_attribute_value' => 'Bear2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 5,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bear2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bluejay1',
                    'cdt_attribute_value' => 'Bluejay1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 6,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bluejay1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bulldog1',
                    'cdt_attribute_value' => 'Bulldog1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 7,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bulldog1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bulldog2',
                    'cdt_attribute_value' => 'Bulldog2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 8,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bulldog2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bulldog3',
                    'cdt_attribute_value' => 'Bulldog3',
                    'description' => '',
                    'cdt_attribute_sort_order' => 9,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bulldog3.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Bulldog4',
                    'cdt_attribute_value' => 'Bulldog4',
                    'description' => '',
                    'cdt_attribute_sort_order' => 10,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'bulldog4.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Cardinal1',
                    'cdt_attribute_value' => 'Cardinal1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 11,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'cardinal1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Devil1',
                    'cdt_attribute_value' => 'Devil1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 12,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'devil1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Eagle1',
                    'cdt_attribute_value' => 'Eagle1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 13,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'eagle1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Eagle2',
                    'cdt_attribute_value' => 'Eagle2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 14,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'eagle2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Eagle3',
                    'cdt_attribute_value' => 'Eagle3',
                    'description' => '',
                    'cdt_attribute_sort_order' => 15,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'eagle3.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Hawk1',
                    'cdt_attribute_value' => 'Hawk1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 16,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'hawk1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Hornet',
                    'cdt_attribute_value' => 'Hornet',
                    'description' => '',
                    'cdt_attribute_sort_order' => 17,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'hornet.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Husky1',
                    'cdt_attribute_value' => 'Husky1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 18,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'husky1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Indian1',
                    'cdt_attribute_value' => 'Indian1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 19,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'indian1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Indian2',
                    'cdt_attribute_value' => 'Indian2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 20,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'indian2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Lion1',
                    'cdt_attribute_value' => 'Lion1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 21,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'lion1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Lion2',
                    'cdt_attribute_value' => 'Lion2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 22,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'lion2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Mustang1',
                    'cdt_attribute_value' => 'Mustang1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 23,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'mustang1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Mustang2',
                    'cdt_attribute_value' => 'Mustang2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 24,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'mustang2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Panther1',
                    'cdt_attribute_value' => 'Panther1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 25,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'panther1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Panther2',
                    'cdt_attribute_value' => 'Panther2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 26,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'panther2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Panther3',
                    'cdt_attribute_value' => 'Panther3',
                    'description' => '',
                    'cdt_attribute_sort_order' => 27,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'panther3.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Panther4',
                    'cdt_attribute_value' => 'Panther4',
                    'description' => '',
                    'cdt_attribute_sort_order' => 28,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'panther4.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Panther5',
                    'cdt_attribute_value' => 'Panther5',
                    'description' => '',
                    'cdt_attribute_sort_order' => 29,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'panther5.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Patriot1',
                    'cdt_attribute_value' => 'Patriot1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 30,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'patriot1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Paw1',
                    'cdt_attribute_value' => 'Paw1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 31,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'paw1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Pirate1',
                    'cdt_attribute_value' => 'Pirate1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 32,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'pirate1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Ram1',
                    'cdt_attribute_value' => 'Ram1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 33,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'ram1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Tiger1',
                    'cdt_attribute_value' => 'Tiger1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 34,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'tiger1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Tiger2',
                    'cdt_attribute_value' => 'Tiger2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 35,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'tiger2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Trojan1',
                    'cdt_attribute_value' => 'Trojan1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 36,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'trojan1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Trojan2',
                    'cdt_attribute_value' => 'Trojan2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 37,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'trojan2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Viking1',
                    'cdt_attribute_value' => 'Viking1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 38,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'viking1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Viking2',
                    'cdt_attribute_value' => 'Viking2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 39,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'viking2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Wildcat1',
                    'cdt_attribute_value' => 'Wildcat1',
                    'description' => '',
                    'cdt_attribute_sort_order' => 40,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'wildcat1.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Wildcat2',
                    'cdt_attribute_value' => 'Wildcat2',
                    'description' => '',
                    'cdt_attribute_sort_order' => 41,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'wildcat2.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Logo',
                    'cdt_attribute_value_display' => 'Wildcat3',
                    'cdt_attribute_value' => 'Wildcat3',
                    'description' => '',
                    'cdt_attribute_sort_order' => 42,
                    'sections' => '',
                    'validation' => '',
                    'image' => 'wildcat3.png'
                ];

        $data[] = [
                    'cdt_tab_id' => $this->circularDesignerHelper->getIdFromTabName('Logos'),
                    'cdt_attribute_title' => 'Select a Color',
                    'cdt_attribute_value_display' => 'Select a Color',
                    'cdt_attribute_value' => 'White:#ffffff|Light Gray:#b3b3b3|Dark Gray:#6d7370|
                    Black:#000000|Yellow:#fbdd2e|Vegas Gold A:#ddb63e|Vegas Gold B:#9f7d23|Gold:#fcba28|
                    Orange:#e25120|Red:#a40000|Maroon:#632534|Light Blue:#1e80d0|Royal Blue:#0a2473|
                    Navy Blue:#192b48|Purple:#444069|Kelly Green:#216421|Forest Green:#154724',
                    'description' => '',
                    'cdt_attribute_sort_order' => 1,
                    'sections' => '',
                    'validation' => '',
                    'image' => ''
                ];
    
         $this->moduleDataSetup->getConnection()->insertArray(
             $this->moduleDataSetup->getTable('cdt_attribute'),
             [
                'cdt_tab_id',
                'cdt_attribute_title',
                'cdt_attribute_value_display',
                'cdt_attribute_value',
                'description',
                'cdt_attribute_sort_order',
                'sections',
                'validation',
                'image'
             ],
             $data
         );
        $this->moduleDataSetup->endSetup();
    }
    public function getAliases()
    {
        return [];
    }
    public static function getDependencies()
    {
        return [];
    }
}
