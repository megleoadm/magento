<?php

namespace Megleo\Delivery\Sdk;

class Routers
{
    /**
     * Retorna url para criar pedido parcial
     */
    public static function criarPedido()
    {
        $anonymous = new Anonymous();
        $anonymous->criar = static function () {
            return 'pedidos/criar';
        };

        return $anonymous;
    }

    public static function criarPedidoSimplificado()
    {
        $anonymous = new Anonymous();
        $anonymous->criar = static function () {
            return 'pedidos/criar_simplificado';
        };

        return $anonymous;
    }

    /**
     * Retorna url para definir transportadora em pedido parcial
     */
    public static function definirTransportadora()
    {
        $anonymous = new Anonymous();
        $anonymous->definir = static function () {
            return 'pedidos/definir_apenas_transportadora';
        };

        return $anonymous;
    }

    /**
     * Retorna url para simular cotação
     */
    public static function simular()
    {
        $anonymous = new Anonymous();
        $anonymous->valores = static function () {
            return 'pedidos/simular_valores';
        };

        return $anonymous;
    }
}
