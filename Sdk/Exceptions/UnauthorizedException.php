<?php

namespace Megleo\Delivery\Sdk\Exceptions;

use Exception;

class UnauthorizedException extends Exception
{
    /**
     * @var string
     */
    private $msg;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $msg
     * @param string $date
     * @param string $path
     */
    public function __construct($msg, $date, $path)
    {
        $this->msg = $msg;
        $this->date = $date;
        $this->path = $path;

        $exceptionMessage = $this->buildExceptionMessage();

        parent::__construct($exceptionMessage);
    }

    /**
     * @return string
     */
    private function buildExceptionMessage()
    {
        return sprintf(
            'ERROR Message: %s. DATE: %s. PATH: %s',
            $this->msg,
            $this->date,
            $this->path
        );
    }
}
