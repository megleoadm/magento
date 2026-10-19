# Megleo Delivery para Magento 2

Módulo de frete da [Megleo](https://www.megleo.com.br) para Magento 2.

- Exibe no checkout as opções de frete das transportadoras Megleo, com preço e prazo.
- Registra automaticamente na Megleo os pedidos finalizados com frete Megleo.
- Adiciona os campos de CPF e CNPJ ao cadastro do cliente e ao endereço.

## Requisitos

- Magento 2.4.x (Open Source / Adobe Commerce / Mage-OS)
- PHP 7.4, 8.1 ou 8.2
- Composer 2
- Conta ativa na Megleo e o **Código de Acesso** da API (fornecido pela Megleo)
- Inventário (MSI) com a fonte de estoque cadastrada com CEP

## Instalação

Execute os comandos na raiz da instalação do Magento, onde fica o arquivo `bin/magento`.

### Opção 1: via Composer (recomendado)

```bash
composer require megleo/delivery:1.4.x-dev
bin/magento module:enable Megleo_Delivery
bin/magento setup:upgrade
```

Se o Composer recusar a instalação por causa de *minimum-stability*, use:

```bash
composer require "megleo/delivery:1.4.x-dev@dev"
```

### Opção 2: manual

1. Baixe o código da versão desejada, por exemplo [1.4.zip](https://github.com/megleoadm/magento/archive/refs/heads/1.4.zip).
2. Extraia o conteúdo para `app/code/Megleo/Delivery`. O arquivo `registration.php` deve ficar em `app/code/Megleo/Delivery/registration.php`.
3. Execute:

```bash
bin/magento module:enable Megleo_Delivery
bin/magento setup:upgrade
```

### Finalizando (as duas opções)

Lojas em **modo produção** precisam ainda:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy pt_BR en_US -f
```

E, em todos os casos:

```bash
bin/magento cache:flush
```

Para ver o modo atual da loja: `bin/magento deploy:mode:show`.

## Configuração

### 1. Origem dos envios

O CEP de origem é usado na cotação e no registro dos pedidos. Configure-o nos dois lugares abaixo, com o **mesmo valor**:

- **Lojas › Configuração › Vendas › Configurações de Entrega › Origem**: País **Brasil** e o **CEP** de onde as mercadorias saem.
- **Lojas › Inventário › Fontes**: a fonte de estoque usada pela loja deve ter País **Brasil** e o mesmo **CEP**.

### 2. Frete Megleo

Em **Lojas › Configuração › Vendas › Métodos de Entrega › Megleo**:

| Campo | Descrição |
|---|---|
| Habilitado | **Sim** para ativar o frete Megleo |
| Título | Nome exibido no checkout (padrão: "Megleo") |
| Código de Acesso | Token da API fornecido pela Megleo |
| URL da API (avançado) | **Deixe em branco.** Usado apenas em ambientes de teste |
| Altura, Largura e Comprimento padrão (cm) | Medidas usadas quando o produto não tem as dimensões preenchidas |
| Peso da encomenda | Unidade em que o peso dos produtos está cadastrado (kg ou gramas) |
| Exibir prazo de entrega / Mensagem do prazo / Prazo extra | Como o prazo aparece no checkout e quantos dias somar a ele |
| Taxa Extra / Valor ou Percentual | Acréscimo opcional sobre o valor do frete (valor fixo ou percentual) |
| Mapeamento de atributos | Atributos de produto que guardam peso, largura, altura e comprimento |

Depois de salvar, execute `bin/magento cache:flush`.

### 3. Produtos

Para cotações precisas, preencha nos produtos o **peso** e as **dimensões** (nos atributos escolhidos em "Mapeamento de atributos"). Produtos sem dimensões usam as medidas padrão.

### 4. CPF / CNPJ

O módulo adiciona os campos **CPF** e **CNPJ** ao cadastro do cliente e ao endereço do checkout. A Megleo precisa de um desses documentos do destinatário para registrar o pedido.

## Como verificar se está funcionando

1. No carrinho ou no checkout, informe um CEP de destino. As opções de frete Megleo devem aparecer com preço e prazo.
2. Finalize um pedido com frete Megleo e abra-o no admin, em **Vendas › Pedidos**. O histórico do pedido mostra o resultado do registro na Megleo:
   - **"Megleo: pedido registrado com sucesso."**
   - ou **"Megleo: falha ao registrar o pedido: ..."**, com o motivo.

Se precisar investigar, consulte `var/log/system.log` e `var/log/exception.log`.

## Atualização

Via Composer, troque a versão no comando. Por exemplo, para a 1.5:

```bash
composer require megleo/delivery:1.5.x-dev
```

Na instalação manual, substitua o conteúdo de `app/code/Megleo/Delivery` pelos arquivos da nova versão.

Depois, nos dois casos:

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile                              # modo produção
bin/magento setup:static-content:deploy pt_BR en_US -f    # modo produção
bin/magento cache:flush
```

Recomendamos testar a atualização em um ambiente de homologação antes de aplicá-la na loja.

## Desinstalação

```bash
bin/magento module:disable Megleo_Delivery
composer remove megleo/delivery        # ou apague app/code/Megleo/Delivery na instalação manual
bin/magento setup:upgrade
bin/magento cache:flush
```

## Versões

As versões são publicadas como branches deste repositório (`1.0` … `1.4`) e ficam disponíveis no Packagist como [`megleo/delivery`](https://packagist.org/packages/megleo/delivery), com o sufixo `.x-dev` (ex.: `1.4.x-dev`).

## Suporte

Dúvidas ou problemas: **comunicado@megleo.com.br**. Ao entrar em contato, informe:

- a versão do Magento;
- a versão do módulo;
- o número do pedido, quando for o caso.
