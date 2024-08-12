<?php

namespace Megleo\Delivery\Sdk\Endpoints;

use Megleo\Delivery\Sdk\Routers;

class DefinirTransportadora extends Endpoint
{
    /**
     * @var stdClass
     */
    private $customer;

    /**
     * @var string
     */
    private $transportadoraId;

    /**
     * @var string
     */
    private $chaveCotacao;

    /**
     * Coleta os dados e envia uma requisição.
     */
    public function definir()
    {
        $data = [
            'chave_cotacao' => $this->chaveCotacao,
            'id' => $this->transportadoraId,
            'destinatario' => [
                'tipo_pessoa' => $this->customer->tipo_pessoa,
                'cpf' => $this->customer->cpf,
                'cnpj' => $this->customer->cnpj,
                'email' => $this->customer->email,
                'nome' => $this->customer->nome,
            ],
        ];

        $response = $this->client->request(
            self::POST,
            Routers::definirTransportadora()->definir(),
            [
                'debug' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->client->getToken()
                ],
                'json' => $data
            ]
        );

        return $response;
    }

    public function withCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    public function withTransportadoraId($transportadoraId)
    {
        $this->transportadoraId = $transportadoraId;

        return $this;
    }

    public function withChaveCotacao($chaveCotacao)
    {
        $this->chaveCotacao = $chaveCotacao;

        return $this;
    }
}
