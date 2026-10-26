# Armazenamento de revisões visuais

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
