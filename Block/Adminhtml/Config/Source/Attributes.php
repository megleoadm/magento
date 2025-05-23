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

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class Attributes implements \Magento\Framework\Option\ArrayInterface {

	private $attributeFactory;

	public function __construct(Attribute $attributeFactory) {
		$this->attributeFactory = $attributeFactory;
	}

	public function toOptionArray() {
		$attributes = $this->attributeFactory->getCollection();

		$options = [];
		$options[] = ['value' => '', 'label' => 'Selecione'];

		foreach ($attributes as $attribute) {
			$front = $attribute->getFrontendLabel();

			if (!empty($front)) {
				$options[] = ['value' => $attribute->getAttributecode(), 'label' => $attribute->getAttributecode()];
			} else {
				$options[] = ['value' => $attribute->getAttributecode(), 'label' => $attribute->getAttributecode()];
			}
		}

		sort($options);

		return $options;
	}
}