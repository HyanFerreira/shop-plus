# ShopPlus — Etapa 4: detalhes do produto

Refinamento limitado à página individual e ao template de compra. Referências analisadas: ecommerce-ui-reference.png, design-system.md, Home, Catálogo, card compartilhado, manifesto de imagens e relatórios/capturas das Etapas 3A/3B. O Git já continha as etapas anteriores, que foram preservadas. Sem commits, dependências novas, alteração de .env ou operações no banco de desenvolvimento.

## Arquivos desta etapa

| Arquivo | Alteração |
| --- | --- |
| resources/views/catalog/show.blade.php | Composição, galeria funcional, informações persistidas, seções descritivas e relacionados reutilizando card. |
| resources/views/livewire/product-add-to-cart.blade.php | Quantidade, limites/disabled, botão primário, carregamento e associação dos erros. |
| resources/css/product-detail.css | Estilos product- responsivos com tokens existentes. |
| resources/css/app.css | Somente importação de product-detail.css nesta etapa. |
| tests/Feature/Catalog/ProductDetailPresentationTest.php | Cinco testes de imagens, conteúdo, reutilização e contrato de compra. |
| tests/Feature/Catalog/PublicCatalogTest.php | Fixture da foto cria arquivo no disco fake, representando uma imagem realmente disponível. |
| docs/design/design-system.md | Contrato implementado da página. |
| docs/superpowers/plans/2026-10-23-product-detail-refinement.md | Decisões e verificações desta etapa. |
| docs/design/reviews/2026-10-23-etapa-4/ | Este relatório, capturas e medições. |

## Melhorias e funcionalidades preservadas

- Galeria à esquerda no desktop e antes das informações no mobile. Imagens inteiras com contain. Arquivos cadastrados disponíveis têm prioridade, em ordem e sem repetir paths. Sem fotos disponíveis, há fallback local do SKU conhecido; sem representação, mensagem de indisponibilidade.
- Uma imagem não cria miniaturas artificiais. Várias imagens oferecem botões de seleção com aria-pressed/controls e posição anunciada. Clique, teclado e troca de foco funcionam. Falhas anteriores à inicialização de Alpine e posteriores ao carregamento recebem fallback.
- Título 24/32px no mobile e 32/40px a partir de 640px; preço 32/40px; marca/categoria/SKU/avaliação 14/20px. Conteúdo longo quebra linha, sem clamp. Relacionados usam exatamente x-home-product-card, preservando preços, imagens, avaliações e links.
- Reutilizados x-storefront-layout, header/footer, x-home-product-card, x-label, x-input, x-button, x-input-error, x-icon e ds-panel. Nenhuma biblioteca ou componente novo.
- Quantidade e ação principal usam alvos de 48px. Limites visuais consideram estoque disponível e máximo existente 99. Disabled acompanha também a digitação, sem nova requisição. Carregamento desabilita ações e mostra Adicionando…; erros têm associação ao campo, aria-invalid e região de anúncio.
- Preservados wire:model quantity, métodos increment/decrement/add e wire:submit add. O servidor continua validando estoque total, inclusive itens já no carrinho. Visitante segue ao login; adição válida mantém redirecionamento e flash reais no carrinho. Não foram alteradas regras de estoque, reservas, preço ou segurança.
- Descrição e especificações são seções reais. Dados técnicos vêm dos registros. Sem comentários individuais disponíveis, não há aba vazia de avaliações. Média/contagem persistidas são exibidas; zero avaliações é informado.
- Removidos favoritos sem ação, selo de oferta sem desconto, promessa de 12x e frete grátis acima de R$199. Comprar agora só abria checkout sem adicionar este produto e foi removido, sem criar outro fluxo. Pagamento é identificado como demonstração; entrega remete às modalidades reais do checkout.

## Build, testes e revisão

- npm.cmd run build: aprovado, assets de produção gerados pelo Vite.
- php vendor/phpunit/phpunit/phpunit tests/Feature/Catalog/ProductDetailPresentationTest.php tests/Feature/Catalog/PublicCatalogTest.php: **12 testes, 77 assertions, aprovados**.
- Suíte completa em SQLite em memória: **197 testes, 885 assertions; 187 aprovados, 7 ignorados e 3 falhas**.
- Comparação dos casos failure/error com JUnit da Etapa 3B: os mesmos três casos, nenhuma falha nova:
  - AdminAccessTest::test_administrator_navigation_shows_the_administration_link — link administrativo no dashboard.
  - ExampleTest::test_the_application_returns_a_successful_response — categories ausente no contexto do teste.
  - SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers — mesma ausência de categories.
- git diff --check: sem erros.
- Revisão independente encontrou disabled desatualizado ao digitar quantidade no binding diferido. Reproduzido antes da correção; seis estados de digitação/incremento/decremento passaram depois, preservando métodos/binding. [Evidência](quantity-validation.json).
- A hipótese adicional de erro anterior à inicialização de Alpine foi reproduzida atrasando o script e usando arquivo inválido no disco temporário. Antes: imagem quebrada sem fallback. Após x-init verificar complete/naturalWidth: imagem ocultada e fallback visível. [Evidência](early-image-validation.json).
- Build, testes específicos, suíte completa e matriz principal do navegador foram executados novamente após as correções.
- Hashes SHA-256 antes/depois confirmaram preservação de Home Blade/CSS, template Livewire/CSS do Catálogo, card compartilhado, header/footer, classe ProductAddToCart, ação AddCartItem e CatalogProductController. Alterações anteriores já presentes no Git nesses arquivos não pertencem à Etapa 4.

## Navegador real

Chrome headless, rotas reais Laravel/Livewire, login e adição reais com dados fictícios em SQLite, disco public e sessões temporários. Nenhum seeder foi executado no banco de desenvolvimento. Galeria de teste usa asset demo existente e imagem sintética da traseira somente no disco temporário; paths repetido/ausente validam deduplicação/prioridade. A segunda imagem não foi adicionada aos assets do projeto.

Viewports CSS, DPR 1, zoom conhecido de 100%, altura de 1000px. A barra de rolagem pode reduzir a largura útil em 15px. Não se presumiu o zoom da referência oficial. Fontes carregadas e imagens decodificadas antes das capturas.

| Viewport CSS | Composição | Colunas relacionadas | Visitante / autenticado |
| --- | --- | --- | --- |
| 320px | Galeria antes das informações | 1 | Aprovado / aprovado |
| 375px | Galeria antes das informações | 2 | Aprovado / aprovado |
| 768px | Galeria antes das informações | 3 | Aprovado / aprovado |
| 1024px | Duas colunas | 4 | Aprovado / aprovado |
| 1280px | Duas colunas | 4 | Aprovado / aprovado |
| 1440px | Duas colunas | 4 | Aprovado / aprovado |

Nas 12 combinações: zero overflow horizontal; um header e um footer; imagem principal única e inteira; duas miniaturas reais deduplicadas; título >=24px e preço >=32px; campo/botão dentro da viewport. [Medições e verificações funcionais](validation.json).

Interações: clique, setas com retorno circular, Home/End e Espaço; foco entre miniaturas; incremento/decremento e limite nativo; erro real ao exceder estoque; carregamento/disabled; adição com redirecionamento e flash; rejeição quando o carrinho já contém o estoque disponível; visitante direcionado ao login. Produto de uma imagem demo, sem estoque, SKU sem imagem e nome/descrição longos (incluindo palavra sem quebra) foram conferidos. Conteúdo longo passou nas seis larguras. Erro posterior da imagem mostrou fallback.

## Capturas

- [Desktop 1440px — topo](product-top-visitor-1440.png) e [página completa](product-visitor-1440.png).
- [Mobile 375px — topo](product-top-visitor-375.png) e [página completa](product-visitor-375.png).
- [320px](product-visitor-320.png), [tablet 768px](product-visitor-768.png), [1024px](product-visitor-1024.png), [1280px](product-visitor-1280.png).
- [Autenticado desktop](product-authenticated-1440.png) e [autenticado mobile](product-authenticated-375.png); demais larguras autenticadas constam nesta pasta.
- [Seleção por teclado](product-gallery-keyboard-desktop.png), [demo com imagem única](product-demo-single-desktop.png), [indisponível](product-unavailable-desktop.png).
- [Erro de estoque](product-stock-error-desktop.png), [sucesso no carrinho](product-add-cart-success.png), [fallback após erro](product-runtime-fallback-mobile.png).
- [Sem imagem e conteúdo longo mobile](product-missing-long-375.png) e [desktop](product-missing-long-1440.png).

## Comparação e limites

Mantidos eixo externo, identidade azul/navy/branco e acabamento da Home/Catálogo. A composição segue imagem/miniaturas à esquerda, informações/preço/ações à direita e descrição abaixo; no mobile, sequência natural. As ilustrações locais continuam diferentes das fotografias do mockup, conforme Etapa 3A. O mosaico oficial não foi usado para inferir pixels CSS ou zoom.

Diferenças intencionais: seções em vez de abas vazias; ausência de favoritos/descontos/compra imediata sem suporte; pagamento e entrega condizentes com o sistema. Nenhuma especificação ou avaliação inventada. A área de ações ocupa mais espaço para preservar labels, alvos de 48px e textos de demonstração. Outros registros podem mudar a altura da página.

Não validados dispositivos físicos, Safari/Firefox, leitores de tela ou conformidade WCAG integral. Disponibilidade é a do momento de renderização; concorrência continua tratada pelo servidor. A captura do carrinho é evidência funcional, sem revisão visual dessa página. Home, Catálogo, checkout, autenticação e demais fluxos não foram refatorados. As três falhas anteriores seguem pendentes fora desta etapa.
