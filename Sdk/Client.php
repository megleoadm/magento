<?php

namespace Megleo\Delivery\Sdk;

use Megleo\Delivery\Sdk\Endpoints\SimularValor;
use Megleo\Delivery\Sdk\Exceptions\InvalidJsonException;
use Megleo\Delivery\Sdk\Exceptions\MegleoException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Megleo\Delivery\Sdk\Endpoints\CriarPedidoParcial;
use Megleo\Delivery\Sdk\Endpoints\DefinirTransportadora;

class Client
{
    /**
     * @var string
     */ 
    protected const BASE_URI = 'https://app.megleo.com.br/api/v1/';

    /**
     * @var \GuzzleHttp\Client
     */
    private $http;

    /**
     * @var string
     */
    private $token;

    /**
     * @var SimularValor
     */
    private $simularValor;

    /**
     * @var CriarPedidoParcial
     */
    private $criarPedidoParcial;

    /**
     * @var DefinirTransportadora
     */
    private $definirTransportadora;

    /**
     * @param string $token
     * @param ?array $extras
     */
    public function __construct(string $token, ?array $extras = null)
    {
        $options = ['base_uri' => self::BASE_URI];

        if ($extras !== null) {
            $options = array_merge($options, $extras);
        }

        $this->http = new HttpClient($options);
        $this->simularValor = new SimularValor($this);
        $this->criarPedidoParcial = new CriarPedidoParcial($this);
        $this->definirTransportadora = new DefinirTransportadora($this);

        $this->setToken(trim($token));
    }

    /**
     * Realiza o request http.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @throws MegleoException
     * @return \ArrayObject
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    public function request($method, $uri, $options = [])
    {
        $response = null;

        try {
            $response = $this->http->request($method, $uri, $options);
            return ResponseHandler::success($response->getBody());
        } catch (InvalidJsonException $exception) {
            throw $exception;
        } catch (RequestException $exception) {
            ResponseHandler::failure($exception);
        } catch (ClientException $exception) {
            ResponseHandler::failure($exception);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken(string $token)
    {
        return $this->token = $token;
    }

    /**
     * @return SimularValor
     */
    public function simularValor()
    {
        return $this->simularValor;
    }

    /**
     * @return CriarPedidoParcial
     */
    public function criarPedidoParcial()
    {
        return $this->criarPedidoParcial;
    }

    /**
     * @return DefinirTransportadora
     */
    public function definirTransportadora()
    {
        return $this->definirTransportadora;
    }
}
