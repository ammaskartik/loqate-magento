<?php

namespace Loqate\ApiIntegration\Plugin\Admin;

use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\CustomerImportExport\Model\Import\Address;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;

/**
 * Class ValidateImportAddress
 */
class ValidateImportAddress extends AbstractPlugin
{
    /**
     * Check if addresses are valid for batch import
     *
     * @param Address $subject
     * @param $result
     * @return void
     */
    public function afterValidateData(Address $subject, $result)
    {
        if ($this->helper->getConfigValue('loqate_settings/address_settings/enable_customer_import')
        && $subject->getBehavior() == Import::BEHAVIOR_ADD_UPDATE) {
            try {
                $source = $subject->getSource();
                if ($source) {
                    $sourceArray = iterator_to_array($source);
                    $batches = array_chunk($sourceArray, 100);
                    $allRowsResult = [];
                    foreach ($batches as $batch) {
                        $allRowsResult = array_merge($allRowsResult, $this->validator->verifyMultipleAddresses($batch, false));
                    }

                    //check for invalid addresses
                    foreach ($allRowsResult as $index => $validAddress) {
                        if (!$validAddress) {
                            $result->addError(
                                AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION,
                                ProcessingError::ERROR_LEVEL_CRITICAL,
                                $index + 1,
                                null,
                                __('Invalid address at row #') . ($index + 1)
                            );
                        }
                    }
                }
            } catch (\Exception $exception) {
                return $result;
            }
        }

        return $result;
    }
}
