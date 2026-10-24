# ShopPlus — Etapa 3B: catálogo

Implementação limitada à apresentação do catálogo. Referências: ecommerce-ui-reference.png, design-system.md, Home aprovada e relatório/capturas da Etapa 3A. O Git já continha alterações das etapas anteriores; elas foram preservadas. Nenhum commit ou instalação de dependências foi realizado.

## Arquivos desta etapa

| Arquivo | Alteração |
| --- | --- |
| resources/css/catalog.css | Estilos responsivos do catálogo, filtros, toolbar, chips, estados vazios e paginação, com tokens existentes. |
| resources/css/app.css | Importação de catalog.css. |
| resources/views/catalog/index.blade.php | Classes do canvas/container do catálogo; mantém layout compartilhado e destino de foco. |
| resources/views/livewire/catalog/browser.blade.php | Apresentação dos filtros/resultados, disclosure, identificações dos filtros ativos e reutilização do card. |
| resources/views/components/home-product-card.blade.php | Propriedade aditiva showBrand=false; catálogo opta por true. |
| resources/views/components/catalog-pagination.blade.php | Foco, nomes acessíveis, estado atual e alvos de 44px; mesmas ações Livewire. |
| tests/Feature/Catalog/CatalogPresentationTest.php | Quatro testes de compatibilidade, filtros, estado vazio e paginação. |
| docs/design/design-system.md | Contrato efetivamente implementado da Etapa 3B. |
| docs/superpowers/plans/2026-10-23-catalog-refinement.md | Plano e verificações desta etapa. |
| docs/design/reviews/2026-10-23-etapa-3b/ | Este relatório, medições JSON e capturas reais. |

## Resultado visual e componentes reutilizados

- O mesmo x-home-product-card da Home fornece mídia 4:3 com contain, padding, borda, sombra, nomes completos em 14px, preços de 18/20px, avaliações, hover e foco. A marca acrescenta identificação no catálogo, em 14px. A Home mantém a variante padrão sem marca.
- A prioridade continua sendo imagem cadastrada disponível, SVG local do SKU fictício conhecido e estado de indisponibilidade já existente. Falha de carregamento posterior também aciona o fallback Alpine. Não foram criados assets novos, descontos ou favoritos sem ação.
- Reutilizados x-input, x-label, x-checkbox, x-button, x-badge, x-icon e ds-panel/ds-card. Não foram introduzidas bibliotecas ou componentes novos.
- Breadcrumb, título, descrição, contador e ordenação agora compartilham uma hierarquia legível. Sidebar e resultados seguem o mesmo eixo externo de 1120px do storefront.
- Sidebar de 240px no desktop; em tablet/mobile, painel details/summary no fluxo da página. Recolhido por padrão abaixo de 1024px, sem overlay nem compressão dos produtos pelo painel lateral. Filtros permanecem aplicados ao recolher/reabrir.
- Identificações cobrem busca, categorias, marcas, faixa de preço, avaliação, estoque e entrega. Limpar filtros conserva a busca, como antes; a ação Limpar busca é separada. Não há controles de remoção individual sem ligação funcional.
- Paginação e menus mantêm as ações existentes, recebem nomes acessíveis, foco visível e espaço para quebra de linha. A nota inferior não repete a promessa de parcelamento sem suporte no sistema.

## Verificações automatizadas

- `npm.cmd run build`: aprovado; assets de produção gerados pelo Vite.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/Catalog/CatalogPresentationTest.php`: 4 testes, 47 assertions, aprovados.
- Suíte completa em SQLite temporário, sem usar o banco de desenvolvimento: **192 testes, 836 assertions; 182 aprovados, 7 ignorados, 3 falhas**.
- Comparação dos casos com failure/error no JUnit da Etapa 3A: nenhuma diferença. As falhas anteriores permanecem, sem tentativa de correção nesta etapa:
  - AdminAccessTest::test_administrator_navigation_shows_the_administration_link — link de administração no dashboard.
  - ExampleTest::test_the_application_returns_a_successful_response — tabela categories ausente no contexto desse teste.
  - SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers — mesma ausência de categories.
- `git diff --check`: sem erros.
- Revisão independente de código: nenhum problema concreto identificado no escopo desta etapa.
- SHA-256 antes/depois confirmou Home Blade, home.css, header, footer, detalhe do produto e app/Livewire/Catalog/Browser.php sem alterações nesta etapa. O estado Git anterior já continha mudanças nesses arquivos.

## Navegador real

Chrome headless, rotas reais Laravel/Livewire e login real com usuário fictício em banco/disco/sessões temporários. Nenhum seeder foi executado no banco de desenvolvimento. Assets compilados, fontes carregadas e imagens decodificadas antes das capturas. Viewports CSS, DPR 1, zoom conhecido de 100%; não foram inferidas dimensões CSS da referência oficial. A barra de rolagem pode reduzir a largura útil.

| Viewport CSS | Colunas | Filtros iniciais | Visitante | Autenticado |
| --- | --- | --- | --- | --- |
| 320px | 1 | Recolhidos | Aprovado | Aprovado |
| 375px | 2 | Recolhidos | Aprovado | Aprovado |
| 768px | 3 | Recolhidos | Aprovado | Aprovado |
| 1024px | 3 | Expandidos à esquerda | Aprovado | Aprovado |
| 1280px | 3 | Expandidos à esquerda | Aprovado | Aprovado |
| 1440px | 3 | Expandidos à esquerda | Aprovado | Aprovado |

Nas 12 combinações: 12 cards na primeira página, imagens disponíveis com contain, fontes mínimas de 14px para nome/marca e 18px para preço, nenhum overflow horizontal ou sobreposição entre cards, exatamente um header e um footer. As medições estão em [validation.json](validation.json).

Interações conferidas no navegador:

- Cinco ordenações: relevância, avaliação, nome, menor e maior preço; conferência dos primeiros resultados esperados.
- Próxima/anterior com quatro produtos na segunda página; opções de 12/24/48 itens.
- Busca com resultado e sem resultado, com retorno após Limpar busca.
- Categoria, marca, mínimo/máximo, avaliação, estoque, frete grátis e entrega rápida; aplicação combinada até um resultado e nove identificações visíveis.
- Limpar filtros conserva a busca; limpeza separada da busca restaura o catálogo. Parâmetro category legado e sua limpeza também verificados.
- Mobile: Enter abre o painel nativo; atualização Livewire mantém a abertura; Escape recolhe e devolve foco ao summary; reabertura mantém checkbox aplicado. Tab/Escape no menu de ordenação devolvem foco ao botão.
- Nome propositalmente longo sem corte nem overflow; busca longa com chip quebrando linha; erro de imagem provocado no navegador mostra o fallback existente.
- Retorno à Home confirmou ausência da marca adicional do catálogo.

## Capturas

- [Desktop 1440px — topo](catalog-top-visitor-1440.png) e [página completa](catalog-visitor-1440.png).
- [Mobile 375px — topo](catalog-top-visitor-375.png) e [página completa](catalog-visitor-375.png).
- [Tablet 768px](catalog-visitor-768.png), [320px](catalog-visitor-320.png), [1024px](catalog-visitor-1024.png) e [1280px](catalog-visitor-1280.png).
- [Autenticado desktop](catalog-authenticated-1440.png) e [autenticado mobile](catalog-authenticated-375.png); as outras quatro larguras autenticadas também constam nesta pasta.
- [Filtros abertos no mobile](catalog-mobile-filters-open.png), [mobile com filtro aplicado e painel recolhido](catalog-mobile-filtered.png).
- [Filtros ativos combinados](catalog-active-filters-desktop.png), [busca sem resultado](catalog-search-empty-desktop.png), [segunda página](catalog-page-2.png).
- [Nome longo](catalog-long-name-mobile.png) e [busca longa](catalog-long-query-mobile.png).

## Comparação visual e limites

A referência aprovada orienta o conjunto azul/navy/branco, sidebar, três colunas desktop e hierarquia dos produtos. O acabamento dos cards agora é o da Home, por reutilização efetiva do componente, sem segunda implementação de imagens/tipografia. As ilustrações próprias continuam distintas das fotografias do mockup. A contagem de colunas se adapta à largura CSS disponível, sem pressupor zoom da composição oficial.

Diferenças intencionais: campos numéricos de preço funcionais em vez de slider decorativo; painel expansível em vez de drawer; ausência de favoritos, descontos e parcelamento inventados; nomes completos podem aumentar a altura da linha. A sidebar pode ser mais longa conforme a quantidade real de marcas, inclusive quando há poucos resultados. Esses pontos preservam funcionalidade e conteúdo existentes.

Não foram validados dispositivos físicos, Safari/Firefox, leitores de tela ou conformidade WCAG integral. As capturas usam dados fictícios isolados; outras imagens e textos cadastrados podem mudar a altura dos cards. Não houve revisão das páginas de detalhe, carrinho, checkout, autenticação ou pedidos. As três falhas anteriores da suíte e integrações futuras já registradas seguem pendentes, fora da Etapa 3B.
