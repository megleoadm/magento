<?php

namespace Megleo\Delivery\Sdk\Endpoints;

use Megleo\Delivery\Sdk\Routers;

class SimularValor extends Endpoint
{
    private $products = [];
    private $cepOrigem;
    private $cepDestino;
    private $cpfDestino;
    private $cnpjDestino;
    private $valorNota;

    /**
     * Coleta os dados e envia uma requisição.
     */
    public function valores()
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
            'cnpj_destinatario' => $this->cnpjDestino,
            'valor_nota_fiscal' => $this->valorNota,
            'correlacao_carga' => 'magento',
            'volume' => $volumes,
            'tipos_carga' => $skus
        ];

        $response = $this->client->request(
            self::GET,
            Routers::simular()->valores(),
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

    public function withCpfDestino($cpfDestino)
    {
        $this->cpfDestino = $cpfDestino;

        return $this;
    }

    public function withCnpjDestino($cnpjDestino)
    {
        $this->cnpjDestino = $cnpjDestino ?? '';

        return $this;
    }

    public function withValorNota($ValorNota)
    {
        $this->valorNota = $ValorNota;

        return $this;
    }
}
