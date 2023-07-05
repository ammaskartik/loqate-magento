<?php

namespace Loqate\ApiIntegration\Setup;

use Loqate\ApiIntegration\Helper\Controller;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\ResourceModel\Address as AddressResourceModel;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    const POSITION_NUMBER = 999;

    private $customerSetupFactory;
    private AddressResourceModel $addressResourceModel;

    public function __construct(
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        AddressResourceModel $addressResourceModel
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->addressResourceModel = $addressResourceModel;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), '1.0.3') < 0) {

            for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
                $fieldName = "loqate_field{$i}_format";
                $fieldLabel = "Enhanced Field {$i}";

                $customerSetup->addAttribute(AddressMetadataInterface::ENTITY_TYPE_ADDRESS, $fieldName, [
                    'label' => $fieldLabel,
                    'input' => 'text',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'source' => '',
                    'required' => false,
                    'position' => self::POSITION_NUMBER + $i,
                    'visible' => true,
                    'system' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                    'frontend_input' => 'hidden',
                    'backend' => '',
                ]);

                $attribute = $customerSetup->getEavConfig()
                    ->getAttribute(AddressMetadataInterface::ENTITY_TYPE_ADDRESS, $fieldName)
                    ->addData([
                        'used_in_forms' => [
                            'adminhtml_customer_address',
                            'adminhtml_customer',
                            'adminhtml_checkout',
                            'customer_address_edit',
                            'customer_register_address',
                            'customer_address',
                        ],
                    ]);
                $attribute->save();
            }
        }
    }
}
