# Plano de implementação: pagamento simulado e ciclo do pedido

## Objetivo

Processar cartões exclusivamente fictícios, concluir ou liberar reservas e registrar transições auditáveis do pedido.

## Persistência

- Criar `payments` com valor, estado, token aleatório, bandeira, últimos quatro dígitos, autorização fictícia e chave idempotente.
- Nunca criar colunas para PAN completo, CVV ou nome impresso.
- Manter pagamentos e históricos vinculados ao pedido e escopados ao proprietário.

## Regras

- Validar formato, Luhn, validade e CVV somente em memória.
- Usar números de teste documentados para aprovação e recusa previsíveis.
- Aprovação converte a reserva em baixa física dentro de transação com bloqueio.
- Recusa ou cancelamento libera a reserva de forma idempotente.
- Cada mudança cria movimento de estoque e histórico do pedido imutáveis.
- Impedir pagamento de pedido que não esteja pendente.

## Interface e segurança

- Formulário Livewire privado e sem cache no detalhe do pedido.
- Limite de tentativas por usuário e pedido.
- Mensagens e exceções nunca incluem PAN ou CVV.
- Limpar os campos sensíveis imediatamente após processamento.

## Verificação

- Aprovação, recusa, repetição idempotente e acesso indevido.
- Ausência de PAN e CVV persistidos.
- Conversão e liberação correta das reservas.
- Suite completa, build e auditorias.
