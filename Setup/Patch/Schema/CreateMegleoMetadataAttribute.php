<?php

namespace Megleo\Delivery\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\DB\Ddl\Table;

class CreateMegleoMetadataAttribute implements SchemaPatchInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        // ✅ Importante: Executar setup fora da transação
        $setup = $this->moduleDataSetup;
        $connection = $setup->getConnection();

        $setup->startSetup();

        $orderTable = $setup->getTable('sales_order');

        if (!$connection->tableColumnExists($orderTable, 'megleo_metadata')) {
            $connection->addColumn(
                $orderTable,
                'megleo_metadata',
                [
                    'type' => Table::TYPE_TEXT,
                    'nullable' => true,
                    'comment' => 'Megleo Metadata JSON',
                ]
            );
        }

        $setup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
