<?php

namespace Megleo\Delivery\Sdk\Endpoints;

use Megleo\Delivery\Sdk\Client;

abstract class Endpoint
{
    /**
     * @var string
     */
    const POST = 'POST';
    /**
     * @var string
     */
    const GET = 'GET';
    /**
     * @var string
     */
    const PUT = 'PUT';
    /**
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
