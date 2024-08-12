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

namespace Megleo\Delivery\Block\Adminhtml\Config\Source;

class TaxaExtra implements \Magento\Framework\Option\ArrayInterface {

	public function toOptionArray() {
		return [
			['value' => '0', 'label' => __('Não')],
			['value' => '1', 'label' => __('Em percentual')],
			['value' => '2', 'label' => __('Em valor')]
		];
	}
}