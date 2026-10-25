# ShopPlus — Etapa 8: autenticação

25/10/2026, America/Sao_Paulo. Escopo exclusivamente visual; nenhum commit criado. Estado inicial: `main`, HEAD `69a616f`, sem alterações locais e alinhada com a referência local `origin/main` (sem fetch).

## Resultado e referência

Analisados a [referência oficial](../../ecommerce-ui-reference.png), o design system, Home, área da conta, layout guest, componentes Blade/Jetstream e relatórios 3A–7C. A implementação usa os tokens existentes, sem criar outro sistema visual.

Login e cadastro têm card navy/branco de até 800px, proporção 42,5%/57,5%, logo, mensagem de boas-vindas, lista curta e ilustração SVG própria com sacolas em perspectiva, alças, dobras e sombras. O formulário utiliza título 24/32px, descrição 16/24px, labels e textos auxiliares 14/20px, campos de pelo menos 44px e ação de 48px mínimos. Abaixo de 768px, o painel ilustrativo é ocultado e o logo aparece antes do formulário.

A recuperação acompanha a composição de card branco único da referência, até 464px, com envelope/cadeado azul vetorial. Redefinição, confirmação, verificação e desafio 2FA seguem esse shell simples. Não há header/footer comercial nas telas de autenticação.

Comparação com o mockup: preservados navy, azul, fundo claro, divisão lateral, card centralizado e a organização do formulário. Tipografia e alvos foram adaptados à escala legível existente. O cadastro é mais alto por seus quatro campos reais. Não foram reproduzidos provedores sociais sem integração, promessas comerciais sem suporte funcional ou ilustrações retangulares antigas. Benefícios descrevem recursos reais da conta. As capturas são da implementação, não recortes da referência.

## Arquivos e componentes

Modificados:

- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/confirm-password.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/auth/two-factor-challenge.blade.php`
- `resources/views/components/auth-benefits-panel.blade.php`
- `resources/css/app.css`: apenas importação de `auth.css`.

Criados:

- `resources/css/auth.css`: estilos restritos a componentes de autenticação.
- `auth-shell`: composição simples/dividida, heading e logo mobile.
- `auth-illustration`: sacolas e envelope decorativos, sem imagens externas.
- `auth-form` / `auth-submit`: POST/CSRF e apresentação de carregamento; o botão bloqueia após submit válido, sem desabilitar inputs ou token.
- `auth-password`: mostrar/ocultar por botão nativo com `aria-controls`, `aria-pressed`, nome acessível dinâmico, ajuda e erro associados.
- `auth-feedback`: alertas escapados, resumo de erros e status do servidor.

Reutilizados `x-input`, `x-label`, `x-input-error`, `x-checkbox`, `x-button`, `x-shop-logo`, `x-icon` e `x-alert`, sem alterar seus contratos globais. O layout guest e o antigo authentication-card permanecem intactos para outros consumidores. Plano: [2026-10-25-auth-refinement.md](../../../superpowers/plans/2026-10-25-auth-refinement.md).

## Validação

| Verificação | Resultado |
| --- | --- |
| Baseline antes das mudanças | 269 testes: 262 aprovados, 7 ignorados, 0 falhas; 1.484 assertions |
| Build final Vite | Aprovado, 58 módulos, 2,08s |
| Suíte completa final SQLite `:memory:` | **262 aprovados, 7 ignorados, 0 falhas; 1.484 assertions**, PHP 8.4.27 |
| Provas funcionais adicionais, fora da descoberta da suíte | **5 testes, 91 assertions, todos aprovados** |
| Chrome: 7 telas × 3 estados × 6 larguras | **126 combinações aprovadas**, sem overflow |
| Smoke Home, catálogo, dashboard e perfil | **8 combinações aprovadas**, 375/1440px |
| Revisão independente do código | Correções de codificação e nome acessível aplicadas; nenhum outro defeito acionável encontrado |

Evidências: [build](build.txt), [suíte](phpunit.txt), [JUnit](phpunit.xml), [provas funcionais](auth-flows.txt), [JUnit adicional](auth-flows.xml), [medições do navegador](validation.json), [smoke comercial/conta](storefront-smoke.json).

As provas adicionais exercitam login inválido com erro associado e sem refletir senha; cadastro vazio, nome/e-mail/senha acima dos limites, e-mail inválido/duplicado, senha curta e confirmação divergente; resposta neutra para e-mail existente/inexistente; pedido de recuperação com notificações falsas; renderização e utilização de token real do broker, rejeição de token inválido/reutilizado e login com a nova senha; desafio 2FA obrigatório, código inválido e consumo único de recuperação. Limites existentes de login, cadastro e recuperação também foram confirmados por respostas 429. Cadastro válido e fluxo comercial permanecem cobertos pela suíte principal.

O executor adicional está em [AuthFlowProbeTest.php](AuthFlowProbeTest.php), fora de `tests/` para manter a comparação exata com o baseline. Reexecutar da raiz com `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` e `DB_URL` vazio, usando o PHP 8.4.27 real e `vendor/phpunit/phpunit/phpunit docs/design/reviews/2026-10-25-etapa-8/AuthFlowProbeTest.php`. A configuração/guards existentes continuam obrigatórios; não executar contra MySQL.

No Chrome foram verificadas larguras CSS de 320, 375, 768, 1024, 1280 e 1440px, DPR 1, heading e logo visíveis únicos, ausência de header/footer da loja, limites dos controles e associação de erros. Estados de erro/sucesso das capturas são fixtures sintéticas aplicadas às views reais, em rotas exclusivas do harness temporário externo. A aplicação não ganhou rotas de preview. Banco SQLite em memória protegido e recriado por requisição; não é prova de persistência HTTP entre requisições. A persistência e os fluxos são comprovados pelos testes PHP isolados.

Enter acionou mostrar senha com foco visível; o nome acessível acompanha Mostrar/Ocultar. A troca 2FA move foco ao campo de recuperação. Verificados carregamento, `aria-busy`, validação nativa de campos vazios/e-mail, links reais e formulário utilizável com bundles JavaScript bloqueados. Reflow equivalente a zoom 200%: viewport física 1280×900, CSS 640×450, DPR 2, nas sete telas com erros; sem overflow horizontal. É emulação de reflow, não validação manual do menu de zoom do Chrome.

## Capturas

| Tela | Desktop 1440px | Mobile 375px |
| --- | --- | --- |
| Login | [Desktop](login-initial-1440.png) | [Mobile](login-initial-375.png) |
| Cadastro | [Desktop](register-initial-1440.png) | [Mobile](register-initial-375.png) |
| Recuperação | [Desktop](forgot-password-initial-1440.png) | [Mobile](forgot-password-initial-375.png) |
| Redefinição | [Desktop](reset-password-initial-1440.png) | [Mobile](reset-password-initial-375.png) |
| Confirmação | [Desktop](confirm-password-initial-1440.png) | [Mobile](confirm-password-initial-375.png) |
| Verificação de e-mail (view inativa) | [Desktop](verify-email-initial-1440.png) | [Mobile](verify-email-initial-375.png) |
| Desafio 2FA | [Desktop](two-factor-challenge-initial-1440.png) | [Mobile](two-factor-challenge-initial-375.png) |

Outros estados: [erro de login](login-error-375.png), [erro de cadastro](register-error-375.png), [erro de recuperação](forgot-password-error-375.png), [carregamento](login-loading-375.png), [senha visível/foco](password-toggle-keyboard-375.png), [código de recuperação](two-factor-recovery-375.png), [reflow 200%](login-zoom200.png). Smoke: [Home](home-smoke-1440.png) e [dashboard](dashboard-smoke-375.png).

## Segurança e demonstração

Nenhuma mudança em actions, models, providers, policies/gates, middlewares, rotas, validações, hashing, sessões, tokens, rate limiting, CSRF, migrations, dados comerciais ou isolamento de testes. `.env`, dependências e páginas comerciais não foram editados. Não houve teste no MySQL de desenvolvimento ou operação destrutiva em banco. A comparação do diff dos diretórios protegidos ficou vazia e a suíte completa manteve o baseline. O CSS novo usa seletores de autenticação e o smoke preservou header/footer únicos, sem vazamento do shell ou overflow em outras páginas.

Limites de 255 caracteres para nome/e-mail e 128 para senhas continuam no servidor. Não foi adicionado `maxlength` que corte silenciosamente uma colagem; nome/senha recebem orientação, dados inválidos continuam rejeitados. Senhas nunca são preenchidas com `old()`. Reset mantém o token oculto e o e-mail fornecido por request/old; autenticação e recuperação usam as ações originais.

A verificação de e-mail **já estava desativada** em Fortify e o modelo já não implementava `MustVerifyEmail`. Isso foi preservado: para a demonstração atual, o professor pode cadastrar-se e iniciar o fluxo de compra sem SMTP. A view foi padronizada em isolamento, sem habilitar o recurso nem adicionar uma etapa. 2FA mantém as condições atuais e só desafia usuários que o configuraram. Não houve OAuth novo.

## Limitações e pendências

- Capturas e teclado em Chrome headless; sem dispositivos físicos, Safari/Firefox, leitor de tela ou auditoria WCAG integral.
- Envio externo de e-mail não foi realizado. Recuperação/redefinição foram comprovadas com Notification fake/transportes isolados, sem dependência externa.
- Verificação de e-mail foi validada como view inativa; seu fluxo HTTP não foi habilitado para esta revisão.
- Reflow a 200% foi emulado, conforme condições acima.
- VM, deploy e outras etapas permanecem fora do escopo. Não há pendência funcional identificada nesta Etapa 8.
