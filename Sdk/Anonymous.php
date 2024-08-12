<?php

namespace Megleo\Delivery\Sdk;

use stdClass;
use Exception;

class Anonymous extends stdClass
{
    /**
     * Chama um método passando parâmetros
     *
     * @param string $methodName
     * @param array $params
     */
    public function __call($methodName, $params)
    {
        if (!isset($this->{$methodName})) {
            throw new Exception('Call to undefined method ' . __CLASS__ . '::' . $methodName . '()');
        }

        return $this->{$methodName}->__invoke(...$params);
    }
}
