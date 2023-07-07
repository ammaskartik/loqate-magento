<?php

namespace Loqate\ApiIntegration\Setup;

use Loqate\ApiIntegration\Helper\Controller;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\ResourceModel\Address as AddressResourceModel;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;

class UpgradeData implements UpgradeDataInterface
{
    const POSITION_NUMBER = 999;

    private CustomerSetupFactory $customerSetupFactory;
    private SalesSetupFactory $salesSetupFactory;
    private QuoteSetupFactory $quoteSetupFactory;

    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;

        $this->quoteSetupFactory = $quoteSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.3') < 0) {

            $setup->startSetup();

            $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
            $quoteSetup = $this->quoteSetupFactory->create(['setup' => $setup]);

            $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

            for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
                $fieldName = "loqate_field{$i}_format";
                $fieldLabel = "Enhanced Field {$i}";

                $salesSetup->addAttribute('order_address', $fieldName, [
                    'label' => $fieldLabel,
                    'input' => 'text',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'source' => '',
                    'required' => false,
                    'position' => self::POSITION_NUMBER + $i,
                    'visible' => true,//todo set false
                    'system' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                    'frontend_input' => 'hidden',
                    'backend' => '',
                ]);

                $quoteSetup->addAttribute('quote_address', $fieldName, [
                    'label' => $fieldLabel,
                    'input' => 'text',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'source' => '',
                    'required' => false,
                    'position' => 2002,
                    'visible' => true,//todo set false
                    'system' => false,
                    'is_used_in_grid' => false,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => false,
                    'is_searchable_in_grid' => false,
                    'frontend_input' => 'hidden',
                    'backend' => '',
                ]);

                $customerSetup->addAttribute(AddressMetadataInterface::ENTITY_TYPE_ADDRESS, $fieldName, [
                    'label' => $fieldLabel,
                    'input' => 'text',
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'source' => '',
                    'required' => false,
                    'position' => self::POSITION_NUMBER + $i,
                    'visible' => true,//todo set false
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
            $setup->endSetup();
        }
    }
}
