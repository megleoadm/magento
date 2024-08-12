<?php

namespace Megleo\Delivery\Sdk\Endpoints;

use Megleo\Delivery\Sdk\Routers;

class CriarPedidoParcial extends Endpoint
{
    private $products = [];
    private $cepOrigem;
    private $cepDestino;

    private $valorNota;
    private $orderId;

    /**
     * Coleta os dados e envia uma requisição.
     */
    public function criar()
    {
        $volumes = [];
        $skus = [];

        foreach ($this->products as $product) {
            $skus[] = $product['sku'];
            $volumes[] = [
                'quantidade' => $product['qty'],
                'peso' => $product['weight'],
                'comprimento' => $product['length'],
                'largura' => $product['width'],
                'altura' => $product['height'],
            ];
        }

        $data = [
            'cep_origem' => $this->cepOrigem,
            'cep_destino' => $this->cepDestino,
            'valor_nota_fiscal' => $this->valorNota,
            'chave_rastreio_embarcadora' => 'magento ' . $this->orderId,
            'volume' => $volumes,
            'correlacao_carga' => 'magento',
            'tipos_carga' => $skus,
        ];

        $response = $this->client->request(
            self::POST,
            Routers::criarPedido()->criar(),
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

    public function withProduct($product)
    {
        array_push($this->products, $product);

        return $this;
    }

    public function withCepOrigem($cepOrigem)
    {
        $this->cepOrigem = $cepOrigem;

        return $this;
    }

    public function withCepDestino($cepDestino)
    {
        $this->cepDestino = $cepDestino;

        return $this;
    }

    public function withValorNota($valorNota)
    {
        $this->valorNota = $valorNota;

        return $this;
    }

    public function withOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }
}
