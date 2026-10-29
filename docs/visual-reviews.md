# Armazenamento de revisões visuais

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
