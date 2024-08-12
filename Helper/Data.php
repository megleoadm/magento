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

namespace Megleo\Delivery\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

	public function __construct(Context $context) {
		parent::__construct($context);
	}
}