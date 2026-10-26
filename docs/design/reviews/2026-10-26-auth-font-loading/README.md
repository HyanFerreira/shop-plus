# FOUT e estabilidade de login/cadastro — 26/10/2026

## Causa efetivamente identificada

O problema reproduzido foi **FOUT**, pela troca de uma fonte do sistema pela Figtree externa. O layout usava Bunny Fonts com `display=swap`, não Google Fonts diretamente. A folha externa precisava chegar antes de o navegador descobrir os arquivos WOFF2. Essa dependência permitia pintar o texto com fallback enquanto as fontes ainda eram transferidas.

O Chrome mostrou a diferença entre fonte declarada e fonte realmente utilizada:

- `getComputedStyle()` já declarava Figtree e os tamanhos/pesos aprovados, inclusive durante o estado inicial.
- `CSS.getPlatformFontsForNode` identificou **DejaVu Sans** nos primeiros estados visíveis em rede lenta.
- Após as fontes carregarem, a fonte real mudou para os arquivos Figtree. Seus nomes internos são `Figtree Light`, `FigtreeLight-Regular`, `FigtreeLight-SemiBold` e `FigtreeLight-Bold`; esses nomes já estavam nos arquivos usados antes, não significam uma mudança dos pesos CSS.
- `document.fonts` registrou os pesos 400, 600 e 700 em carregamento. A conclusão coincidiu com mudanças nas métricas/quebras/posições registradas e nos frames.

### Causas diferenciadas

| Hipótese | Resultado |
| --- | --- |
| FOUT | Confirmado: fallback real → Figtree, com métricas diferentes. |
| FOUC | Não reproduzido na configuração válida. `app.css` é folha bloqueante em head tanto no build quanto no Vite. |
| CSS de autenticação tardio | Não encontrado. Os imports locais são processados no CSS entregue; não há uma requisição tardia separada de `auth.css`. |
| Dimensões da imagem não reservadas | Não encontrado: imagem mantém width/height e proporção já aprovadas. |
| Classes alteradas por Alpine/JS | Nenhuma mutação de `class` nos registros dos cenários. Os controles interativos continuam usando seus mecanismos existentes. |
| Zoom | `visualViewport.scale = 1`, DPR1 durante as comparações. |

O script de prefetch já emitido pela integração Laravel/Vite não recebeu alterações; ele não alterava a tipografia ou classes dos elementos avaliados. Não foi atribuído a ele o FOUT.

## Estratégia implementada

- Arquivos **WOFF2 locais idênticos aos anteriormente fornecidos pelo Bunny**, sem conversão, alteração de desenho ou troca de versão.
- Pesos normais **400, 500, 600 e 700**. Removida a dependência de buscar os pesos 800/900 nas duas rotas, pois não são utilizados nelas.
- Preservados os subsets Latin e Latin Extended e seus `unicode-range`. Os extended ficam disponíveis sob demanda e não são antecipados quando desnecessários.
- `@font-face` em um partial de head, com família CSS `Figtree`, estilo normal, pesos corretos, WOFF2 e **`font-display: swap`**. Não há período de texto invisível introduzido para mascarar o problema.
- Preload dos pesos Latin 400/600/700, **34.304 bytes no total**, com `as="font"`, `type="font/woff2"` e `crossorigin="anonymous"`. O peso 500 recebe preload somente quando existem erros do servidor, pois as mensagens o utilizam.
- Preloads das fontes antes do CSS e da imagem; `@vite` permanece em head com stylesheet bloqueante. O preload da ilustração foi mantido depois da descoberta do CSS, ainda com prioridade alta.
- Nenhuma regra de tamanho, espaçamento, fallback, altura fixa ou compensação de métrica foi adicionada. Nenhum timer, spinner, ocultação do body ou espera de `document.fonts.ready` foi introduzido na aplicação. Essa API aparece apenas no script de diagnóstico para capturar o estado final.

A entrega local está **restrita às rotas `login` e `register`**. O guest layout também atende a loja, então a home e a recuperação mantêm o carregamento externo anterior. Uma regressão de escopo foi escrita e reproduzida antes dessa restrição; a correção foi validada na suíte.

Os arquivos são distribuídos com o copyright e a [SIL Open Font License 1.1 original](https://github.com/google/fonts/blob/main/ofl/figtree/OFL.txt), preservados em `public/fonts/figtree/OFL.txt`. URLs originais, tamanhos e SHA-256 estão em [sources.json](../../../../public/fonts/figtree/sources.json). O modelo de stylesheet/head da integração existente foi mantido; veja também a [documentação oficial de integração do Vite](https://vite.dev/guide/backend-integration.html).

## Evidências e validação de carregamento

**44 cenários após a correção**, comparados aos mesmos cenários antes:

- Build e Vite de desenvolvimento.
- Login e cadastro.
- Acesso inicial com cache desabilitado em 320, 375, 768, 1024, 1280 e 1440px.
- Atualização comum, hard reload com `ignoreCache`, navegação entre as duas páginas.
- Rede normal e rede limitada (100ms de latência, 200.000 bytes/s), desktop e mobile.

Todos os cenários finais passaram nas verificações de:

- Fonte real Figtree desde o primeiro estado visível avaliado.
- Mesma família, peso, tamanho, altura de linha e letter-spacing ao estabilizar.
- Nenhuma mudança nas medidas/posições coletadas depois do primeiro paint.
- Apenas três solicitações de fontes nas páginas sem erros, cada URL solicitada uma vez, sem fontes externas nem pedidos duplicados entre preload e `@font-face`.
- Inputs e botões de 40px, ausência de overflow, imagem e dimensões finais preservadas.
- **CLS observado igual a zero**, usado junto das verificações de fonte real e geometria, não como única prova.

Exemplos em rede lenta, desktop:

| Modo / tela | Fonte real inicial antes | Fonte inicial depois | CLS antes | CLS depois |
| --- | --- | --- | ---: | ---: |
| Build / login | DejaVu Sans | Figtree | 0,002898 | 0 |
| Build / cadastro | DejaVu Sans | Figtree | 0,061110 | 0 |
| Vite / login | DejaVu Sans | Figtree | 0,002874 | 0 |
| Vite / cadastro | DejaVu Sans | Figtree | 0,015389 | 0 |

Medições de laboratório individuais, não garantias absolutas para toda condição de rede. [Resumo dos 44 cenários](verification-summary.json), [comparações de carregamento](loading-comparison.json).

### Filmstrip e dados brutos

**Abrir filmstrip antes/depois (artefato local; consulte a nota de armazenamento abaixo)** — hard reload e rede lenta, build e Vite, desktop e mobile, com frames clicáveis na resolução original.

Na cópia local indicada abaixo, os diretórios `before-build`, `before-dev`, `after-build` e `after-dev` contêm os JSONs individuais. Cada JSON registra:

- Estilos computados e geometria da logo, títulos, benefício, label, input, link, botão, bloco inferior, card e imagem em cada mudança observada.
- Estado e pesos de `document.fonts`.
- Fontes reais iniciais/finais retornadas pelo Chrome.
- Folhas de estilo, pedidos/prioridades/tempos, paint e layout shifts.
- Lista e timestamps dos frames JPEG sem retoque.

Os frames iniciais podem pertencer ao documento anterior. O filmstrip identifica os timestamps negativos; os dados brutos são preservados, sem usar esse frame como prova do primeiro paint da nova página. Elementos ocultos responsivamente podem retornar listas de fontes vazias no protocolo.

### Design final preservado

**24 comparações PNG pixel a pixel**, ambas as telas × seis larguras × build/Vite: **zero pixels alterados** após estabilização. [Resultado](final-pixel-comparison.json).

Exemplos finais:

- [Login antes](before-build/login-1440-cold-final.png) / [depois](after-build/login-1440-cold-final.png).
- [Cadastro antes](before-build/register-1440-cold-final.png) / [depois](after-build/register-1440-cold-final.png).
- [Login mobile antes](before-build/login-375-cold-final.png) / [depois](after-build/login-375-cold-final.png).
- [Cadastro mobile antes](before-build/register-375-cold-final.png) / [depois](after-build/register-375-cold-final.png).

## Arquivos deste ajuste

- `resources/views/layouts/guest.blade.php`: seleção das fontes locais somente nas duas rotas, fontes externas anteriores preservadas nas demais, ordem de recursos em head.
- `resources/views/partials/auth-fonts.blade.php`: declarações e preloads compartilhados.
- `public/fonts/figtree/`: oito WOFF2 sem modificações, licença, proveniência e hashes.
- `tests/Feature/AuthFontLoadingTest.php`: fontes/preloads, peso de erro e preservação da entrega em home/recuperação.
- Este diretório de evidências e diagnóstico.

Não houve alteração no CSS aprovado, JavaScript, autenticação, cadastro, validação, CSRF, sessões, rotas, banco, `.env`, configuração do Vite ou do servidor de desenvolvimento. O layout autenticado da aplicação também não foi editado.

## Testes e build

- `npm run build` aprovado, Vite 6.4.5. Os hashes dos bundles permaneceram os mesmos, pois CSS/JS não foram alterados nesta etapa.
- Suíte final: **299 testes, 1754 assertions, 7 ignorados, nenhuma falha**. [Resultado](full-tests.txt).
- Pint do teste novo e `git diff --check` aprovados.
- Novos testes foram RED antes da implementação; a regressão de escopo foi RED antes da restrição às rotas. [Registro inicial](server-red.txt), [escopo](scope-red.txt).
- Revisão independente encontrou o uso compartilhado de guest na loja. A entrega foi restringida e o teste de home/recuperação passou; os demais pontos de fontes/preloads/licença não tiveram achados. A revisão foi somente de código, sem repetir os testes do implementador.

## Isolamento e reprodução

PHP e preview utilizaram a cópia descartável `/tmp/shopplus-shared-auth/app`, `.env.testing`, proteções do projeto e SQLite `:memory:`. Não foi acessado banco persistente. O `.env`, cache de configuração e `public/hot` originais foram preservados. Não houve commit, instalação de biblioteca ou publicação.

O preview em `APP_ENV=testing` bloqueia Vite pela CSP, corretamente. Para diagnosticar o modo dev, o Chrome de teste usou `Page.setBypassCSP` **somente nesse modo**, permitindo observar os assets do Vite que a CSP de `local` já autoriza. Isso não modifica a aplicação ou sua segurança; esses registros não pretendem testar a CSP. O modo build foi testado com a CSP normal. Um primeiro teste dev com CSP de testing bloqueou assets e foi descartado da comparação; não foi tratado como FOUC do projeto.

O Vite existente em 127.0.0.1:5173 foi usado apenas para fornecer assets; a alternância de `public/hot` ocorreu exclusivamente na cópia descartável. O script [verify.mjs](verify.mjs) requer esse preview isolado em :19118 e Chrome CDP em :19398. Exemplo: `node docs/design/reviews/2026-10-26-auth-font-loading/verify.mjs after build`. Para modo dev, configure apenas o hot file da cópia antes e use `after dev`.

## Armazenamento das evidências temporárias

Frames, filmstrip e JSONs por execução foram arquivados localmente em `.artifacts/visual-reviews/cleanup-2026-10-26-122644269026/docs/design/reviews/2026-10-26-auth-font-loading/`. O manifesto na raiz desse arquivo registra caminhos originais e SHA-256. Esses dados não são publicados no Git; os caminhos de dados brutos descritos acima passam a ser relativos à cópia local. Capturas finais e resumos vinculados permanecem neste relatório.
