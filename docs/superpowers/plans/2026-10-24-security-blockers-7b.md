# Etapa 7B — execução focada nos bloqueadores

Pedido autoriza atualizar runtime e verificar os bloqueadores, sem repetir a auditoria completa ou refatorar backend. Continuar pelo terminal; nenhuma nova automação de tela nesta retomada.

1. Preservar checkout e PHP atual. Backup feito em `%TEMP%/shopplus-before-7b-20261024-180329`.
2. Herd aberto, mas CLI instalada sem php:update. Usar ZIP NTS x64 PHP 8.4.27 oficial, SHA-256 validado, testar extensões em staging, parar workers e manter instalação anterior para rollback.
3. Reiniciar FastCGI Herd e artisan serve em loopback; comprovar CLI/HTTP/INI efetivo sem endpoint diagnóstico público. Corrigir omissão de argumentos de exceções para impedir a causa do vazamento histórico de CPF.
4. Analisar apenas os quatro avisos NPM restantes. Backport parser 6.1.5 em override específico do Typography, validado por comparação de CSS idêntico e build. Conservar auditoria bruta e documentar divergência de faixa no feed.
5. Executar suíte SQLite protegida, probes de isolamento, build e auditorias finais; documentar origem dos dois CPFs sem valores.
6. VM só no IF, Docker daemon indisponível: sem MySQL descartável seguro nesta sessão. Registrar como pendentes, entregar checklist operacional curto e passos para log rotation sem apagar evidências.

Sem commits, sem recovery/binlogs, sem migrations/testes em banco persistente e sem alteração de layout.

## Resultado

Passos locais concluídos: PHP8.4.27 oficial com checksum confirmado, FastCGI/CLI e HTTP real verificados, upload progress Off, argumentos de exceção omitidos e prova sintética aprovada. Rollback preservado. Suite269/262aprovados/7skips/0falhas,1482assertions; build e gates passaram. Override6.1.5 só no parser do Typography; único pacote com versão alterada em relação ao lock da Etapa7B. CSS idêntico na comparação. Auditoria NPM ainda4moderados por divergência do feed com backport listado pelo mantenedor; auditoria sem dev0. Composer0.

VM e MySQL simultâneo permanecem pendentes por indisponibilidade; checklist e relatório em docs/security/7b. Log antigo preservado com hash idêntico, origem fictícia no seeder documentada sem valores; rotação proposta, não executada. Nenhum backend foi refatorado.
