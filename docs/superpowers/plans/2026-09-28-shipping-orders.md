# Plano de implementação: entrega e acompanhamento de pedidos

## Objetivo

Completar o fluxo acadêmico de venda com remessa simulada, histórico imutável, acompanhamento do cliente e operação administrativa.

## Persistência

- Criar uma remessa por pedido pago com modalidade, valor, prazo, estado e rastreio aleatório fictício.
- Criar eventos imutáveis para todas as transições de entrega.
- Manter o endereço somente no snapshot cifrado já pertencente ao pedido.

## Regras

- Criar a remessa na mesma transação que autoriza o pagamento.
- Permitir somente transições explícitas de estado.
- Sincronizar `processing`, `shipped` e `delivered` no pedido.
- Exigir administrador para alterações e registrar ator e observação sanitizada.
- Clientes consultam apenas seus pedidos e eventos.

## Interface e verificação

- Lista de pedidos do cliente e rastreio na página privada do pedido.
- Painel administrativo de pedidos e entregas.
- Testes de autorização, transições, imutabilidade e isolamento.
- Suite completa, build, migrations e auditorias.
