<?php
namespace Dcw\CustomTax\Controller\Adminhtml\CustomTax;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Dcw\CustomTax\Controller\Adminhtml\CustomTax
{
    public function execute()
    {
        $resultRedirect = $this->_resultRedirectFactory->create();
        $finalCsvWithHeading = [];
        $finalCsvArrHeadingAsKey = [];
        $file = $this->getRequest()->getFiles('sheet_file');

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();

        if (!empty($file["name"])) {
            
            $fileInfo = $this->file->getPathInfo($file["name"]);
            $extension = $fileInfo['extension'];
            if ($extension == 'csv') {
                
                $missing_heading_error = 0;
                $missing_heading_arr = [];
                $csvDataAll = $this->_csv->getData($file['tmp_name']);
                $csvDataHeadingArr = $csvDataAll[0];
                $required_csv_heading_arr = ['State','ZipCode','EstimatedCombinedRate'];
                foreach ($required_csv_heading_arr as $required_csv_heading) {
                    if (!in_array(strtolower($required_csv_heading), array_map('strtolower', $csvDataHeadingArr))) {
                        $missing_heading_error = 1;
                        $missing_heading_arr[] = $required_csv_heading;
                    }
                }
                if ($missing_heading_error == 1) {
                    $this->messageManager
                    ->addError('Can not import due to Missing fields : '.implode(", ", $missing_heading_arr));
                    return $resultRedirect->setPath('*/*/new');
                }

                $finalCsvWithHeading[] = $csvDataHeadingArr;
                
                array_shift($csvDataAll);

                $state_code = '';
                foreach ($csvDataAll as $csvData) {
                    $csvRowDataArr = [];
                    //$csvRowDataArr[] = 'US';
                    $total_csvDataHeadingArr = count($csvDataHeadingArr);
                    for ($i=0; $i < $total_csvDataHeadingArr; $i++) {
                        if ($i == 0 && empty($state_code)) {
                            $state_code = $csvData[$i];
                        }
                        $csvRowDataArr[] = $csvData[$i];
                    }
                    $finalCsvArrHeadingAsKey[] =
                    array_combine(array_map('strtolower', $csvDataHeadingArr), $csvRowDataArr);
                    $finalCsvWithHeading[] = $csvRowDataArr;
                }

                $csvDirectoryWrite = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

                $csvDirectoryWrite->create('custom_tax');

                $fileNameWithPath = $mediaDir.'/custom_tax/import_custom_tax_'.time().'.csv';
                $this->_csv->saveData($fileNameWithPath, $finalCsvWithHeading);
                $csvTaxData = $this->_csv->getData($fileNameWithPath);
                array_shift($csvTaxData);
            
                $newTaxRateIdArr = [];
                foreach ($finalCsvArrHeadingAsKey as $key => $finalCsv) {
                    $region_code = $finalCsv['state'];
                    $postcode = $finalCsv['zipcode'];
                    $rate = $finalCsv['estimatedcombinedrate'];
                    if (!empty($region_code) && !empty($postcode) && !empty($rate)) {
                            
                        $regionId = $this->region->loadByCode($region_code, 'US')->getId();
                        $tax_calculation_rate_table = $this->connection->getTableName("tax_calculation_rate");
                        $select_rate_sql = $this->connection->select()
                        ->from($tax_calculation_rate_table, 'tax_calculation_rate_id')
                        ->where('tax_country_id = ?', 'US')
                        ->where('tax_region_id = ?', $regionId)
                        ->where('tax_postcode = ?', $postcode)
                        ;
                        $tax_calculation_rate_id = $this->connection->fetchOne($select_rate_sql);
                        //echo '<br>Exist tax_calculation_rate_id ='.$tax_calculation_rate_id
                        if (!empty($tax_calculation_rate_id) && $tax_calculation_rate_id > 0) {
                            $taxCalculationRate = $this->objectManager
                            ->create(\Magento\Tax\Model\Calculation\Rate::class);
                            $taxCalculationRate = $taxCalculationRate->load($tax_calculation_rate_id);
                        } else {
                            $taxCalculationRate = $this->objectManager
                            ->create(\Magento\Tax\Model\Calculation\Rate::class);
                            $taxCalculationRate->setCode("US-".$region_code."-".$postcode."-Rate");
                            $taxCalculationRate->setTaxCountryId("US");
                            $taxCalculationRate->setTaxRegionId($regionId);
                            $taxCalculationRate->setTaxPostcode($postcode);
                        }
                        
                        $finalRate = $rate * 100;

                        $taxCalculationRate->setRate($finalRate);
                        $taxCalculationRate->save();
                        $newTaxRateIdArr[] = $taxCalculationRate->getId();
                    
                    } else {
                            
                        $err_field_data_arr = [];
                        if (empty($region_code)) {
                            $err_field_data_arr[] = 'State';
                        }
                        if (empty($postcode)) {
                            $err_field_data_arr[] = 'ZipCode';
                        }
                        if (empty($rate)) {
                            $err_field_data_arr[] = 'EstimatedCombinedRate';
                        }
                        $err_msg = 'Row No.'.($key+2).': value is missing for '.implode(', ', $err_field_data_arr);
                        $this->messageManager->addError($err_msg);
                    }
                        
                }
                    
                $existTaxRateIdArr = [];
                $tax_calculation_rule_table = $this->connection->getTableName("tax_calculation_rule");
                $select_rule_sql = $this->connection->select()
                ->from($tax_calculation_rule_table, 'tax_calculation_rule_id')
                ->where('code = ?', 'US-'.$state_code.'-tax rule');
                $tax_calculation_rule_id = $this->connection->fetchOne($select_rule_sql);
                //echo '<br>Exist tax_calculation_rule_id ='.$tax_calculation_rule_id;

                if (!empty($tax_calculation_rule_id) && $tax_calculation_rule_id > 0) {
                    $taxCalcRule = $this->objectManager->create(\Magento\Tax\Model\Calculation\Rule::class);
                    $taxCalcRule = $taxCalcRule->load($tax_calculation_rule_id);

                    $existTaxRateIdArr = $this->_calculation->getRates($tax_calculation_rule_id);
                } else {
                    $taxCalcRule = $this->objectManager->create(\Magento\Tax\Model\Calculation\Rule::class);
                    $taxCalcRule->setCode('US-'.$state_code.'-tax rule');
                }

                $taxRateIdArr = array_merge($existTaxRateIdArr, $newTaxRateIdArr);
                $taxRateIdArr = array_unique($taxRateIdArr);
                if (!empty($newTaxRateIdArr)) {
                    $taxCalcRule->setPriority(0);
                    $taxCalcRule->setCustomerTaxClassIds([3]);
                    $taxCalcRule->setProductTaxClassIds([2]);
                    $taxCalcRule->setTaxRateIds($taxRateIdArr);
                    $taxCalcRule->save();
                }
                
                //echo  '<br>rule->getId ='. $taxCalcRule->getId();

                $this->messageManager->addSuccess('Custom Tax imported successfully');
            } else {
                $this->messageManager->addError('Only CSV file allowed');
            }
        } else {
            $this->messageManager->addError('There is no file to import data');
        }

        return $resultRedirect->setPath('*/*/new');
    }
}
