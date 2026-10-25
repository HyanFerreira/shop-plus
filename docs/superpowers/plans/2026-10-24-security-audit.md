# Etapa 7 — plano de execução da auditoria

Execução nativa autorizada pelo pedido, sem commits e sem redesign. Manter o checkout atual para incluir as alterações locais das etapas anteriores; backup externo antes de editar. Não usar bancos persistentes em testes.

1. Preservar diff e arquivos não rastreados; executar baseline com o bootstrap protegido, SQLite `:memory:`. Investigar os dois testes que acessam a home sem migrations.
2. Conferir PHP CLI, processos HTTP, INI e extensões. Consultar avisos oficiais; usar atualização do gerenciador Herd quando disponível e backup do INI. Se indisponível, registrar procedimento e aplicar apenas mitigação reversível comprovável.
3. Ler rotas, middleware, componentes, actions, modelos e consultas. Reproduzir falhas de validação de autenticação, filtros, limites de carrinho e expiração de pagamentos em testes isolados antes de corrigir.
4. Corrigir causas confirmadas sem alterar apresentação. Acrescentar testes de autorização, propriedade de dados, XSS, SQLi, CSRF real, entrada adversarial, repetição e rollback de operações comerciais.
5. Executar auditorias Composer/NPM; atualizar versões compatíveis e validar build. Documentar avisos sem correção compatível, sem upgrades major automáticos.
6. Rodar suíte completa, isolamento e revisão do diff. Entregar relatório em `docs/security/etapa-7-auditoria.md`, evidências, checklist da VM, validação MySQL pendente e roteiro acadêmico/Workbench.

## Limites de comprovação

VM não identificada nem acessada; sem presumir configuração remota. SQLite não comprova locks MySQL. Nenhum `migrate:fresh`, `db:wipe` ou teste será executado contra `shopplus_db`. Relatório deve separar revisão estática, testes executados e verificações pendentes.

## Foco da revisão

IDs e propriedades Livewire hostis, autorização backend, arrays antes de casts, limites antes de hashes/consultas, idempotência sob lock, ausência de CVV/PAN em persistência e snapshots, manutenção de escape e middleware CSRF.

## Execução e revisão

- Baseline: 227 testes, 218 aprovados, 7 ignorados, 2 falhas reproduzidas. Backup externo realizado; sem commits ou descarte.
- Correções de aplicação e regressões executadas em SQLite :memory:. Auditorias Composer/NPM e build executados. Resultados e limitações em `docs/security/etapa-7-auditoria.md` e `docs/security/evidence/`.
- Revisão independente pelo agente `security_review`: dois achados P2 no guard (paginação e campo numérico vazio), reproduzidos RED e corrigidos GREEN; nenhuma falha adicional apontada nas correções comerciais/API.
- Decisão: usuário informou que a VM só existe no PC do IF. Entregar roteiro específico em vez de alegar inspeção remota; custo residual é validação de infraestrutura pendente antes da liberação.
- Decisão: preservar log histórico com duas linhas de padrão CPF; não substituir evidência local anterior. Acesso/retenção e saneamento privado antes da apresentação permanecem necessários.
- Adiamentos: runtime PHP crítico não atualizado porque o gerenciador Herd Desktop está fechado; mitigação INI reversível aplicada e verificada CLI. Quatro avisos moderados CSS sem upgrade major, CSP permissiva, MySQL concorrente e inspeção visual não realizados. Não houve alteração de layout.
- Verificação final registrada em evidence/phpunit.txt e XML: 269 testes, 262 aprovados, 7 ignorados, zero falhas, 1482 assertions. Gates de isolamento PASS; lint e build passaram.
