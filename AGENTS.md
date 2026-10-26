# Evidências de revisão visual

- Capturas intermediárias, filmstrips, matrizes de responsividade, traces, vídeos, comparações e JSONs temporários de browser devem ser gravados exclusivamente em `.artifacts/visual-reviews/`, ignorada pelo Git.
- Nos executores Node, use `visualReviewOutput` de `scripts/visual-review-output.mjs`. O padrão cria uma execução única; diretórios explícitos também devem ficar dentro dessa pasta. Não use `docs/design/reviews/` como saída de testes.
- Preserve todas as verificações, cenários e viewports. Mude o armazenamento, sem reduzir a cobertura ou gerar dezenas de imagens permanentes por viewport.
- Somente copie capturas finais selecionadas para documentação quando necessárias: desktop, mobile e estados específicos relevantes. Confira os links e mantenha relatórios sucintos com alterações, testes, resultados e limitações.
- Reutilize relatórios existentes quando adequado. Não crie uma pasta documental para cada tentativa de ajuste visual.
- Não mova assets de produção, mockup oficial, design system, testes, relatórios de segurança ou trabalho manual como se fossem descartáveis. Antes de retirar evidências, verifique referências e preserve uma cópia local com estrutura original, sem sobrescrever execuções anteriores.
- Antes de encerrar uma revisão, confirme com `git check-ignore` que a saída está ignorada e confira `git status --short --untracked-files=all`.
