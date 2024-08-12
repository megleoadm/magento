<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Megleo\Delivery\Setup\Patch\Data;

use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class CreateCustomerCNPJAttribute implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig,
        AttributeResource $attributeResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeResource = $attributeResource;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(
            CustomerModel::ENTITY,
            'cnpj',
            [
                'type' => 'varchar',
                'label' => 'CNPJ',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'position' => 999,
                'system' => 0,
            ]
        );

        $attribute = $this->eavConfig->getAttribute(
            CustomerModel::ENTITY,
            'cnpj'
        );

        // #2 add to forms
        $attribute->setData('used_in_forms', [
            'adminhtml_customer',
            'checkout_register',
            'customer_account_create',
            'customer_account_edit'
        ]);

        $attribute->addData([
            'attribute_set_id' => 1,
            'attribute_group_id' => 1
        ]);

        // #3 save
        $this->attributeResource->save($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
