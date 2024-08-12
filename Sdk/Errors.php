<?php

declare(strict_types=1);

namespace Megleo\Delivery\Sdk;

class Errors
{
	/**
	 * Lista de erros possíveis.
	 *
	 * @var array
	 */
	private static $errors = [
		'999' => 'Erro inesperado.',
		'001' => 'Falha na conexão com a Megleo. Por favor, tente mais tarde.',
		'002' => 'País de origem/destino deve ser Brasil.',
		'003' => 'Código Postal da Loja está incorreto.',
		'004' => 'Dimensões não encontradas para o produto %s.',
		'005' => 'O CPF informado é inválido.',
		'006' => 'O CNPJ informado é inválido.',
		'401' => 'Falha ao se conectar com a Megleo.',
		'402' => 'Não foi possível identificar o CPF nem CNPJ do destinatário.',
	];

	/**
	 * Obtém a mensagem do erro.
	 *
	 * @param string $code
	 */
	public static function getMessage($code)
	{
		if (array_key_exists($code, self::$errors)) {
			return self::$errors[$code];
		}

		return self::$errors['999'];
	}
}
