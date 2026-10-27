# Ajustes finais SDS

Goal: gerar dados acadêmicos adicionais a cada cinco minutos, permitir acompanhar somente contas fictícias e corrigir pagamentos simulados.

Architecture: ações existentes de carrinho, endereço, pedido e pagamento; marcador próprio nos usuários e registro transacional único por janela UTC de cinco minutos. Scheduler existente, automação desligada por padrão.

Tech stack: Laravel, Livewire, MySQL na aplicação; testes exclusivamente em cópia isolada SQLite `:memory:`.

Global constraints: preservar dados, expiração de pedidos a cada minuto, permissões administrativas e verificação de e-mail desativada. Não alterar `.env`, não executar seeders, migrations destrutivas, commits ou push.

Review focus: privacidade do diretório; idempotência e rollback do ciclo; ausência de PAN/CVV persistidos; fluxo real de estoque e entrega.

- [x] Testar e implementar gerador, migration aditiva, marcador e agendamento.
- [x] Implementar diretório e dropdown compartilhado com testes de acesso e privacidade.
- [x] Corrigir validação e limpeza de pagamento com testes de compra completa.
- [x] Executar suíte integrada isolada, build e revisão das alterações.
- [x] Documentar operação, cartões de teste e implantação pendente na VM.

## Complemento obrigatório: monitoramento relacional

- [x] Nomes Faker pt_BR, cliente e produto sintéticos selecionados aleatoriamente; contas humanas nunca participam.
- [x] Resultado persistido por ciclo: aprovado 70%, recusado 20%, pendente 10%; pesos configuráveis e transições pelas Actions existentes.
- [x] Marcar produtos e pedidos sintéticos; distinguir usuário novo do comprador no registro do ciclo.
- [x] `/monitoramento` com três abas e detalhes sanitizados navegáveis de cliente, produto e pedido; filtros e paginação.
- [x] Testar fronteiras de probabilidades, múltiplas compras do mesmo cliente, pendência/expiração e isolamento de dados humanos.

Validação: suíte completa 435 testes / 3.545 asserções / sete ignorados; SQLite em memória em cópia isolada. Build Vite, Pint, Composer e revisão de código aprovados. Operação e limitações em `docs/academic-monitoring.md`; implantação na VM pendente.
