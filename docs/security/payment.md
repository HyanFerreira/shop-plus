# Segurança do pagamento simulado

## Cartões exclusivamente fictícios

O simulador aceita apenas números de teste explicitamente documentados na interface. Eles passam pelo algoritmo de Luhn e produzem respostas determinísticas de aprovação ou recusa. Não use dados financeiros reais.

O número completo, o nome informado e o CVV existem somente no estado transitório da requisição Livewire. Eles não são enviados a modelos, banco, histórico, sessão, logs ou exceções. O componente limpa esses campos em um bloco `finally`, inclusive quando validação ou processamento falham.

## Dados persistidos

O registro de pagamento contém apenas:

- valor em centavos;
- estado;
- token e chave idempotente aleatórios;
- bandeira e últimos quatro dígitos;
- autorização fictícia quando aprovada;
- instante de processamento.

Não existem colunas para PAN completo, CVV ou nome do portador.

## Integridade do pedido e estoque

O processamento bloqueia pedido e estoque em transação MySQL. Aprovação reduz simultaneamente o saldo físico e a reserva; recusa libera somente a reserva e cancela o pedido. Cada resultado cria histórico do pedido e movimento imutável com chave idempotente.

Somente o proprietário pode pagar ou cancelar um pedido pendente. Tentativas de pagamento são limitadas por usuário e pedido, e repetições com a mesma chave não duplicam cobrança simulada nem movimento.
