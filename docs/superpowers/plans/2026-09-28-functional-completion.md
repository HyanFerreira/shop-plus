# Plano de conclusão funcional

## Objetivo

Fechar todas as lacunas do desenho aprovado, deixando como pendência apenas refinamento visual e integrações declaradas fora do escopo.

## Ciclo financeiro e estoque

- Adicionar prazo de pagamento e comando agendado para expirar pedidos pendentes.
- Liberar reservas de modo idempotente em cancelamento e expiração.
- Implementar reembolso administrativo, devolução ao estoque, pagamento reembolsado e encerramento coerente da entrega.

## Administração

- Criar gestão de administradores e bloqueio de contas com confirmação de senha.
- Impedir autodesativação e remoção do último administrador ativo.
- Exibir métricas reais no dashboard.
- Criar consulta paginada e filtrável da auditoria, sem revelar metadados cifrados sensíveis.

## Auditoria e proteção

- Auditar login, falha, logout, lockout, recuperação/troca de senha, dados pessoais e falhas administrativas de autorização.
- Auditar os CRUDs administrativos restantes e recebimentos de estoque.
- Completar limites de requisição para cadastro, recuperação e área administrativa.

## Usabilidade funcional

- Centralizar rótulos em português para estados.
- Documentar SMTP de demonstração e atualizar o estado real do README.
- Testar migrations do zero, agendamento, autorização, idempotência, build e dependências.
