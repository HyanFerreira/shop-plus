# Validação visual — Etapa 2

Capturas reais das rotas Laravel, com assets compilados e Livewire carregado, usando banco SQLite temporário com nove produtos fictícios e um usuário de teste. Nenhuma imagem ou dado do banco do projeto foi substituído. Os produtos de teste não têm fotos e exibem o fallback existente.

## Capturas selecionadas

- [Home, visitante, viewport CSS 320px](home-visitor-320.png)
- [Home, autenticado com nome longo, viewport CSS 1440px](home-authenticated-1440.png)
- [Catálogo, autenticado, viewport CSS 320px](catalog-authenticated-320.png)
- [Catálogo, visitante, viewport CSS 1024px](catalog-visitor-1024.png)

Foram produzidas 24 capturas no ambiente temporário. Quatro foram preservadas aqui para revisão. A matriz completa de medições está em [validation.json](validation.json).

## Ambiente e critérios

- Chrome headless; deviceScaleFactor 1, zoom padrão conhecido de 100% no ambiente de teste. Isso não implica que a referência oficial tenha sido capturada no mesmo zoom.
- Viewports CSS: 320, 375, 768, 1024, 1280 e 1440px, altura 1000px. Capturas de página inteira podem ter largura útil 15px menor devido à barra de rolagem do navegador.
- Duas páginas × dois estados de sessão × seis larguras = 24 combinações.
- Todas com exatamente um header, footer e main, busca/navegação/conta/carrinho visíveis e sem sobreposição no header ou overflow horizontal.
- Header, conteúdo e footer com containers alinhados; limite externo 1120px, incluindo gutters.
- Texto da busca reserva 52px à direita para submit de 44px; forma arredondada e padding conferidos no CSS calculado.
- Teclado real Tab/Enter: atalho inicial visível, foco no conteúdo, dez destinos do header alcançáveis e foco visível de 2px.
- Busca GET q validada contra produtos reais do banco temporário, para visitantes e autenticados; sessão autenticada obtida pelo formulário real de login.

## Testes automatizados

- Build: npm.cmd run build passou.
- StorefrontStructureTest: 6 testes, 66 assertions, todos passaram.
- Suíte existente + novos testes: 183 testes; 173 passaram, 7 ignorados e 3 falhas idênticas ao baseline da Etapa 1.
- Falhas anteriores: AdminAccessTest::test_administrator_navigation_shows_the_administration_link; ExampleTest::test_the_application_returns_a_successful_response; SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers.
- Os dois últimos consultam categories sem preparar a tabela no banco em memória. Essas falhas não foram corrigidas nesta etapa.

## Observações visuais e limites

Header agora mantém busca e navegação no mobile; footer usa texto legível e colunas responsivas. O conteúdo original das páginas foi preservado: fontes pequenas em cards/categorias, composição/corte do hero, fallback das imagens, placeholders gráficos e densidade dos filtros continuam pendentes de revisão específica. Não se tentou obter igualdade pixel a pixel com a composição oficial.

Não validado em dispositivos físicos, outros navegadores, leitor de tela ou zoom real de 200%. A validação de foco/teclado e semântica é básica, não uma auditoria completa de acessibilidade.
