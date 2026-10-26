# Carregamento da ilustração de autenticação — 26/10/2026

## Causa comprovada

A ilustração utilizada pelo painel aprovado é **`public/images/login.png`**, PNG RGBA de **1774×887**, e não o antigo SVG de sacolas. Ela tinha **880.181 bytes**.

A inspeção do componente/CSS e o Chrome DevTools Protocol mostraram:

- Nenhum `loading="lazy"`, timer, scroll, estado Alpine ou animação controla essa imagem. Logo, selo e ícones são SVGs inline e aparecem junto do painel.
- `fetchpriority="high"` já existia: a prioridade **High** foi confirmada no rastreamento de rede, antes e depois.
- `decoding="async"` permitia separar a apresentação da imagem de outras pinturas; as tarefas de decode medidas eram da ordem de milissegundos/dezenas de milissegundos.
- O tempo dominante em rede limitada foi a **transferência do PNG**, aproximadamente 7,8s. O painel e seus textos já haviam sido pintados antes de a imagem terminar de chegar. Os frames mostram também a pintura parcial das linhas do PNG durante a transferência.
- Não se reproduziu uma sequência de imagem completa → ocultação → reapresentação no mesmo documento. O efeito reproduzido foi a chegada/pintura posterior ao restante do painel.
- `width`/`height` explícitos e o CSS existente já reservavam a proporção correta. A conclusão da imagem não alterou sua área nem a altura final dos cards.

Portanto, remover uma suposta animação não resolveria a causa encontrada.

## Correção aplicada

1. **Recompressão sem perda do mesmo PNG**, no mesmo caminho: **752.578 bytes**, redução de **127.603 bytes (14,5%)**. Nenhuma redução de resolução, paleta, qualidade ou transparência. Comparação dos bytes RGBA decodificados, SHA-256 e metadados: [lossless-comparison.json](lossless-comparison.json).
2. **Preload em `<head>`**, antes das folhas de estilo/scripts, emitido pelo componente institucional compartilhado via `@once` + `@push`, consumido pelo guest layout. O link usa exatamente a mesma URL da imagem e `fetchpriority="high"`.
3. **Media query do preload `(min-width: 1024px)`**, correspondente à visibilidade do painel. Não introduz preload de desktop no mobile ou nas telas de recuperação. O comportamento de download eager da imagem oculta no mobile já existia e foi mantido.
4. **`loading="eager"` e `decoding="sync"` explícitos** na imagem existente. A dica de decodificação favorece a apresentação conjunta quando os dados estiverem disponíveis; ela não bloqueia a página esperando a rede nem garante carregamento instantâneo.
5. Preservados dimensões explícitas, aspect ratio, CSS, layout e fonte. Nenhum placeholder, spinner, timer ou JavaScript de exibição foi adicionado.

O componente continua único e o asset continua único. CSS, JavaScript, formulários, autenticação, validações, requisitos de senha e backend não receberam alterações neste ajuste.

## Arquivos modificados neste ajuste

- `resources/views/components/auth-benefits-panel.blade.php`: preload compartilhado e atributos de carregamento/decodificação da imagem.
- `resources/views/layouts/guest.blade.php`: ponto de saída do stack de preload. Páginas sem o painel não emitem o link.
- `public/images/login.png`: somente recompressão lossless, desenho exatamente preservado.
- `tests/Feature/AuthImageLoadingTest.php`: regressão do preload único em head, URLs correspondentes, atributos/dimensões e ausência de preload nas outras telas de autenticação.
- Este diretório de evidências e scripts de inspeção.

O build foi executado; como não houve alteração de CSS/JS nesta etapa, os hashes dos bundles permaneceram os mesmos da etapa anterior.

## Evidências dos primeiros frames

Chrome headless, DPR1, viewport desktop 1440×900. Captura de frames via `Page.startScreencast`, pedidos/prioridades via domínio `Network`, decodificação via `Tracing` e eventos de paint/LCP/layout via PerformanceObserver. JSON de cada cenário inclui timestamps e a lista dos frames JPEG sem montagem ou retoque.

Os dez cenários foram executados **antes e depois**: cache vazio, recarga normal, recarga forçada, navegação por link e rede limitada, para login e cadastro. Cada cenário registrou **uma única solicitação da ilustração**, High, sem duplicação pelo preload. A descoberta passou de `initiatorType: img` para `link`.

| Rede limitada | Solicitação antes/depois | Fim da transferência antes/depois |
| --- | --- | --- |
| Login | 149,0ms / 122,8ms | 7.956,1ms / 7.310,9ms |
| Cadastro | 147,9ms / 130,5ms | 7.955,1ms / 7.317,4ms |

Configuração: latência de 100ms e throughput agregado de 200.000 bytes/s. São medições individuais de laboratório, com CSS, JS e fontes concorrendo pela rede; não são promessa de desempenho absoluto. Os arquivos, tempos e cenários completos estão em [comparison.json](comparison.json).

Os JSONs individuais de login/cadastro em cache vazio e rede limitada, com a lista de frames e seus timestamps, estão na cópia local indicada abaixo. O resumo dos resultados permanece em `comparison.json`.

**Abrir comparação visual das sequências de frames (artefato local; consulte a nota de armazenamento abaixo).**

Os arquivos `*-00.jpg`, `*-01.jpg` etc. referenciados nesses JSONs estão nos respectivos diretórios da cópia local indicada abaixo. O primeiro frame de um screencast pode ainda corresponder ao documento anterior; preservei a sequência bruta e os timestamps, sem tratá-lo como primeiro paint da nova navegação. Em uma navegação rápida, o observer de RAF pode não coletar estados intermediários; os pedidos e frames continuam registrados.

## Estabilidade e limites da verificação

- Nenhuma alteração de `opacity`, `visibility` ou `animation` da ilustração foi observada; nenhum estado carregado voltou a não carregado no documento.
- A área da imagem permaneceu estável durante os estados coletados. Alturas finais idênticas antes/depois: login **642px**, cadastro **740px**, na viewport desktop testada.
- A carga em rede lenta continua podendo mostrar a imagem chegando progressivamente; transferência real não é eliminada por preload ou dicas de decode.
- Há deslocamentos preexistentes durante a troca da fonte externa com `display=swap`, anteriores à conclusão da imagem, tanto antes como depois. Eles aparecem no trace e não foram ocultados nem atribuídos à imagem. A política de carregamento da Figtree foi preservada para não ampliar o escopo. Não se afirma CLS total zero para toda a página.
- Recargas foram exercitadas no servidor temporário PHP de teste; os eventos registrados mostram os pedidos efetivos. Cabeçalhos/cache de uma instalação nginx/CDN não foram alterados ou simulados como se fossem produção.
- Verificação final de ambas as páginas em 320, 375, 768, 1024, 1280 e 1440px: tipografia, alturas de controle e ausência de overflow passaram. [Resultado](layout.txt), [estilos/medidas](final/computed.json), [login desktop](final/login-1440.png), [cadastro desktop](final/register-1440.png), [login mobile](final/login-375.png), [cadastro mobile](final/register-375.png).

## Build e testes

- `npm run build`: aprovado, Vite 6.4.5.
- **33 testes, 219 assertions, 1 ignorado, nenhuma falha**, incluindo autenticação, feedback, cadastro, política de senha e o novo teste de preload. [Resultado](tests.txt).
- O novo teste falhou antes da correção por ausência de preload em head e passou depois. [RED](server-red.txt).
- Pint do teste novo e `git diff --check`: aprovados.
- Revisão independente somente leitura: nenhum problema encontrado no push/stack Blade, reaproveitamento da URL, escopo mobile/recuperação ou fidelidade do asset. Execução de browser/testes feita pelo implementador, não repetida pelo revisor.

Todos os testes HTTP/PHP foram executados na cópia descartável protegida com `.env.testing`, guards e SQLite `:memory:`. Nenhuma alteração em `.env`, configuração em cache original, dados persistentes, dependências ou commits.

Para repetir as capturas, o script [loading.mjs](loading.mjs) exige o mesmo harness protegido em `127.0.0.1:19118` e Chrome CDP em `:19398`: `node docs/design/reviews/2026-10-26-auth-image-loading/loading.mjs before` ou `after`. O terceiro argumento opcional restringe a `login` ou `register`. Não execute a prévia/testes com banco persistente.

## Armazenamento das evidências temporárias

Frames, filmstrip e JSONs por execução foram arquivados localmente em `.artifacts/visual-reviews/cleanup-2026-10-26-122644269026/docs/design/reviews/2026-10-26-auth-image-loading/`. O manifesto na raiz desse arquivo registra caminhos originais e SHA-256. Esses dados não são publicados no Git; os caminhos de dados brutos descritos acima passam a ser relativos à cópia local. Capturas finais e resumos vinculados permanecem neste relatório.
