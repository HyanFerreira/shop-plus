# ShopPlus — Etapa 5B: checkout e pagamento simulado

Requisitos no anexo da Etapa5B. Trabalho anterior foi separado em sete commits na main por solicitação explícita; base d9b63f9. Alterações novas desta etapa ficam sem commit. Sem dependências, .env, banco de desenvolvimento, modelos ou ações de negócio.

## Decisões

- Checkout em container1120px, cards de produtos/endereço/entrega na coluna principal e resumo de320px à direita em1024px; sequência vertical abaixo disso. Manter layout autenticado/header/footer e evitar sticky.
- Imagens: primeira cadastrada disponível, demo do SKU conhecido, fallback. Adicionar apenas eager loading de imagens em CheckoutManager.render para evitar N+1.
- Preparar valores de exibição no render servidor: totais por linha (antes calculados no Blade), opção selecionada e total estimado. Preservar subtotal, cálculo de peso/frete, confirm, chave idempotente e PlaceOrder. Total definitivo continua recalculado na ação. Nenhum cálculo financeiro no JavaScript ou Blade.
- Select de endereço mantém addressId e vínculo autenticado; apresentar somente label, sem dados privados adicionais. Radio shippingMethod recebe .live para atualizar estimativa pelo servidor ao trocar modalidade. Mesmos valores/opções/validação.
- Pagamento continua somente no pedido pending_payment. Refinar payment-form e integrar antes dos detalhes do pedido nessa condição; demais itens/entrega/rastreio da página não serão refatorados. Flash existente de resultado continua visível.
- Formulário preserva pay/cancel, wire:confirm, bindings, IDs, autocomplete off, dados fictícios e limpeza de estado sensível. Usar x-label/input/button/error/alert, aviso acadêmico permanente, total persistido, erros por campo e loading/disabled com alvos explícitos.

## Tarefas

- [x] RED: testes de valores servidor/modalidades/endereço, imagens, vazio/sem endereço, criação/reserva/idempotência e markup do pagamento/erros/owner/limpeza.
- [x] GREEN: templates, CSS checkout/payment e somente dados de apresentação em CheckoutManager.render.
- [x] Build e testes existentes completos em SQLite isolado; comparar202testes935assertions,192pass7skip3falhas da5A.
- [x] Browser real em320/375/768/1024/1280/1440px: checkout/pagamento, vazio/sem endereço, opções/valores, criação/reserva, validação/loading, aprovação/recusa/cancelamento, controle de acesso e capturas só fictícias.
- [x] Revisão independente e hashes; relatório com diferenças, limites e pendências fora do escopo.


Revisão final: rótulos duplicados e perda de foco durante quote foram reproduzidos e corrigidos somente na apresentação. Preservado select nativo; Gerenciar endereços e orientação para nomes repetidos. Rádio/select não se desabilitam ao consultar sua própria estimativa, apenas em confirm. Build e10testes70assertions passaram; suíte208/988 mantém3falhas7skips. Matriz30combinações final aprovada. Cancelamento validado com confirmação simulada no headless, diálogo nativo visual não validado. Evidências em docs/design/reviews/2026-10-24-etapa-5b/README.md.
