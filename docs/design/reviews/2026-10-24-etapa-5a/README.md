# ShopPlus — Etapa 5A: carrinho

Escopo exclusivo da apresentação do carrinho. Referências analisadas: ecommerce-ui-reference.png, design-system.md, relatórios/capturas das etapas anteriores, templates Home/Catálogo/Produto, card compartilhado e contratos CartManager/UpdateCartItem. O Git já continha as etapas aprovadas, preservadas. Sem commits, bibliotecas, alteração de .env ou operações no banco de desenvolvimento.

## Arquivos desta etapa

| Arquivo | Alteração |
| --- | --- |
| resources/views/cart/show.blade.php | Container storefront1120px, breadcrumb/h1 no conteúdo, sem header local adicional; mantém x-app-layout autenticado. |
| resources/views/livewire/cart-manager.blade.php | Linhas com imagem/marca/preço/quantidade, controles, remoção, resumo separado e vazio ilustrado. |
| resources/css/cart.css | Estilos cart- responsivos usando tokens existentes. |
| resources/css/app.css | Apenas importação de cart.css nesta etapa. |
| resources/views/components/icon.blade.php | Case trash aditivo, sem alterar os ícones existentes. |
| tests/Feature/Checkout/CartPresentationTest.php | Cinco testes de apresentação e compatibilidade funcional. |
| docs/design/design-system.md | Contrato efetivamente implementado do carrinho. |
| docs/superpowers/plans/2026-10-24-cart-refinement.md | Decisões e verificações da Etapa5A. |
| docs/design/reviews/2026-10-24-etapa-5a/ | Este relatório, capturas e medições. |

## Resultado e componentes reutilizados

- Desktop em duas colunas a partir de1024px: itens flexíveis e resumo de320px, com24px entre eles. Abaixo disso, resumo após a lista. Sem sticky, overlay ou redução da tipografia para preservar colunas.
- Linhas próprias no template do carrinho, sem forçar o card vertical do catálogo. Nome completo16/24px, marca/metadados14/20px, preço unitário18/26px. Campos/botões com pelo menos44px. Nomes e marcas longos quebram linha.
- Imagens80px no mobile e112px a partir de640px, com contain. Primeiro arquivo cadastrado disponível tem prioridade; depois SVG do SKU fictício conhecido; sem representação, estado textual. Erros de carregamento anteriores e posteriores à inicialização de Alpine recebem fallback.
- Reutilizados x-app-layout, header/footer existentes, x-button, x-input, x-label, x-input-error, x-alert, x-icon, ds-panel e ds-storefront-container. Vazio usa ilustração com o ícone shopping-cart existente, fundo azul suave, mensagem e CTA real para o catálogo.
- Resumo branco com borda, separadores, preços alinhados e CTA azul. Total dos produtos permanece na mesma linha, com rótulo capaz de quebrar. Entrega indicada como A definir no checkout. Nenhuma promessa de frete zero, desconto, cupom ou taxa inventada.

## Contratos preservados

- CartManager, UpdateCartItem, AddCartItem, modelos, rotas e checkout não foram modificados.
- quantities.ID continua diferido; Atualizar mantém wire:click updateItem(ID); Remover mantém removeItem(ID). Novos +/− mudam somente a quantidade local e chamam updateItem, sem nova ação PHP ou regra de estoque.
- Disabled acompanha a quantidade digitada e o limite visual min(99, estoque disponível). Atualização zero mantém a remoção já suportada. Remover tem ícone e nome acessível, sem modal.
- Todos os controles usam alvo explícito updateItem,removeItem para loading/disabled. Requisição pausada comprovou bloqueio de quantidade, atualização e remoção em todas as linhas. Uma região role=status anuncia atualização.
- Erros globais quantity/cart que a view anterior ocultava agora aparecem. Mensagens por quantities.ID e flash existentes permanecem; nenhuma regra de validação foi alterada.
- Nenhuma multiplicação, soma ou cálculo financeiro novo na view/JavaScript. Subtotal e Total dos produtos formatam o subtotal calculado por CartManager. Total por linha foi omitido porque não é fornecido pelo componente; evita duplicar cálculo comercial no template.
- Alterar quantidade/remover atualiza os valores pelo servidor. O checkout real mantém autenticação/autorização e cálculo definitivo. Visitantes continuam redirecionados ao login; não foi criado carrinho de sessão.

## Build, testes e revisão

- npm.cmd run build: aprovado; assets gerados pelo Vite.
- CartPresentationTest, CartManagementTest e CheckoutInterfaceTest: **14 testes,73 assertions, aprovados**.
- Suíte completa em SQLite em memória: **202 testes,935 assertions;192 aprovados,7 ignorados,3 falhas**.
- Comparação failure/error do JUnit com Etapa4: nenhuma diferença. Permanecem:
  - AdminAccessTest::test_administrator_navigation_shows_the_administration_link — link administrativo no dashboard.
  - ExampleTest::test_the_application_returns_a_successful_response — categories ausente no contexto do teste.
  - SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers — mesma ausência de categories.
- git diff --check: sem erros.
- Revisão independente somente leitura: nenhum bloqueador ou regressão concreta encontrado. Integração Alpine/Livewire foi comprovada no navegador.
- A validação pausando uma requisição mostrou que o loading implícito deixava algumas ações de outras linhas habilitadas. Alvos explícitos corrigiram a apresentação; a repetição confirmou todos os controles desabilitados até a resposta.
- Build e suíte foram repetidos após os ajustes finais. Hashes SHA-256 confirmaram preservação dos templates/CSS Home, Catálogo e Produto, card compartilhado, header/footer, layout app, checkout, CartManager, ações e rotas. Alterações anteriores já presentes no Git nesses arquivos não pertencem à5A.

## Navegador real

Chrome headless com rotas reais Laravel/Livewire, login e ações reais em SQLite/disco/sessões temporários com dados fictícios. Nenhum seeder rodou no banco de desenvolvimento. Viewports CSS320/375/768/1024/1280/1440px, DPR1, zoom conhecido100%, altura1000px. A barra de rolagem pode reduzir a largura útil em15px; não se presumiu o zoom do mosaico oficial.

A matriz principal cobriu vazio/um/três produtos nas seis larguras (**18 combinações**). Nomes/marca longos, imagem ausente e estoque reduzido foram conferidos nas seis larguras (**12 combinações adicionais**). Depois dos ajustes finais de espaçamento/moeda, uma verificação somente leitura repetiu as seis larguras em vazio/um produto longo/dois produtos, usando os estados deixados pelos testes funcionais (**18 combinações**).

Em todos os casos verificados: sem overflow horizontal; um header, um main e um footer; nomes sem corte; controles com pelo menos44px e dentro da viewport. Resumo à direita a partir de1024px e abaixo dos itens nas demais larguras. Imagens disponíveis carregadas com contain.

[Validação funcional e matriz inicial](validation.json) · [Validação do CSS final](final-layout-validation.json).

Interações verificadas:

- Visitante: carrinho redireciona para login.
- + por Enter e clique; −; digitação e estados disabled imediatos; quantidade máxima disponível.
- Atualizar confirma o valor digitado; subtotais conferidos contra valores esperados após cada alteração.
- Quantidade acima do estoque: erro real visível e subtotal salvo preservado. Corrigir/atualizar removeu o erro no cenário testado.
- Requisição interceptada/pausada: controles desabilitados e Atualizando carrinho visível; reabilitação após resposta.
- Remover recalcula subtotal pelo servidor; remoção do último item por Espaço atinge o vazio.
- Digitar zero e Atualizar mantém remoção funcional existente.
- Imagem com erro posterior recebe fallback.
- Link real chega ao checkout. Nenhum pedido ou pagamento foi confirmado.

## Capturas

Capturas com prefixo cart-final correspondem ao CSS final e aos estados após os testes funcionais.

- [Desktop1440px — topo](cart-final-top-multi-1440.png) e [página completa](cart-final-multi-1440.png).
- [Mobile375px — topo](cart-final-top-multi-375.png) e [página completa](cart-final-multi-375.png).
- [320px](cart-final-multi-320.png), [tablet768px](cart-final-multi-768.png), [1024px](cart-final-multi-1024.png), [1280px](cart-final-multi-1280.png).
- [Vazio desktop](cart-final-empty-1440.png) e [vazio mobile](cart-final-empty-375.png); demais larguras constam nesta pasta.
- [Um produto longo/sem imagem desktop](cart-final-long-1440.png) e [mobile](cart-final-long-375.png); demais larguras constam nesta pasta.
- [Um produto com foto desktop](cart-single-1440.png) e [mobile](cart-single-375.png): capturados durante a matriz funcional, antes da última compactação do espaçamento desktop.
- [Estoque reduzido desktop](cart-low-1440.png) e [mobile](cart-low-375.png).
- [Erro real de estoque](cart-stock-error-desktop.png), [remoção de item](cart-after-removal-desktop.png), [último item removido no mobile](cart-removed-empty-mobile.png): evidências durante os testes funcionais, antes do ajuste final de espaçamento/moeda.

## Diferenças da referência e limites

O carrinho mantém azul/navy/branco, imagens, bordas, raios e tipografia das etapas aprovadas. O eixo externo é1120px; dimensões CSS não foram inferidas da resolução do mockup. A composição replica itens à esquerda/resumo à direita e se reorganiza naturalmente no mobile.

Diferenças intencionais: ilustrações locais em vez de fotografias; linhas mais altas para labels e alvos acessíveis; ação Atualizar preservada além de +/−; entrega indefinida em vez de frete definitivo; Total dos produtos em vez de afirmar um total final antes da modalidade. Não há descontos/cupons inventados. Não foi calculado total individual na view.

Não validados dispositivos físicos, Safari/Firefox, leitores de tela ou conformidade WCAG integral. Concorrência de estoque continua responsabilidade do servidor. CSS usa :has, já utilizado no projeto. O layout é conferido por medições e inspeção de capturas reais, sem promessa de reprodução pixel a pixel do mosaico.

## Comportamentos preexistentes e pendências para5B

- UpdateCartItem valida posse, quantidade e estoque, mas não revalida status ativo de produto/categoria nessa ação. PlaceOrder faz essa verificação antes de criar o pedido. Preservado; eventual revisão funcional precisa de tarefa própria, sem ser incluída em ajuste visual.
- Erros comerciais da ação usam chaves globais quantity/cart, sem ID de linha. Foram tornados visíveis globalmente; associação mais específica depende de mudança de contrato PHP, fora desta etapa.
- Valores do resumo correspondem aos itens salvos. Digitação isolada não altera valores até Atualizar, conforme contrato diferido existente e instrução exibida.
- Etapa5B: revisar visualmente endereço, modalidades de entrega e resumo definitivo do checkout, reaproveitando tokens/componentes e preservando cálculos/regras. A navegação foi testada, mas nenhum template ou comportamento do checkout foi modificado.
- As três falhas anteriores da suíte permanecem fora do escopo; não foram corrigidas nesta etapa.
