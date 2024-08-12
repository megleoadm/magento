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

class PesoEncomenda implements \Magento\Framework\Option\ArrayInterface {

	const WEIGHT_GR = 'gr';
	const WEIGHT_KG = 'kg';

	public function toOptionArray() {
		return [
			['value' => self::WEIGHT_GR, 'label' => __('Gramas')],
			['value' => self::WEIGHT_KG, 'label' => __('Kilos')],
		];
	}
}