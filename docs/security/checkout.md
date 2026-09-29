# Segurança do carrinho e checkout

## Limites de confiança

O navegador informa somente identificadores, quantidades e escolhas. Preço, disponibilidade, peso, frete e total são sempre consultados e recalculados no servidor. O carrinho não persiste preços.

Carrinho, endereços e pedidos são escopados ao usuário autenticado. As telas de checkout e pedido usam `Cache-Control: no-store`, e tentativas de confirmação possuem limite por usuário.

## Reserva e idempotência

A confirmação bloqueia carrinho, itens e saldos de estoque em uma transação MySQL. O pedido somente é criado se todos os itens continuarem ativos e disponíveis. Cada reserva incrementa `reserved` e cria um movimento imutável `sale_reserved`.

Uma chave UUID exclusiva identifica o checkout. Repetir a confirmação retorna o mesmo pedido, sem duplicar reserva, movimento ou total.

## Histórico e dados pessoais

Itens do pedido congelam nome, SKU, preço e quantidade. Assim, mudanças no catálogo não alteram o histórico comercial.

O endereço selecionado é copiado para um snapshot cifrado com a criptografia autenticada do Laravel. Alterar ou apagar o endereço da agenda não modifica o pedido. O snapshot não aparece na serialização comum do modelo.

## Cartão

Este incremento não recebe nem persiste dados de cartão. A simulação de pagamento será adicionada separadamente; número completo e CVV deverão existir somente durante a requisição e ser descartados antes de persistência, logs ou exceções.
