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

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

	public function install(
		SchemaSetupInterface $setup,
		ModuleContextInterface $context
	) {
	}
}
