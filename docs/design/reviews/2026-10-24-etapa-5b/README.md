# ShopPlus — Etapa 5B: checkout e pagamento fictício

Implementação limitada ao checkout e ao formulário de pagamento já existente na página privada do pedido. Referências: ecommerce-ui-reference.png, design-system.md, relatórios/capturas anteriores, carrinho, componentes e ações/testes de checkout/pagamento. Sem dependências, .env, banco de desenvolvimento, modelos ou ações de negócio alterados.

## Commits anteriores e base de trabalho

Por solicitação explícita, o trabalho concluído foi organizado em sete commits locais na main, criada a partir da antiga branch setup/laravel-bootstrap:

| Commit | Escopo |
| --- | --- |
| d3a1f59 | Fundação e estrutura compartilhada |
| c5b102b | Home e assets fictícios locais |
| 5fc0096 | Catálogo e filtros |
| d4e6413 | Detalhes e controles de compra |
| da75d1f | Carrinho |
| e7d77e4 | Testes de apresentação/compatibilidade |
| d9b63f9 | Contratos, planos e evidências visuais |

A árvore estava limpa nessa base. Não houve push. As alterações novas da5B permanecem sem commit.

## Arquivos desta etapa

| Arquivo | Alteração |
| --- | --- |
| app/Livewire/CheckoutManager.php | Somente render: eager loading de imagens e variáveis de exibição lineTotals/selectedOption/estimatedTotal. |
| resources/views/checkout/show.blade.php | Título/etapa/container alinhados ao storefront, mantendo layout autenticado. |
| resources/views/livewire/checkout-manager.blade.php | Produtos com imagens, seleção de endereço, modalidades, estimativa, loading/erros e vazio. |
| resources/views/livewire/payment-form.blade.php | Aviso acadêmico, formulário/erros por campo, contexto do pedido, loading e cancelamento existente. |
| resources/views/orders/show.blade.php | Integração condicionada a pending_payment: pagamento antes dos detalhes e largura storefront. Demais blocos/estados preservados. |
| resources/css/checkout.css | Estilos checkout-/payment-, responsividade e foco. |
| resources/css/app.css | Importação de checkout.css. |
| tests/Feature/Checkout/CheckoutPresentationTest.php | Quatro testes de estimativas, estados, criação/reserva e rótulos duplicados. |
| tests/Feature/Payment/PaymentPresentationTest.php | Dois testes de integração, acesso e validação/limpeza. |
| docs/design/design-system.md | Contrato efetivamente implementado. |
| docs/superpowers/plans/2026-10-24-checkout-payment-refinement.md | Decisões e validações. |
| docs/design/reviews/2026-10-24-etapa-5b/ | Relatório, medições e capturas fictícias. |

## Resultado visual e reutilização

- Checkout em duas colunas a partir de1024px: coluna principal e resumo320px; abaixo disso, sequência vertical. Container externo1120px, sem sticky/overlay.
- Cards brancos, bordas/raios/sombras e azul da fundação. Título32/40px; nomes dos itens16/24px, quantidades14/20px e valores18/26px. Campos com pelo menos44px; botão do pagamento48px.
- Imagens: primeiro arquivo cadastrado disponível, asset local do SKU conhecido, indisponibilidade. Contain e fallback para erro de carregamento inclusive anterior ao Alpine.
- Reutilizados x-app-layout, header/footer, x-input, x-label, x-button, x-alert, x-badge, x-input-error e x-icon. Nenhuma biblioteca ou componente novo.
- Endereço conserva select nativo e seleção Livewire. Somente rótulo é mostrado, sem CEP/rua/destinatário adicionais. Rótulos repetidos recebem ordinal e orientação para revisar/renomear; link Gerenciar endereços disponível. Não há regra nova de unicidade ou bloqueio comercial.
- Modalidades usam radios, nomes/prazos/preços recebidos do ShippingCalculator. Seleção visual clara e foco preservado após atualizar o servidor.
- Pagamento apresenta pedido criado, total persistido, aviso acadêmico permanente, números de teste públicos identificados e campos fictícios. Validade em uma coluna no mobile, duas a partir de640px; contexto ao lado em1024px.
- O pagamento foi colocado antes dos detalhes já exibidos apenas no estado pending_payment. Ajustes de quebra de nome/moeda nos detalhes são escopados a essa condição. Lista geral, acompanhamento, rastreio e demais estados não foram redesenhados.

## Fluxos, valores e segurança preservados

1. confirm continua validando, criando pedido e reservando estoque por PlaceOrder, com a mesma chave idempotente e redirecionamento.
2. pay continua sendo uma ação posterior, no pedido criado. ProcessPayment, estado/autorização/idempotência e movimentações de estoque não foram modificados.
3. cancel conserva wire:confirm e CancelOrder; não há transição nova.
4. O subtotal original e o cálculo de peso/opções de entrega continuam no render. O total por linha antes multiplicado no Blade foi levado para variáveis servidor; a estimativa soma subtotal e preço da opção no servidor. Blade apenas formata. Nenhum cálculo financeiro em JavaScript. PlaceOrder recalcula/valida o total definitivo.
5. shippingMethod recebeu .live para que a estimativa reflita a escolha pelo servidor. addressId e valores das modalidades permanecem iguais; select/radios não se desabilitam na própria consulta, preservando foco. O botão confirm desabilita durante consultas/criação; os seletores também desabilitam durante confirm.
6. PaymentForm PHP permanece intacto: mesmos campos, locks, finally que limpa nome/número/CVV, limites, validação e autorização. Inputs usam autocomplete off; CVV continua password. Pay/cancel desabilitam todos os campos e ambos os botões durante operação.
7. Erros de cardExpiry e order, antes sem saída própria no template, agora são visíveis. Mensagens não incluem os valores digitados. Não foram adicionados logs, analytics, persistência de cartões ou integração financeira.
8. Hashes comprovaram preservação de Home/Catálogo/Produto/Carrinho, componentes compartilhados, layout app, PaymentForm, PlaceOrder, CancelOrder, ProcessPayment, ShippingCalculator, FictitiousCardValidator e rotas.

## Build, testes e revisão

- npm.cmd run build: aprovado; assets gerados pelo Vite.
- CheckoutPresentationTest + PaymentPresentationTest + PaymentInterfaceTest: **10 testes,70 assertions, aprovados**.
- Suíte completa em SQLite em memória: **208 testes,988 assertions;198 aprovados,7 ignorados,3 falhas**.
- Comparação com JUnit da5A: nenhuma falha nova. As três anteriores são:
  - AdminAccessTest::test_administrator_navigation_shows_the_administration_link — link administrativo no dashboard.
  - ExampleTest::test_the_application_returns_a_successful_response — categories ausente no contexto do teste.
  - SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers — mesma ausência de categories.
- git diff --check: sem erros.
- Revisão independente encontrou rótulos duplicados indistinguíveis; teste RED confirmou e GREEN passou após ordinal/aviso/link de revisão, sem dados privados adicionais.
- A revisão sugeriu verificar foco dos controles .live. Browser reproduziu foco indo para BODY após atualizar radio desabilitado pela própria requisição; depois do ajuste, o foco permanece no radio. A seleção de endereço também preserva foco após atualização.
- Build e suíte foram repetidos depois das correções; CSS final foi novamente validado no navegador.

## Navegador e estado real do banco isolado

Chrome headless, rotas reais Laravel/Livewire, login e ações existentes. SQLite/disco/sessões temporários e dados exclusivamente fictícios. Nenhum seeder ou operação foi executado no banco de desenvolvimento. Viewports CSS, DPR1, zoom conhecido100%, altura1000px; largura útil pode ser15px menor pela rolagem. Não se inferiu zoom da referência oficial.

Matriz final: checkout preenchido, vazio, sem endereço, estoque insuficiente e pagamento pending_payment em320/375/768/1024/1280/1440px: **30 combinações**. Sem overflow horizontal; um header/main/footer nas páginas alvo; controles dentro da viewport e >=44px; imagem contain. Resumo/contexto após o conteúdo no mobile e à direita no desktop.

[Validação do navegador](validation.json) · [Conferência de dados/estoque](database-validation.json).

Verificações funcionais:

- Visitantes redirecionados ao login em checkout/pagamento. Outro usuário autenticado recebe404 no pedido alheio.
- Dois endereços próprios, modalidades econômica/expressa/retirada e respectivas estimativas, incluindo mudança de região. Navegação por setas nos radios e preservação de foco após atualizar.
- Remover seleção de endereço retira confirm e deixa total A definir; sem endereço oferece cadastro real.
- Pedido criado com total804590 centavos e frete1890 centavos, de acordo com as opções servidor. Mantido pending_payment com reserva de1 telefone e2 fones, ainda sem pagamento.
- Estoque insuficiente retorna mensagem real sem criar outro pedido.
- Request de criação pausado: confirmação e seletores desabilitados. Pagamento pausado: inputs, submit e cancelamento desabilitados.
- CVV inválido, número fictício inválido e validade expirada geraram erros, com nome/número/CVV limpos depois. Só foram usados números públicos fictícios.
- Aprovação: pedido paid, estoque5→3 e reserva2→0; pagamento exibe somente final1111.
- Recusa: pedido cancelled, estoque5 preservado e reserva2→0; pagamento exibe somente final0002.
- Cancelamento: pedido cancelled, estoque5 preservado e reserva2→0.
- Nenhum número completo de cartão nos registros payments; não existem colunas card_number/cvv. As ações responsáveis permanecem inalteradas.

A automação inicial ficou bloqueada na confirmação nativa de cancelamento. A continuação confirmou estados persistidos, repetiu a matriz final e executou o cancelamento respondendo à confirmação via window.confirm simulado no navegador de teste. O atributo wire:confirm original foi mantido e conferido. **A aparência/operação manual do diálogo nativo não foi validada.** Não foi alterado código do produto para contornar o diálogo.

## Capturas

- [Checkout desktop — topo](checkout-top-1440.png) e [página completa](checkout-checkout-1440.png).
- [Checkout mobile — topo](checkout-top-375.png) e [página completa](checkout-checkout-375.png).
- Checkout em [320px](checkout-checkout-320.png), [768px](checkout-checkout-768.png), [1024px](checkout-checkout-1024.png) e [1280px](checkout-checkout-1280.png).
- [Pagamento desktop — topo](payment-top-1440.png) e [página completa](payment-pending-1440.png).
- [Pagamento mobile — topo](payment-top-375.png) e [página completa](payment-pending-375.png).
- Pagamento em [320px](payment-pending-320.png), [768px](payment-pending-768.png), [1024px](payment-pending-1024.png) e [1280px](payment-pending-1280.png).
- [Checkout vazio desktop](checkout-empty-1440.png) / [mobile](checkout-empty-375.png).
- [Sem endereço desktop](checkout-noaddress-1440.png) / [mobile](checkout-noaddress-375.png).
- Evidências funcionais: [entrega expressa](checkout-express-desktop.png), [estoque insuficiente](checkout-stock-error-desktop.png), [pedido criado](checkout-order-created-desktop.png), [validação de pagamento](payment-validation-mobile.png), [aprovado desktop](payment-approved-desktop.png) / [mobile](payment-approved-mobile.png), [recusado](payment-declined-mobile.png) e [cancelado](payment-cancelled-mobile.png).

As capturas de fluxo anteriores às últimas correções de foco/rótulo mostram os resultados reais daquele percurso; as matrizes principais checkout-checkout e payment-pending foram repetidas com o CSS final. Os valores diferem entre pedidos porque os dados fictícios de cada cenário são diferentes.

## Diferenças e pendências

O mosaico oficial orienta identidade, densidade e composição; não possui uma tela específica detalhada de checkout/pagamento. A implementação usa o acabamento das etapas aprovadas, sem copiar condições comerciais ou inventar uma transação única. Ilustrações locais continuam diferentes de fotografias.

O total do checkout é explicitamente estimado até confirmação; o pagamento usa o total persistido. Aviso acadêmico e cancelamento permanecem visíveis. Nomes iguais de endereços devem ser revisados pelo usuário no cadastro; não se expõem dados privados adicionais para distinguir destinos.

Não foram validados dispositivos físicos, Safari/Firefox, leitores de tela, conformidade WCAG integral ou interação manual com o diálogo nativo. Áreas gerais de pedidos/rastreio continuam com a apresentação anterior, inclusive rótulos internos de status; não foram refatoradas. As três falhas anteriores da suíte ficam pendentes. Nenhuma nova integração de pagamento, persistência de cartão, alteração de reserva ou regra comercial foi introduzida.
