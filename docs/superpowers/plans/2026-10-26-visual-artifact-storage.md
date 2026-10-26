# Visual artifact storage Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Retirar evidências temporárias do Source Control preservando o trabalho local.
**Architecture:** Arquivo local com manifesto SHA-256 e caminhos originais; saída compartilhada dos executores restrita à pasta ignorada. Relatórios e capturas citadas permanecem versionáveis.
**Tech Stack:** Git, Python para inventário local, Node.js para executores CDP.
**Spec:** Solicitação do usuário de 26/10/2026, limpeza conservadora de artefatos visuais.

## Global Constraints

Sem commits, push, migrations, alterações de .env, design, autenticação, banco ou assets de produção. Não descartar mudanças locais.

## Review Focus

Caminhos repetidos não podem sobrescrever arquivos na cópia de recuperação. Links de relatórios e comparações preservadas precisam resolver. Diretórios explícitos de saída fora da pasta ignorada devem falhar antes do browser. Symlinks não podem redirecionar gravações para documentação. Capturas finais citadas e código dos testes devem permanecer.

## Tasks

- [x] Registrar inventário e hashes anteriores, classificar filmstrips/JSONs por execução e imagens redundantes comprovadas.
- [x] Testar e implementar `visualReviewOutput(review, phase, requested)` em `scripts/visual-review-output.mjs`; cobrir isolamento, repetição, saída explícita e symlinks em `scripts/visual-review-output.test.mjs`.
- [x] Atualizar os 21 executores apenas na seleção do diretório; adicionar `.artifacts/` ao ignore e política curta em `AGENTS.md`.
- [x] Mover arquivos classificados para arquivo local único com estrutura original e manifesto; corrigir documentação sobre os dados brutos locais.
- [x] Verificar hashes, links, ignore, status, sintaxe dos executores, testes Node e build; registrar resultados sem commits.
