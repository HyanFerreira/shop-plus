# Plano de implementação: auditoria, hardening e demonstração

## Objetivo

Encerrar a atividade com trilha de auditoria protegida, dados sintéticos reproduzíveis e instruções operacionais seguras.

## Auditoria

- Registrar eventos críticos de checkout, pagamento, ajuste de estoque e entrega.
- Manter ator, entidade, horário e metadados cifrados, sem PAN, CVV ou PII em claro.
- Tornar registros imutáveis no modelo.

## Demonstração

- Seeder idempotente com administrador, cliente, CPF sintético válido, telefone, endereço, catálogo, fornecedor e estoque.
- Criar pedidos pendente e pago usando as mesmas ações de domínio da aplicação.
- Obter senha de `DEMO_PASSWORD` ou gerar e exibir uma senha local efêmera; nunca versionar senha real.

## Hardening e entrega

- Documentar variáveis de produção para cookies seguros, sessão cifrada e modo sem debug.
- Revisar cabeçalhos, cache de PII, autorização e ausência de segredos versionados.
- Executar migrations, seeder em transação, suite completa, build e auditorias.
