# Etapa 8 — Refinamento da autenticação

**Objetivo:** executar a especificação fornecida para autenticação, seguindo `docs/design/ecommerce-ui-reference.png` e os tokens existentes.

**Arquitetura:** views Blade/Fortify existentes, shell exclusivo de autenticação e CSS `auth-` isolado. Login/cadastro divididos navy/branco; recuperação e segurança em card único. Componentes globais e backend preservados.

**Restrições:** sem commits, dependências novas, alterações de `.env`, banco de desenvolvimento, regras de segurança ou páginas comerciais. Trabalho no checkout solicitado; estado inicial main limpa, HEAD 69a616f, alinhada com origin/main.

- [x] Criar shell, ilustrações SVG próprias, mensagens e controles de apresentação reutilizáveis. Manter formulários POST e CSRF; erros associados aos campos, sem truncamento de entrada.
- [x] Refinar login, cadastro, recuperação, redefinição, confirmação, verificação e desafio 2FA, preservando condições e ações existentes.
- [x] Build e suíte protegida SQLite :memory:; comparar com 262 aprovados/7 ignorados. Exercitar cadastro/login, validações, token de recuperação e segurança.
- [x] Chrome real em 320/375/768/1024/1280/1440px, foco, teclado, carregamento e zoom 200%; capturas e comparação com referência. Conferir ausência de alterações comerciais/backend.
- [x] Relatório em docs/design/reviews/2026-10-25-etapa-8/README.md, evidências e limitações reais.

Decisões: especificação visual já fornecida pelo usuário é a direção aprovada; execução contínua neste checkout para entregar mudanças revisáveis. Sem habilitar verificação de e-mail (já desativada) nem outros recursos. Ilustrações vetoriais nativas evitam dependências. Não aplicar maxlength que possa cortar colagens silenciosamente; informar limites e manter validação autoritativa do servidor.
