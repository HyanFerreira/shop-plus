# Compras e integridade de estoque

## Fornecedores fictícios

Somente administradores gerenciam fornecedores. Nome, documento, e-mail, telefone e endereço comercial são cifrados com a criptografia autenticada do Laravel. O documento sintético normalizado usa o mesmo mecanismo de índice cego documentado para dados pessoais, permitindo unicidade sem texto pesquisável no banco.

Nenhum dado real deve ser cadastrado. Use domínios reservados como `example.test`, nomes explicitamente fictícios e endereços de demonstração.

## Ordens de compra

- Custos são obtidos do vínculo fornecedor/produto no servidor e congelados no item.
- Totais são recalculados com centavos inteiros; o navegador não informa o total confiável.
- Somente rascunhos podem ser editados ou emitidos.
- Ordens recebidas ou canceladas não retornam a rascunho.
- Números públicos são aleatórios e não revelam contagens internas.

## Recebimento

O recebimento ocorre em transação MySQL e bloqueia ordem, itens e saldos relevantes. A quantidade acumulada nunca pode superar a quantidade comprada. Recebimentos parciais atualizam o estado da ordem; o último recebimento marca a ordem como recebida.

Cada movimento recebe chave idempotente determinística por recebimento/item. Repetir a mesma solicitação não duplica saldo nem movimento.

## Livro-razão e projeção

`inventory_items` é a projeção rápida:

- `on_hand`: quantidade física;
- `reserved`: quantidade comprometida com vendas;
- disponível: `on_hand - reserved`.

`stock_movements` é o livro-razão. O modelo bloqueia atualização e exclusão; correções são novos movimentos compensatórios. Chaves idempotentes são únicas.

Ajustes manuais exigem administrador, justificativa, quantidade diferente de zero e uma chave idempotente. Um ajuste não pode tornar o saldo negativo nem inferior às reservas existentes.

## Próxima integração

Carrinho não reserva estoque. O checkout fará reserva transacional, e pagamento/cancelamento produzirão movimentos adicionais sem editar o histórico existente.
