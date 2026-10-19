<?php

namespace Megleo\Delivery\Sdk;

use GuzzleHttp\Exception\BadResponseException;
use Megleo\Delivery\Sdk\Exceptions\MegleoException;
use Megleo\Delivery\Sdk\Exceptions\InvalidJsonException;
use Megleo\Delivery\Sdk\Exceptions\UnauthorizedException;

class ResponseHandler
{
    /**
     * @param string $payload
     *
     * @throws InvalidJsonException
     * @return \ArrayObject
     */
    public static function success($payload)
    {
        return self::toJson($payload);
    }

    /**
     * @param BadResponseException $originalException
     *
     * @throws PagarMeException
     * @return void
     */
    public static function failure(\Exception $originalException)
    {
        throw self::parseException($originalException);
    }

    /**
     * @param \Exception $guzzleException
     *
     * @return MegleoException
     */
    private static function parseException(\Exception $guzzleException)
    {
        // Timeout / falha de conexão (ConnectException) não têm resposta HTTP
        $response = method_exists($guzzleException, 'getResponse') ? $guzzleException->getResponse() : null;

        if (is_null($response)) {
            return new MegleoException($guzzleException->getMessage());
        }

        if ($response->getStatusCode() == 401) {
            return new UnauthorizedException('401 Unauthorized', date("d/m/Y"), '/v1/autentica/cartaopostagem');
        }

        $body = $response->getBody()->getContents();

        $jsonError = null;

        try {
            $jsonError = self::toJson($body);
        } catch (InvalidJsonException $invalidJson) {
            return new MegleoException('HTTP ' . $response->getStatusCode() . ': ' . $body);
        }

        $error = $jsonError->error ?? $jsonError->message ?? $jsonError->errors ?? $body;
        if (!is_scalar($error)) {
            $error = json_encode($error, JSON_UNESCAPED_UNICODE);
        }

        return new MegleoException('HTTP ' . $response->getStatusCode() . ': ' . $error);
    }

    /**
     * @param string $json
     * @return \ArrayObject
     */
    private static function toJson($json)
    {
        $result = json_decode($json);

        if (json_last_error() != \JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg());
        }

        return $result;
    }
}
