# Armazenamento de revisões visuais

## Marcadores de 18px e afastamento dos conectores — 29/10/2026

Correção conforme referência explícita do seletor enviada pelo usuário: círculos e conectores futuros agora usam `rgb(193 194 196)`, isolado em `--dashboard-pending-color`. A mistura anterior resultava em aproximadamente RGB 123/137/156 e permanecia mais escura que o tom desejado. Labels mantêm sua cor legível e peso 500 em uma variável separada, sem mudanças em dimensões, alinhamento ou estados. Build e 72 combinações visuais aprovados; cor computada exata 193/194/196 confirmada para preenchimentos/bordas/linhas, e geometria idêntica ao baseline. Captura desktop inspecionada. Evidências ignoradas em `.artifacts/visual-reviews/dashboard-pending-reference-gray-HJIf7Q/`; sem commits ou push.

Ajuste fino posterior dos labels: somente `.dashboard-progress [data-step-state='unrecorded'] .dashboard-progress__label` recebe o cinza claro aprovado `--dashboard-pending-color` e `--ds-weight-medium` (500). Linhas/círculos já aprovados e estados concluídos/em foco permanecem intactos. Build e 72 combinações visuais aprovados; “Pago” e preparo efetivo inspecionados. Peso 500 e igualdade exata da cor entre labels/círculos/linhas pendentes confirmados; geometria, textos e hierarquia dos demais estados idênticos. Evidências ignoradas em `.artifacts/visual-reviews/dashboard-pending-labels-ui0Pya/`, com `results.json` e capturas. Sem commits ou push.

Suavização posterior exclusivamente de cor: `--dashboard-pending-color` mistura 85% de `--ds-control-line` com 15% de `--ds-surface`, aplicada igualmente ao preenchimento/borda dos futuros e conectores neutros. Build aprovado; 72 combinações visuais e sete destinos revalidados. Contraste dos marcadores/linhas permanece acima de 3:1 sobre branco; comparação exata confirma geometria e labels idênticos, com azuis, halo, 18px/14px, 2px e afastamentos de 4px preservados. Captura desktop inspecionada. Evidências isoladas e ignoradas em `.artifacts/visual-reviews/dashboard-pending-gray-SR3gjZ/`; sem commits ou push.

Ajuste restrito ao CSS e à partial da timeline: círculos de 16 para 18px, checks de 12 para 14px com traço 2 da biblioteca existente e afastamento de 4px entre cada extremidade dos conectores e os círculos, inclusive na apresentação vertical. Linhas mantêm 2px e suas cores; futuros são preenchidos com `--ds-control-line`, igual ao neutro dos conectores. Primary, foco e halo preservados. Contextos secundários, incluindo “Aguardando”, deixam de ser visíveis e permanecem acessíveis a leitores de tela, sem alterar os estados. A altura anterior é reservada por tokens; compensação proporcional mantém as posições dos labels e o layout externo dos pedidos.

Build aprovado e 25 testes de Conta/267 assertions sem falhas em SQLite `:memory:`. Chrome: 72 combinações dos oito cenários e nove larguras existentes; sete destinos retornando 200. Medidos 18px/14px, check branco centralizado com traço 2, 4px nas duas extremidades, espessura de 2px, neutro preenchido, cores de progressão e ausência visual dos contextos. Comparação exata confirmou x/y/largura/altura das regiões externas e botões idênticos à execução anterior. Desktop/mobile inspecionados, sem colisões ou overflow da timeline.

Evidências preservadas em `.artifacts/visual-reviews/dashboard-marker-spacing-G3LdsV/`, com `results.json`, capturas e baseline anterior; saída ignorada confirmada. Preview isolado; limitação preexistente do header permanece. Sem commits ou push.

## Contraste e foco da timeline — 29/10/2026

Refinamento restrito à apresentação da timeline. Neutros usam `--ds-control-line` (círculos/linhas) e `--ds-secondary` (labels), mantendo 2px nos conectores. Concluídos têm check branco/primary e label navy; foco tem ponto azul e halo suave do token `--ds-brand-soft`, com conector de chegada em `--ds-brand-line` quando a etapa anterior está concluída. Próxima etapa usa borda tracejada e “Aguardando”; trabalho confirmado usa borda contínua e “Em andamento”. As cinco colunas iguais, centralização e alinhamento independente dos botões permanecem; abaixo de 640px utiliza a apresentação vertical compacta para acomodar o contexto sem colisões.

`DashboardOrderTimeline` é um presenter sem atualizações de modelos ou novas consultas: consome os relacionamentos já carregados, Enums e histórico. Registro do pedido prova sua criação; pagamento confirmado prova “Pago”. Demais conclusões exigem evidência. Pago/aguardando preparação destaca “Preparando” como próxima etapa, e o preparo efetivo é distinto. Blocos separados de preparação foram integrados; movimentações e exceções logísticas continuam explícitas. A revisão independente encontrou um caso de preparo registrado seguido de cancelamento da entrega: corrigido e coberto por teste RED→GREEN, exibindo “Interrompido”, sem apagar o registro nem declarar conclusão.

Validação final: build aprovado; suíte SQLite `:memory:` com 451 testes, 3.747 assertions e 7 ignorados, sem falhas; Conta com 25 testes/267 assertions. Chrome verificou 72 combinações (oito cenários em 320/375/479/480/640/768/1024/1280/1440px), preservando toda a cobertura anterior e adicionando preparação efetiva, envio e interrupção. Medidos contraste dos labels ≥4,5:1, bordas futuras ≥3:1, checks/pontos/halo, três cores de progressão, 2px, continuidade, centralização, ausência de colisões e dimensões dos botões/colunas externas preservadas. Sete destinos reais responderam 200. Desktop/mobile comparados com a referência, mantendo dimensões compactas e indicando espera em vez de execução presumida.

Evidências locais em `.artifacts/visual-reviews/dashboard-timeline-focus-HuyBjX/`, com `results.json`, `links.json` e capturas, ignoradas pelo Git. Preview com fixtures isoladas; limitação preexistente do header mantida. Sem commits ou push.

## Marcadores e labels centralizados — 29/10/2026

Somente CSS da timeline da Dashboard: círculos pendentes usam a mesma borda `--ds-line` dos conectores neutros; concluídos mantêm fundo primary e check branco centralizado (12px em círculo de 16px). Estado atual usa borda primary, fundo branco e ponto central de 8px, sem o anel interno comprimido. Labels e marcadores centralizados em suas cinco colunas na apresentação horizontal; conectores reposicionados entre as bordas dos círculos, mantendo 2px e a progressão de cores existente. Apresentação vertical compacta preservada nas larguras estreitas.

Build e 22 testes de Conta (249 assertions) aprovados. Chrome validou os mesmos cinco cenários em nove larguras (45 combinações), incluindo cores neutras/primary, dimensões consistentes, checks centralizados nos dois eixos, ponto com proporções corretas, labels centralizados abaixo dos círculos na grade horizontal, continuidade das linhas de 2px e alinhamento compacto no mobile. Geometria das regiões de pedido e botões idêntica à execução anterior; desktop/mobile inspecionados. Nenhuma mudança em dados, estados ou outros componentes.

Evidências em `.artifacts/visual-reviews/dashboard-marker-refinement-YoA2xC/`, com `results.json`, capturas e baseline preservado; saída ignorada confirmada. Preview usa fixtures isoladas e mantém a limitação preexistente do header. Sem commits ou push.

## Conectores da timeline: cor e espessura — 29/10/2026

Somente os conectores da Dashboard foram ajustados: azul `--ds-brand` quando as duas etapas adjacentes estão concluídas com evidência no histórico; demais segmentos continuam em `--ds-line`. Preparação em andamento mantém o segmento que chega a ela neutro. Sem inferir etapas ausentes. Espessura de 2px derivada de `--ds-border-width`, com centralização nos círculos: altura no desktop e largura na apresentação vertical mobile. Círculos, textos, badges, preços, botões e suas posições preservados.

Build aprovado; suíte SQLite `:memory:` com 448 testes, 3.729 assertions e 7 ignorados, sem falhas. Novo teste confirmou pendente, pago, preparando, enviado, entregue e entrega sem histórico. Chrome: 45 combinações dos cinco cenários/larguras existentes; cores medidas nos segmentos (pago: um azul; pendente: nenhum; entregue com histórico completo: quatro), 2px de espessura, continuidade/centralização e igualdade exata da geometria dos círculos/textos/ações contra a execução anterior. Desktop/mobile inspecionados; sete destinos retornaram 200.

Evidências locais preservadas em `.artifacts/visual-reviews/dashboard-connector-colors-jK1Jmc/`, incluindo `results.json`, capturas e `previous-timeline.json`; saída ignorada pelo Git. Fixtures de navegador isoladas, sem acesso ao MySQL persistente. Limitação preexistente do header permanece. Sem commits ou push.

## Timeline com cinco colunas iguais — 29/10/2026

Alteração restrita à timeline da Dashboard: rótulos “Pedido criado”, “Pago”, “Preparando”, “Enviado” e “Entregue”; removida a distribuição desigual do desktop, mantendo `repeat(5, minmax(0, 1fr))`, sem `nowrap`. Conectores passam entre as bordas dos círculos, separados da área dos rótulos; apresentação vertical compacta mantida em telas estreitas. Badges completos, históricos, indicadores, preços, imagens, botões e a grade externa dos pedidos preservados.

Build aprovado e 21 testes de Conta (243 assertions) sem falhas em SQLite `:memory:`. Chrome validou 45 combinações: cinco cenários em 320/375/479/480/640/768/1024/1280/1440px. Verificados limites reais de cada fragmento de texto dentro de sua coluna, cinco larguras iguais, alinhamento dos círculos/conectores, posições iguais entre pedidos, labels em uma linha no desktop e ações à direita independentemente do texto dos botões. Pagos e pendentes comparados; desktop e largura compacta inspecionados. Sete destinos reais responderam 200.

Evidências locais em `.artifacts/visual-reviews/dashboard-timeline-equal-columns-17WS7Y/`, com `results.json`, capturas e baseline anterior preservado. Saída ignorada confirmada por `git check-ignore`; o preview usa fixtures isoladas e conserva a limitação preexistente do header. Sem commits ou push.

## Setas globais e timelines — 29/10/2026

`x-arrow` centraliza o desenho já utilizado na Home: 20×20px, proporção 1:1, traço 2.5 e rotação conforme a direção. Ações de Home, Catálogo, Carrinho, Checkout, Conta, Pedidos, Monitoramento e autenticação, além de paginações Laravel/Livewire e templates auxiliares, usam o mesmo componente. SVGs/Unicode e estilos redundantes de seta removidos; chevrons de dropdowns mantidos. Os overrides locais de paginação preservam URLs, comandos, cursores, chaves, scroll e estados desabilitados dos templates instalados.

Dashboard: três regiões com a mesma grade em todas as linhas; região de ações reservada por token e colunas internas da timeline independentes do texto/estado atual. Preços e botões à direita. Etapas em uma linha no desktop, com alinhamento de cada marcador entre pedidos; adaptação anterior mantida em telas menores. Nenhuma alteração no domínio ou no container global.

Validação: build aprovado; suíte isolada com 447 testes, 3.723 assertions e 7 ignorados, sem falhas. Probe adicional em SQLite `:memory:` confirmou `nextPage`/`previousPage` e itens corretos no Catálogo e Favoritos (1 teste, 12 assertions). Chrome: 90 combinações de rotas/larguras em 320/375/768/1024/1440px; 35 combinações da Dashboard em 320/375/640/768/1024/1280/1440px; sete destinos reais retornando 200. Medidos tamanho/traço/direções, chevrons, botões, alinhamento das três regiões e dos cinco marcadores, labels em uma linha e ausência de overflow da Dashboard. Desktop/mobile inspecionados e revisão independente sem problemas críticos/importantes.

Evidências temporárias preservadas em `.artifacts/visual-reviews/global-arrows-timelines-MvVZvf/`, incluindo `global-results.json`, `results.json` e capturas. Limitações: preview usa fixtures em memória; ensaio adicional de clique no navegador ficou inconclusivo, e as ações de paginação foram confirmadas diretamente nos componentes pelo probe PHP. Overflow preexistente do header mantido. A largura padronizada de 20px pode acrescentar uma linha natural em um atalho em 320px, sem alterar fonte ou espaçamentos. Saída ignorada confirmada; sem commits ou push.

## Dashboard do cliente — 29/10/2026

Ajuste visual posterior: hero alinhado pelo topo; coração `heart-square` dos favoritos com proporção 1:1 e sem compressão; “Plus” usa `ds-shop-logo__accent`; cinco setas usam `x-icon` com o desenho já presente no CTA da Home, 20px e traço 2.5. Sem mudanças nos dados. Build e 35 testes relevantes (391 assertions) aprovados. As 25 combinações de cenários/viewports e sete destinos foram preservados; medições confirmam ícones de benefícios em 24×24px centralizados, cor da marca idêntica à logo, topo das colunas igual e dimensões dos indicadores/atalhos iguais ao baseline. Desktop/mobile inspecionados. Evidências desta execução em `.artifacts/visual-reviews/dashboard-polish-sVKW0E/`, separadas da revisão anterior e ignoradas pelo Git. A limitação preexistente do header permanece.

Layout exclusivo de `/dashboard`: hero em duas colunas, destaque para segurança da conta, quatro indicadores reais (incluindo favoritos), três pedidos recentes com etapas baseadas no histórico e atalhos inferiores. Figtree, tokens, container, painéis, ícones, imagens/status e secondary de 40px existentes. CSS carregado somente pela Dashboard; header, footer e demais páginas preservados. Sem fidelidade, percentuais ou dados fictícios no produto.

Validação: build aprovado; suíte em SQLite `:memory:` com 447 testes, 3.723 assertions, 7 ignorados e nenhuma falha. Rechecagem de Conta/Header/Estrutura: 35 testes, 391 assertions. Chrome: 25 combinações de cinco cenários (com pedidos, vazio, cancelado/reembolsado/falha logística, processamento/envio/entrega e textos longos) em 320/375/768/1024/1440px; quatro indicadores, grids, fonte, botões, imagens, ausência de dados alheios e overflow da Dashboard. Sete destinos reais retornaram 200; âncora de segurança confirmada. Capturas desktop, tablet e mobile inspecionadas.

Revisão independente sem achados críticos ou importantes. Dois ajustes menores foram cobertos por testes que falharam antes da correção: linguagem neutra sobre a opção de duas etapas e contagem de favoritos com o mesmo escopo de visibilidade da lista de destino. Nenhuma pendência dessa revisão.

Evidências locais em `.artifacts/visual-reviews/dashboard-redesign-MZ1rr5/`: `results.json`, `links.json` e capturas por cenário/largura; armazenamento confirmado por `git check-ignore`. Limitação: overflow do header existente em 320 e 1024px foi reproduzido com a Dashboard oculta; nenhuma alteração no header. Navegador usa fixtures isoladas, sem acesso ao MySQL persistente. Sem commits ou push.

## Secondary do design system — 28/10/2026

Padrão centralizado em `app.css`: branco, texto primary oficial, borda primary a 20%, hover a 5%, active a 10%, peso 600 e tokens existentes de Figtree 14px, raio e sombra. Comprar agora usa a variante sem sobrescritas locais. Quantidade mantém dimensões e divisórias; paginação mantém 40 × 40px, grupo contínuo e raios externos. Header, dropdowns e o controle destrutivo Remover preservam seus estilos anteriores.

Validação: build isolado aprovado; 67 testes de Catálogo/compra/Carrinho, 682 assertions, sem falhas. Chrome verificou 15 cenários de paginação (início/meio/fim em 320/375/768/1024/1440px), cinco larguras do produto, normal/hover/focus/disabled/loading e movimento reduzido. Comparação com CSS anterior confirmou primary, danger, ghost/text, header, dropdowns, página ativa e input intactos. Capturas desktop/mobile inspecionadas; revisão independente sem achados. Evidências temporárias em `.artifacts/visual-reviews/secondary-buttons-kjvpld/`, com `results.json` e capturas, ignoradas pelo Git.

Limitações: navegador usa HTML real de fixtures em SQLite em memória; ações de servidor cobertas por PHPUnit. Dois testes auxiliares de armazenamento falharam no Windows por dependerem de `/tmp` e permissão de symlink; os outros dois passaram. Ignore confirmado diretamente com `git check-ignore`. Sem commits ou push.

Evidências temporárias ficam em `.artifacts/visual-reviews/`, ignorada pelo Git. Isso inclui frames, filmstrips, screenshots de todas as viewports, JSONs por cenário, traces, vídeos e resultados temporários de browser. Os cenários e verificações continuam completos; somente o destino mudou.

Os 21 executores CDP em `docs/design/reviews/` usam `visualReviewOutput` de `scripts/visual-review-output.mjs`. Execute-os a partir da raiz com o preview isolado e Chrome exigidos no cabeçalho. Cada chamada sem saída explícita cria um diretório novo, com nome da revisão/fase e sufixo único. Liste os diretórios mais recentes em `.artifacts/visual-reviews/` para localizar a execução. Nunca use um preview conectado ao banco persistente; siga [a proteção de testes](testing-safety.md).

| Executor | Argumentos após o nome do arquivo |
| --- | --- |
| Executores de 25/10 | `[diretório de saída]` |
| auth-typography/verify e recovery/auth-regression | `[before ou after]` |
| auth-image-loading/loading | `[before ou after] [login ou register]` |
| auth-font-loading/verify e recovery/loading | `[before ou after] [build ou dev]` |
| password-requirements/verify | `[diretório de saída]` |
| recovery/verify | nenhum |
| shared-auth/verify | `[before ou after] [diretório de saída]` |

Diretórios explícitos devem estar dentro de `.artifacts/visual-reviews/`; destinos externos e symlinks são rejeitados antes de acessar o navegador. Para executores consumidores que leem `admin-orders-baseline.json`, passe o mesmo diretório local usado pelo executor de baseline. A saída explícita permite esse compartilhamento; use uma pasta nova para cada revisão para preservar as execuções anteriores.

Apenas capturas finais selecionadas devem ser copiadas manualmente para documentação: desktop, mobile e estados que expliquem um resultado. Relatórios devem informar alteração, testes executados, resultado e limitações, com links conferidos. Não criar pastas documentais para cada tentativa. Novos executores Playwright/browser devem usar esse mesmo destino para screenshots, vídeos, traces e resultados, preservando toda a cobertura.

Verificação de armazenamento: `node --test scripts/visual-review-output.test.mjs`. Confirme também `git check-ignore .artifacts/visual-reviews/exemplo/frame.png` e examine `git status --short --untracked-files=all`.

## Recuperação da limpeza de 26/10/2026

Os arquivos removidos da documentação estão em `.artifacts/visual-reviews/cleanup-2026-10-26-122644269026/`, mantendo a estrutura `docs/design/reviews/...`. O `manifest.json` local contém origem, destino, tamanho, SHA-256, motivo e indicação de rastreamento anterior. `original-reports/` guarda o texto original dos relatórios que contextualizavam os dados brutos.

Para recuperar um arquivo, consulte sua entrada no manifesto, valide o hash e copie o destino para a origem **somente se a origem não existir**. Não sobrescreva arquivos atuais. Para recuperar o filmstrip, use o HTML dentro da cópia local, onde os caminhos relativos dos frames continuam válidos. Nenhum histórico Git foi reescrito.

O [relatório da limpeza](visual-artifact-cleanup.md) registra inventário, critérios, contagens e validação.

## Controles e pagina??o ? 28/10/2026

Componente `x-select` compartilhado para os 16 selects nativos existentes e o seletor de quantidade do Cat?logo; variantes ghost/text/icon no `x-button` e migra??o dos bot?es administrativos com estilos paralelos. Perfil e Carrinho j? utilizavam os bot?es compartilhados. O Cat?logo teve o aviso inferior removido, rodap? em linha no desktop e pagina??o compacta unida. Bindings, confirma??es e op??es foram preservados.

Valida??o: build isolado aprovado; su?te com 442 testes, 3.622 assertions e 7 testes ignorados. Navegador: 15 combina??es de p?ginas inicial/intermedi?ria/final com larguras 320, 375, 768, 1024 e 1440px; selects e bot?es renderizados de Perfil/Administra??o; teclado dos selects nativos, menu de ordena??o e menu do perfil. Resultado aprovado. Evid?ncias tempor?rias em `.artifacts/visual-reviews/controls-pagination-1KywIZ/`, com `results-final.json` e capturas finais desktop/mobile, ignoradas pelo Git.

Limita??es: a??es de servidor foram verificadas pelo PHPUnit; a revis?o de navegador usa HTML real de fixtures em SQLite em mem?ria. O menu aberto do select nativo segue o sistema operacional. O overflow anterior da header em 320/375px foi reproduzido com o CSS anterior e mantido: o rodap? ajustado cabe na ?rea de resultados, e esta tarefa n?o altera a header.

### Corre??o do dropdown do Cat?logo e controles de 40px

O Cat?logo agora usa `x-catalog-dropdown` tanto em `model="sort"` quanto em `model="perPage"`, extra?do da implementa??o Alpine original de Ordenar por. CSS e comportamento s?o compartilhados, incluindo painel, op??es, chevron, sele??o, foco, setas, Home/End, Escape e clique externo. Os demais selects nativos n?o foram alterados nesta corre??o. O valor fora das op??es na URL mant?m o fallback efetivo de 12 corretamente anunciado e focado.

Setas e n?meros da pagina??o t?m largura/altura fixas de 40px; abaixo de 1280px uma janela com menos n?meros preserva atual, limites e setas, mantendo o tamanho dos controles. Somente a janela vis?vel participa do layout e da navega??o acess?vel.

Valida??o final: 443 testes, 3.666 assertions, 7 ignorados, sem falhas; build aprovado. Navegador verificou 15 cen?rios de Cat?logo (in?cio/meio/fim em 320/375/768/1024/1440px), dimens?es exatas, uma ?nica p?gina ativa vis?vel, equival?ncia de estilos dos dropdowns e teclado. Evid?ncias: `.artifacts/visual-reviews/catalog-custom-dropdown-rLz0Zb/results-final.json` e `per-page-open-final.png`. As a??es de servidor foram cobertas pelo PHPUnit; o navegador usa HTML real de fixtures isoladas. Limita??o da header estreita permanece conforme o registro anterior.
# Carrinho — 29/10/2026

Lista em painel único, resumo lateral no desktop e empilhamento no mobile, com dados reais, estoque, lixeira por item e limpeza do carrinho. Controle de quantidade extraído do produto sem mudar seu visual ou comandos. Digitação preservada; alterações salvam ao sair do campo ou pressionar Enter, e +/− salvam automaticamente. Sem botão Atualizar. Navegação para checkout aguarda o salvamento válido; mutações concorrentes são bloqueadas.

Validação: build e Pint aprovados; suíte PHP isolada com 452 testes, 3.758 assertions e 7 ignorados, sem falhas; 10 testes JavaScript aprovados. Navegador verificou 35 combinações de sete cenários e cinco larguras (320/375/768/1024/1440px), além da equivalência exata de estilos do controle do produto antes/depois nas cinco larguras. Seis fluxos reais de teclado/clique confirmaram edição seguida de +/−, Enter, remoção, limpeza e checkout sem chamadas duplicadas nem perda da ação. A revisão independente identificou corridas entre blur, clique e navegação; corrigidas e cobertas pelos testes.

Evidências locais: `.artifacts/visual-reviews/cart-redesign-9AdexF/`, com `results.json`, `product-parity.json`, `interactions.json` e capturas desktop/mobile inspecionadas. Saída confirmada por `git check-ignore`. Fixtures usam SQLite em memória, sem banco persistente. Limitação: overflow preexistente do header em largura estreita preservado; o conteúdo do carrinho não apresenta overflow. Sem commits ou push.
