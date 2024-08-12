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

class FormatoEmbalagem implements \Magento\Framework\Option\ArrayInterface {

	public function toOptionArray() {
		return [
			['value' => '1', 'label' => __('Caixa/Pacote')],
			['value' => '2', 'label' => __('Rolo/Prisma')],
			['value' => '3', 'label' => __('Envelope')]
		];
	}
}