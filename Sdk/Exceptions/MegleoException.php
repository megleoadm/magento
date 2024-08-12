<?php

namespace Megleo\Delivery\Sdk\Exceptions;

use Exception;

class MegleoException extends Exception
{
    /**
     * @var string
     */
    private $error;

    /**
     * @param string $error
     */
    public function __construct($error)
    {
        $this->error = $error;
        $exceptionMessage = $this->buildExceptionMessage();

        parent::__construct($exceptionMessage);
    }

    /**
     * @return string
     */
    private function buildExceptionMessage()
    {
        return sprintf('ERROR Message: %s.', $this->error);
    }
}
