# ShopPlus — Fundação do Design System

> Execução nesta sessão, com as skills writing-plans e executing-plans. Sem commits.

**Objetivo:** implementar somente a Etapa 1 autorizada pelo usuário.
**Arquitetura:** tokens RGB compatíveis em `app.css`, classes semânticas existentes e componentes Blade atuais. Sem dependências novas.
**Stack:** Laravel, Blade, Livewire, Tailwind 3 e Vite.
**Especificação:** solicitação da Etapa 1 nesta conversa e `docs/design/ecommerce-ui-reference.png`.

## Restrições

- Preservar APIs, aliases, atributos, bindings e formatos dos tokens existentes.
- Não editar páginas individuais, header/footer, backend, rotas ou `.env`.
- Não alterar autenticação, checkout, pagamento ou segurança.
- Testes de banco somente com SQLite em memória, sem operar o banco do projeto.

## Execução

- [x] Conferir Git, consumidores de tokens, componentes e configuração de testes.
- [x] Centralizar cores, escala tipográfica, espaços, raios, sombras e estados no CSS; conectar classes existentes.
- [x] Ajustar somente componentes reutilizáveis necessários e carregar todos os pesos Figtree já usados nos dois layouts.
- [x] Documentar contratos implementados e limites desta etapa.
- [x] Executar build, suíte existente em memória e verificação de renderização dos contratos Blade.
- [x] Revisar diff, escopo e limitações de validação; entregar sem commit.

## Foco da revisão

- Cores continuam sendo canais RGB; `--ds-brand-strong` mantém semântica de hover.
- Classes utilitárias de largura/tamanho dos consumidores continuam podendo sobrescrever defaults.
- Aliases mantêm tipo de botão, variante e encaminhamento de atributos.
- Estados disabled/read-only/erro e foco não removem semântica ou bindings.
- Mudanças globais podem aumentar controles; páginas com CSS próprio ficam pendentes para a Etapa 2.

## Decisões

- Manter larguras de containers: alterações de estrutura pertencem à Etapa 2.
- Não criar testes que apenas espelhem CSS; usar build, suíte existente e inspeção da renderização real dos componentes.

## Evidências de execução

- Git inicial limpo; alterações mantidas na branch existente, sem commits.
- Build: `npm.cmd run build` passou. `npm.ps1` é bloqueado pela política local de scripts.
- Suíte: 177 testes, 167 passaram, 7 ignorados e 3 falharam (627 assertions), com SQLite em memória.
- As mesmas três falhas foram reproduzidas em snapshot do HEAD original: link administrativo ausente no dashboard, ExampleTest e SecurityHeadersTest da home sem tabela categories. Nenhum ajuste fora do escopo foi realizado.
- 27 verificações de renderização Blade passaram: tipos, aliases, fallbacks, atributos wire/Alpine/ARIA, disabled, label e erro.
- Chrome headless: amostra isolada sem overflow em 320/375/768/1024/1440px; cores hover/active/disabled, foco de teclado, erro/foco durante hover e movimento reduzido passaram.
- Pesos Figtree 400/500/600/700/800/900 carregados no navegador; combinações de texto testadas com contraste >= 4.5:1.
- Revisão independente identificou especificidade excessiva no hover do input; corrigida com `:where()` e validada no navegador.
- Não validado: todas as telas completas, zoom real de 200%, dispositivos móveis físicos e comparação visual integral com a referência.
