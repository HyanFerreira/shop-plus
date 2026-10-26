# Refinamento da composição do login

Especificação: solicitação detalhada fornecida pelo usuário em 25/10/2026. Execução no checkout atual, inicialmente limpo, sem commits.

Direção: ampliar apenas o login para o limite storefront existente, com duas colunas iguais no desktop (1024px), benefícios com ícones e descrições reais à esquerda, formulário centralizado à direita e convite de cadastro em superfície informativa existente. Abaixo de 1024px, priorizar formulário em card único. Manter SVG aprovado, controles e todos os tokens globais. A alternativa de alterar o shell inteiro afetaria o cadastro e foi descartada pelo escopo.

- [x] Inspecionar design system, referência oficial, CSS global/auth, componentes, Home, Catálogo, Checkout, Conta e login atual.
- [x] Aplicar variante opt-in do login no shell e painel; CSS limitado a `.auth-page--login`, sem mudanças funcionais ou dependências.
- [x] Executar build e suíte com proteção SQLite em memória, preservando 262 aprovados/7 ignorados/zero falhas.
- [x] Validar navegador real em 320/375/768/1024/1280/1440, estados, teclado e reflow; capturar desktop/mobile e revisar diff.
- [x] Registrar evidências e diferenças deliberadas da referência.

Verificação visual é apropriada para esta mudança reversível de apresentação; a suíte existente cobre contratos funcionais. Não adicionar testes que apenas espelhem markup/CSS.
