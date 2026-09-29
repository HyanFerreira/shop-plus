# Plano de implementação: carrinho, frete e checkout

## Objetivo

Entregar o quinto incremento da arquitetura: carrinho por usuário, cálculo interno de frete e criação idempotente de pedido pendente com reserva de estoque.

## Persistência

- Criar `carts` com no máximo um carrinho ativo por usuário e `cart_items` únicos por produto.
- Criar `orders`, `order_items` e `order_status_histories` com valores inteiros em centavos.
- Congelar nome, SKU e preço dos produtos no pedido.
- Cifrar o snapshot completo do endereço de entrega.
- Registrar modalidade, preço e prazo da entrega escolhida no pedido.

## Regras de domínio

- Recalcular preço e disponibilidade exclusivamente no servidor.
- Impedir produtos inativos, excluídos ou sem estoque disponível no carrinho e no checkout.
- Calcular frete por CEP, peso e modalidade com regras internas determinísticas.
- Criar pedido e reservar estoque em uma única transação, usando bloqueio pessimista.
- Usar chave idempotente por checkout e movimentos de estoque derivados dela.
- Não armazenar dados de cartão neste incremento.

## Interface e segurança

- Exigir autenticação e conta ativa em carrinho e checkout.
- Escopar todas as consultas ao usuário autenticado.
- Aplicar `Cache-Control: no-store` ao checkout e aos dados do pedido.
- Validar novamente endereço, catálogo, preços, quantidades e estoque ao confirmar.
- Oferecer carrinho e checkout em Livewire, sem confiar em valores do navegador.

## Verificação

- Testes de persistência e isolamento do carrinho.
- Testes de cálculo de frete.
- Testes de checkout, snapshots, reserva, concorrência lógica e idempotência.
- Testes dos componentes Livewire e das rotas privadas.
- Suite completa, Pint, build e auditorias de dependências.
