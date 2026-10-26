# Autenticação compartilhada — 26/10/2026

O cadastro utiliza o painel aprovado do login, sem variantes, e o mesmo `public/images/login.png`. O login permanece visualmente idêntico, incluindo o `margin-top: 20px` da marca.

## Arquivos e responsabilidades

- `resources/views/components/auth-benefits-panel.blade.php`: componente existente simplificado. Uma implementação da logo, selo, texto, três benefícios e imagem. Removida a variante antiga do cadastro; nenhum asset foi criado ou copiado.
- `resources/views/components/auth-shell.blade.php`: layout existente utilizado pelas duas páginas, com a apresentação compartilhada `auth-page--branded`.
- `resources/css/auth.css`: regras anteriormente restritas ao login agora compartilhadas; somente tipografia de cadastro tem regras próprias. Título de 32px, labels, inputs, botão e navegação inferior de 16px, controles de 40px, largura máxima de 416px no desktop. Sem altura fixa do card.
- `resources/views/auth/register.blade.php`: quatro campos com ícones, placeholders, limites, mensagens individuais e formulário Fortify com CSRF. Erro `confirmed` do Laravel apresentado na confirmação. Não consulta existência de conta durante digitação.
- `resources/views/components/auth-password.blade.php`: reutilizado no cadastro com os ícones já existentes e mensagens individuais; demais páginas mantêm a apresentação anterior.
- `resources/views/components/auth-field-error.blade.php`: renomeação do antigo `login-field-error`, reutilizado nas duas páginas, mantendo ícone e apresentação.
- `resources/js/auth-validation.js`: generalização do antigo `login-validation`, preservando a lógica de interação do login e adicionando nome, política de senha e confirmação dependente no cadastro. Corrige erros após edição, conserva erros do servidor em valores inalterados e mantém a validação nativa sem JS. Termos obrigatórios continuam respeitados quando habilitados pelo Jetstream.
- `resources/views/auth/login.blade.php` e `resources/js/app.js`: referências aos componentes e ao módulo compartilhados.
- `tests/Feature/RegistrationPresentationTest.php`: seis testes de painel idêntico, campos acessíveis, mensagens escapadas, validação autoritativa, duplicidade e confirmação.
- `public/build/manifest.json` e assets gerados: build atualizado e versionado conforme a organização existente.

## Comparação visual real

Chrome headless, DPR 1, fontes e imagens carregadas, mesma viewport e foco removido em ambas as execuções. Os PNGs foram comparados em RGB com Pillow: **zero pixels alterados nas sete capturas do login**. Resultado detalhado em [login-pixel-comparison.json](login-pixel-comparison.json).

| Viewport CSS | Login antes | Login depois | Cadastro |
| --- | --- | --- | --- |
| 320×640 | [PNG](before/login-320x640.png) | [PNG](after/login-320x640.png) | [PNG](after/register-320x640.png) |
| 375×812 | [PNG](before/login-375x812.png) | [PNG](after/login-375x812.png) | [PNG](after/register-375x812.png) |
| 768×900 | [PNG](before/login-768x900.png) | [PNG](after/login-768x900.png) | [PNG](after/register-768x900.png) |
| 1024×768 | [PNG](before/login-1024x768.png) | [PNG](after/login-1024x768.png) | [PNG](after/register-1024x768.png) |
| 1280×720 | [PNG](before/login-1280x720.png) | [PNG](after/login-1280x720.png) | [PNG](after/register-1280x720.png) |
| 1440×800 | [PNG](before/login-1440x800.png) | [PNG](after/login-1440x800.png) | [PNG](after/register-1440x800.png) |
| 1440×600 | [PNG](before/login-1440x600.png) | [PNG](after/login-1440x600.png) | [PNG](after/register-1440x600.png) |

O painel esquerdo é idêntico no DOM das duas páginas. As verificações também confirmam ausência de overflow horizontal, centralização, imagem carregada sem corte/sobreposição, campos e botão de 40px, largura máxima de formulário, título/labels e navegação inferior. Telas curtas têm rolagem vertical natural. O painel fica oculto abaixo de 1024px, preservando o comportamento do login. As dimensões finais dos PNGs podem excluir a barra vertical e incluir o restante da página rolável; a viewport CSS continua a indicada.

## Validação e segurança dos testes

A configuração em cache existente no checkout original foi rejeitada pelo guard. Ela foi preservada. Criou-se uma cópia descartável da aplicação fora do repositório, sem `.env`, `public/hot` ou cache de configuração, com `.env.testing`, `TestEnvironment::protect` e banco exclusivamente SQLite `:memory:`. Nenhum teste foi executado em banco persistente. Os três gates de segurança standalone passaram antes da execução.

- Baseline: **275 testes, 1543 assertions, 7 ignorados**, sem falhas.
- Final: **281 testes, 1607 assertions, os mesmos 7 ignorados**, sem falhas. [Comparação dos casos](suite-comparison.json), [baseline](baseline.txt), [resultado final](final.txt).
- `RegistrationTest::test_new_users_can_register` confirma criação funcional e autenticação em SQLite descartável; demais testes preservam rate limiting e fluxos existentes. Os seis testes novos validam as regras de backend sem modificá-las.
- Browser: campo intocado neutro; envio obrigatório; erro corrigido; formato de e-mail; limites; senha curta; limite mínimo válido; confirmação após mudança de senha; mensagens de servidor; foco vermelho com borda de 1px; associação ARIA; confirmação Fortify; mostrar/ocultar; reflow dos erros a 320px; formulário nativo sem JavaScript; smoke do login. [Resultado](browser-after.txt), [obrigatórios](after/register-required.png), [obrigatórios a 320px](after/register-required-320.png), [erro de servidor](after/register-server-error.png).
- O estado de duplicidade no browser usa mensagem sintética em rota exclusiva do harness temporário; o POST real com e-mail duplicado é verificado por PHPUnit. A prévia HTTP usa sessão array e não comprova persistência entre requisições; o teste funcional isolado comprova criação/autenticação.
- `npm run build`, `git diff --check` e Pint no teste novo passaram.
- Os novos testes de apresentação e de browser falharam antes da implementação; evidências em [server-red.txt](server-red.txt) e [browser-red.txt](browser-red.txt).

Backend, rotas da aplicação, hashing, Fortify/Jetstream, CSRF, rate limiting, banco, migrations e `.env` permaneceram intactos. Nenhuma dependência, ilustração ou commit foi criado.

## Revisão independente e limite conhecido

Revisão de código somente leitura não encontrou problemas críticos ou importantes. A revisão visual e a execução dos testes foram feitas pelo implementador; o revisor independente avaliou o código.

Observação menor mantida: `maxlength` nativo mede unidades UTF-16, enquanto Laravel e a verificação JS medem pontos de código. Caracteres fora do BMP, como alguns emojis, podem atingir o limite nativo antes do limite do backend. Os limites permanecem seguros; essa diferença afeta a capacidade máxima desses valores excepcionais.

## Reexecução

O script [verify.mjs](verify.mjs) requer uma prévia **já isolada** em `127.0.0.1:19118` e Chrome CDP em `:19398`. Execute `node docs/design/reviews/2026-10-26-shared-auth/verify.mjs before` antes de alterar o código e `... after` depois do build. Os cenários de mensagens de servidor requerem `/__auth-errors` exclusivamente no harness, retornando `auth.register` com `ViewErrorBag` sintético (`?kind=confirmation` para erro `confirmed`). Essa rota não foi adicionada à aplicação.
