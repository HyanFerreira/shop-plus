# ShopPlus — composição do login

25/10/2026, America/Sao_Paulo. Refinamento exclusivamente de apresentação, sem commits.

## Resultado

Card centralizado com limite storefront existente de 1120px e duas colunas iguais a partir de 1024px. Painel esquerdo com logo compartilhado, mensagem institucional, quatro benefícios com ícones e descrições de recursos reais e ilustração SVG aprovada da Etapa 8. Painel direito com título/descrição centralizados, formulário equilibrado verticalmente e convite de cadastro em superfície azul suave existente. Abaixo de 1024px, card de formulário único com logo; a ilustração fica oculta.

O login anterior tinha limite de 800px, proporção 42,5%/57,5%, lista apenas com checks e formulário alinhado ao topo. Essas foram as diferenças estruturais corrigidas. Cadastro e demais telas mantêm a composição anterior por meio de propriedades opcionais com padrão desativado.

## Arquivos

- `resources/views/auth/login.blade.php`: ativa a composição exclusiva, atualiza descrição e placeholder de senha.
- `resources/views/components/auth-shell.blade.php`: propriedade opcional `login`, classe de escopo e encaminhamento ao painel.
- `resources/views/components/auth-benefits-panel.blade.php`: propriedade opcional `detailed`, mantendo a lista original para outros consumidores.
- `resources/css/auth.css`: novos seletores exclusivamente sob `.auth-page--login`.
- `public/build/manifest.json` e CSS compilado com hash atualizado pelo Vite.
- Plano e este diretório: documentação, capturas e evidências de validação.

Reutilizados `x-auth-shell`, `x-auth-benefits-panel`, `x-auth-illustration`, `x-shop-logo`, `x-icon`, `x-auth-form`, `x-auth-feedback`, `x-label`, `x-input`, `x-input-error`, `x-auth-password`, `x-checkbox`, `x-auth-submit` e seu `x-button` existente.

## Aderência ao design system

Inspecionados design-system.md, ecommerce-ui-reference.png, app.css, auth.css, componentes Blade/Jetstream, Home, Catálogo, Checkout, Conta e login anterior. Mantidos navy #0B173F, ação #2563EB, superfícies/textos/bordas semânticos, Figtree e sombras/raios existentes. Título desktop 32/40px, mobile 24/32px; descrições e campos 16/24px; labels/ajudas 14/20px. Espaços de 8/16/24/32/40/48px consomem tokens existentes. Inputs, labels, checkboxes e botão primário não receberam novas regras visuais.

[Medições reais](login-layout.json) confirmam Figtree, input com fonte 16px e altura mínima 44px, botão com fundo rgb(37,99,235), colunas iguais e ausência de overflow nas seis larguras. `resources/css/app.css` e todos os tokens globais permanecem sem diff. Comparação com Home, Catálogo, Checkout e Conta confirma o mesmo vocabulário de cores, títulos, controles, superfícies e geometria. Nenhuma página comercial foi editada.

## Validação

- `npm.cmd run build`: aprovado, Vite 6.4.5, 58 módulos. Usado npm.cmd porque a política do PowerShell bloqueia npm.ps1.
- Três gates de isolamento existentes: aprovados antes da suíte.
- PHP 8.4.27, PHPUnit protegido com SQLite `:memory:`: **262 aprovados, 7 ignorados, zero falhas; 1484 assertions**, baseline preservado. [Saída](phpunit.txt), [JUnit](phpunit.xml).
- Rota real `/login` em Chrome headless, 320/375/768/1024/1280/1440px CSS, DPR1: seis larguras aprovadas. [Executor](verify-login.mjs), [medições](login-layout.json).
- Renderização adicional: sete telas de autenticação × três estados × seis larguras, **126 combinações sem overflow**, com controles de pelo menos 44px e erros associados. [Medições](validation.json). Teclado/foco do botão Mostrar/Ocultar, nome acessível dinâmico, carregamento, validação nativa, links e uso sem bundles JavaScript aprovados no login.
- Home, Catálogo, Dashboard e Perfil: **oito combinações aprovadas**, 375/1440px, sem vazamento do shell de autenticação ou overflow. [Smoke](storefront-smoke.json).
- Revisão independente do diff: nenhum problema concreto encontrado.
- `git diff --check`: aprovado. Sem mudanças em backend, rotas, validações, rate limiting, CSRF, sessão, hashing, policies, migrations, banco persistente, `.env`, dependências ou testes existentes.

O navegador usa o harness externo isolado da Etapa 8, com `.env.testing`, proteção de conexão e SQLite em memória recriado a cada requisição. As rotas de estados sintéticos ficam exclusivamente nesse harness; não foram adicionadas à aplicação. O preview PHP foi iniciado com opcache desativado por falha de inicialização ASLR do runtime local, sem alterar configuração do projeto. Não foi usado MySQL de desenvolvimento. Este isolamento é o guard do projeto, não uma VM/container adicional.

## Capturas e comparação

| Largura CSS | Captura real |
| --- | --- |
| 320px | [Login](login-320.png) |
| 375px | [Login mobile](login-375.png) |
| 768px | [Login tablet](login-768.png) |
| 1024px | [Login desktop compacto](login-1024.png) |
| 1280px | [Login desktop](login-1280.png) |
| 1440px | [Login desktop amplo](login-1440.png) |

Estrutura comparável à referência enviada: card central, painéis laterais, marca e mensagem no topo esquerdo, benefícios em lista, arte inferior, formulário à direita e cadastro após a ação principal.

Diferenças deliberadas: fundo canvas discreto, cantos/sombra do sistema, botão azul sólido, tipografia na escala existente, controle textual Mostrar/Ocultar e sacolas vetoriais aprovadas. Mantido o gradiente institucional preexistente do painel, sem criar outro gradiente. Não foram copiados os azuis elétricos, grandes raios, círculos decorativos, ícones dentro dos campos ou arte 3D da referência. O formulário prevalece no tablet de 768px para evitar campos comprimidos.

Limites: Chrome headless, sem dispositivo físico, Safari/Firefox ou leitor de tela. Reflow equivalente a zoom 200% em 640×450 CSS/DPR2; não é zoom manual do menu do navegador. A execução adicional do harness da Etapa 8 interrompeu na asserção de foco ao trocar o código do 2FA, após concluir as 126 renderizações e as verificações de senha/carregamento do login. A causa desse resultado de foco não foi investigada; a view e lógica de 2FA permanecem sem alterações. As verificações complementares específicas do login passaram. As capturas demonstram apresentação; a persistência funcional permanece coberta pela suíte protegida.
