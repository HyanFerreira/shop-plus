# Segurança da entrega simulada

Uma remessa é criada somente após autorização do pagamento fictício e permanece vinculada de forma única ao pedido. Modalidade, valor e prazo são copiados dos cálculos já confirmados pelo servidor; o cliente não fornece esses valores.

## Máquina de estados

As transições permitidas são explícitas. Estados terminais não aceitam novas alterações, saltos inválidos são rejeitados e somente administradores podem operar a entrega. As mudanças relevantes sincronizam os estados `processing`, `shipped` e `delivered` do pedido.

Cada transição gera um evento. O modelo impede atualização ou exclusão desses eventos; correções exigem um novo evento válido. O ator administrativo e uma observação limitada a 500 caracteres são registrados. A interface escapa a observação para impedir XSS persistente.

## Isolamento e privacidade

Clientes veem apenas pedidos e rastreios próprios. Listagem e detalhe usam `Cache-Control: no-store`. O código de rastreio é aleatório e fictício, e nenhum serviço externo recebe o endereço cifrado do pedido.
