# Limpeza conservadora de evidências — 26/10/2026

A auditoria inicial encontrou **997 arquivos não rastreados** e **19 alterações em arquivos versionados** (15 modificados e 4 exclusões anteriores): 1.016 entradas no status detalhado. `docs/design/reviews/` continha **1.406 arquivos / 178.982.415 bytes (170,7 MiB)**, incluindo **332 imagens versionadas**. O inventário local registra extensões, caminhos e referências de imagens por relatório.

Foram classificados e movidos **849 artefatos / 77.680.052 bytes (74,1 MiB)**:

| Categoria | Arquivos |
| --- | ---: |
| Frames intermediários JPEG | 644 |
| JSONs de diagnóstico por execução | 119 |
| Capturas intermediárias/de viewport sem link documental | 74 |
| Visualizadores de filmstrip | 2 |
| Imagens versionadas sem link, idênticas a imagens preservadas | 10 |

Os 10 arquivos versionados aparecem como exclusões locais pendentes no Git. As cópias recuperáveis mantêm bytes e estrutura original. Não houve alteração no índice, commit, push, publicação ou reescrita do histórico. As exclusões anteriores do desenvolvedor permanecem separadas no inventário inicial.

Destino: `.artifacts/visual-reviews/cleanup-2026-10-26-122644269026/`. O manifesto local contém origem, destino, tamanho, SHA-256 e motivo de cada movimento. Os relatórios que apontavam para sequências temporárias agora explicam a localização local; suas capturas finais e resumos continuam disponíveis. Os filmstrips arquivados continuam abrindo seus próprios frames. Para recuperação, consulte [o procedimento](visual-reviews.md).

Foram preservados código, testes, assets de produção, mockup, design system, relatórios de segurança, capturas citadas e arquivos sem classificação segura de redundância. A documentação não foi reduzida indiscriminadamente: permanecem **557 arquivos / 101.306.096 bytes (96,6 MiB)** nas revisões. Evidências de estados específicos e comparações históricas foram mantidas quando ainda referenciadas.

## Prevenção

`.artifacts/` está no `.gitignore`. Os **21 executores de browser** agora usam o mesmo seletor de saída local: cada execução padrão recebe um diretório único, e destinos explícitos fora da pasta ignorada ou com symlinks são rejeitados. Os cenários, assertions, capturas e matrizes de viewport dos executores foram preservados. As instruções em `AGENTS.md` estendem essa política às próximas revisões, incluindo ferramentas de browser usadas diretamente pelo agente.

## Validação

- **849 hashes de arquivos arquivados conferidos**; as 10 cópias redundantes também foram comparadas às capturas que permanecem na documentação.
- **563 arquivos fora das revisões conferidos antes/depois da validação/build**, incluindo as **23 imagens em `public/images/`**, as alterações locais de aplicação e `.env`. Nenhuma mudança adicional nesses arquivos.
- **508 links locais** em documentos Markdown e comparações HTML conferidos, sem links quebrados; nenhum já estava quebrado na auditoria inicial.
- **1.056 referências dos filmstrips arquivados** conferidas na cópia local.
- `node --test scripts/visual-review-output.test.mjs`: **4 testes aprovados**, cobrindo isolamento de execuções, diretório explícito, rejeição de destinos externos/symlinks e integração dos 21 executores antes do contato com CDP.
- Sintaxe dos **21 executores** aprovada por `node --check`; `git diff --check` aprovado.
- `npm run build`: **aprovado**, 60 módulos, sem alteração adicional nos bundles existentes no início desta limpeza.
- `git check-ignore` confirmou a regra; arquivos de evidência criados pelos testes não aparecem no status e não há arquivos rastreados em `.artifacts/`.

As verificações acima testam a política de armazenamento. Não foi repetida uma revisão visual das telas nem executada suíte PHP/migrations: não houve mudança na aplicação ou nas verificações de interface, apenas no destino das evidências.

O status final detalhado contém **207 entradas: 164 não rastreados e 43 alterações em arquivos versionados**, redução de aproximadamente **80%**. As alterações anteriores do projeto foram preservadas; as novas alterações são a política/documentação, o seletor de saída com seus testes, os diretórios dos executores e as 10 exclusões redundantes. Os arquivos temporários ficaram fora do Source Control. O inventário inicial e o status final completo estão na cópia local para comparação.
