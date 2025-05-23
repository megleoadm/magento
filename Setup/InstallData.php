<?php

/**
* 
* Módulo de integração com a Megleo
* 
* @category     megleo
* @package      Módulo de integração com a Megleo
* @copyright    Copyright (c) 2024 megleo (https://www.megleo.com.br)
*
*/

declare(strict_types=1);

namespace Megleo\Delivery\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{

	private $eavSetupFactory;

	public function __construct(EavSetupFactory $eavSetupFactory)
	{
		$this->eavSetupFactory = $eavSetupFactory;
	}

	public function install(
		ModuleDataSetupInterface $setup,
		ModuleContextInterface $context
	) {
		$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

		if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'volume_altura')) {
			$eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'volume_altura', [
				'group' => 'Frete',
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Altura (cm)',
				'input' => 'text',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => false,
				'unique' => false,
				'apply_to' => 'simple,bundle,grouped,configurable',
				'sort_order' => 1,
				'note' => 'Altura da embalagem do produto (Para cálculo de Frete)'
			]);
		}

		if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'volume_largura')) {
			$eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'volume_largura', [
				'group' => 'Frete',
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Largura (cm)',
				'input' => 'text',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => false,
				'unique' => false,
				'apply_to' => 'simple,bundle,grouped,configurable',
				'sort_order' => 2,
				'note' => 'Largura da embalagem do produto (Para cálculo de Frete)'
			]);
		}

		if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, 'volume_comprimento')) {
			$eavSetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, 'volume_comprimento', [
				'group' => 'Frete',
				'type' => 'decimal',
				'backend' => '',
				'frontend' => '',
				'label' => 'Comprimento (cm)',
				'input' => 'text',
				'class' => '',
				'source' => '',
				'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
				'visible' => true,
				'required' => false,
				'user_defined' => false,
				'default' => '',
				'searchable' => false,
				'filterable' => false,
				'comparable' => false,
				'visible_on_front' => false,
				'used_in_product_listing' => false,
				'unique' => false,
				'apply_to' => 'simple,bundle,grouped,configurable',
				'sort_order' => 3,
				'note' => 'Comprimento da embalagem do produto (Para cálculo de Frete)'
			]);
		}
	}
}
