# Cadastro: requisitos de senha e espaçamento — 26/10/2026

A tela preserva os painéis e a largura do formulário aprovado. O novo card fica entre a confirmação e o botão, com o mesmo gap de **16px** dos outros grupos. Sua altura vem do conteúdo; nenhuma altura fixa ou margem de compensação foi adicionada. Inputs e botão continuam com **40px**.

## Política confirmada no servidor

No cadastro, Laravel/Fortify exige:

- De **8 a 128 caracteres**.
- Pelo menos uma letra minúscula e uma maiúscula, cobrindo também a exigência de uma letra.
- Pelo menos um caractere especial, conforme `Password::symbols()` do Laravel.
- Confirmação idêntica à senha.

**Números não são obrigatórios.** Uma senha válida sem dígitos é aceita e testada diretamente no endpoint. A política anterior só exigia tamanho e confirmação; o cadastro agora aplica `letters()->mixedCase()->symbols()` sobre uma cópia de `Password::default()`. Os defaults são preservados, e a cópia impede que essa configuração altere os fluxos de atualização/redefinição. Contas existentes continuam usando suas senhas atuais para entrar.

As expressões locais usam as categorias Unicode do Laravel: `Ll` e `Lu` para caixa das letras, `Z/S/P` para separadores, símbolos e pontuação. Por definição do framework, espaços também pertencem ao conjunto de `symbols()`. Não há requisito adicional de número ou consulta externa. O backend continua autoritativo mesmo sem JavaScript.

## Comportamento e apresentação

O card tem cinco itens, evitando repetir “uma letra” além de minúscula/maiúscula. Itens pendentes usam texto neutro e círculo discreto; itens atendidos recebem check e a cor `--ds-success-strong`. O estado acessível “Pendente/Atendido” acompanha cada item. A avaliação ocorre localmente em eventos de edição, mudança, blur e envio. O card não armazena nem anuncia valores das senhas.

A confirmação só fica verde com os dois valores preenchidos e exatamente iguais; editar qualquer senha recalcula esse estado. As mensagens de campo mantêm o componente compartilhado de erros, com comportamento após interação/envio. Simples foco e blur em campos intocados não criam erros. O card não transforma pendências em alertas vermelhos.

CSS exclusivo de cadastro usa `--ds-brand-soft`, `--ds-brand-line`, raio e espaçamentos existentes. Título/itens auxiliares de 14px, duas colunas a partir de 640px e uma coluna em telas menores. Campos, labels e botão preservam as dimensões e tipografia anteriores. O painel institucional, benefícios e `images/login.png` não foram editados.

## Dimensões e responsividade

| Viewport CSS | Altura natural do card principal | Altura do card de requisitos |
| --- | ---: | ---: |
| 320×640 | 908px | 190px |
| 375×812 | 840px | 170px |
| 768×900 | 824px | 122px |
| 1024×768 | 780px | 162px |
| 1280×720 | 740px | 122px |
| 1440×800 | **740px** | 122px |
| 1440×600 | 740px | 122px |

No desktop largo, a altura principal passou de 640px para 740px. As larguras foram preservadas: formulário de até 416px e a mesma proporção das colunas. Centralização quando cabe na viewport, rolagem vertical natural quando necessário, sem overflow horizontal. A ilustração mantém suas dimensões proporcionais e acompanha a base do painel, sem corte.

## Capturas reais

| Estado | Desktop | Mobile |
| --- | --- | --- |
| Neutro | [1440×800](after/register-neutral-1440x800.png) | [375×812](after/register-neutral-375x812.png) |
| Parcialmente atendido | [PNG](after/register-partial-1440.png) | [PNG](after/register-partial-375.png) |
| Integralmente atendido | [PNG](after/register-complete-1440.png) | [PNG](after/register-complete-375.png) |
| Erros abaixo dos campos | [PNG](after/register-errors-1440.png) | [PNG](after/register-errors-375.png) |

As capturas de estado completo usam viewport desktop de 1440×900; a altura do card continua 740px. Há também capturas neutras nas sete viewports da tabela e [geometria detalhada](after/layout.json).

O login foi capturado novamente antes e depois, na mesma viewport, DPR1 e com fontes carregadas. **Sete comparações com zero pixels alterados.** [Resultado](login-pixel-comparison.json), [antes 1440×800](before/login-1440x800.png), [depois 1440×800](after/login-1440x800.png).

## Arquivos deste ajuste

- `app/Actions/Fortify/PasswordValidationRules.php`: opção de política de cadastro, preservando defaults e demais consumidores.
- `app/Actions/Fortify/CreateNewUser.php`: solicita a política de cadastro na validação existente.
- `resources/views/auth/register.blade.php`: novo card entre confirmação e botão.
- `resources/js/auth-validation.js`: avaliação local compartilhada entre os indicadores e a validação do campo de senha; comportamento do login preservado.
- `resources/css/auth.css`: estilos exclusivos do novo card no cadastro.
- `tests/Feature/RegistrationPasswordPolicyTest.php`: 13 casos novos, incluindo requisições diretas inválidas e válidas, fronteiras de tamanho, Unicode, ausência de dígitos obrigatórios, hash persistido e autenticação.
- `tests/Feature/RegistrationTest.php` e `tests/Feature/UserRoleAndStatusTest.php`: duas fixtures de cadastro bem-sucedido atualizadas para a nova política; assertions preservadas.
- `tests/Feature/RegistrationPresentationTest.php`: seleção dos itens limitada ao painel institucional, para não confundir seus três benefícios com os cinco requisitos do novo card.
- `public/build/manifest.json`, assets gerados, plano e este diretório de evidências.

## Testes e build

Foi utilizada a cópia descartável protegida em `/tmp/shopplus-shared-auth/app`, exclusivamente SQLite `:memory:`, `.env.testing` e guards existentes. O cache local do checkout original e `.env` foram preservados. Nenhum banco persistente foi acessado pelos testes.

- Baseline: **281 testes, 1607 assertions, 7 ignorados**.
- Final: **294 testes, 1672 assertions, os mesmos 7 ignorados**, nenhuma falha. Todos os casos anteriores preservaram o resultado. [Comparação](suite-comparison.json), [baseline](baseline.txt), [resultado final](final.txt).
- Os testes diretos rejeitam senha vazia, curta, sem minúscula, sem maiúscula, sem letras, sem caractere especial, longa demais e confirmação ausente/divergente. Senhas válidas de 8/128 caracteres e Unicode persistem com hash e autenticam.
- Browser: todos os estados solicitados, correção ao digitar, erro após blur/envio, alteração após confirmação válida, Unicode e correspondência com símbolos do Laravel, layouts desktop/mobile, controles de 40px, gaps de 16px e ausência de overflow. [Resultado](browser-after.txt), [script](verify.mjs).
- Os novos testes falharam antes da implementação: [backend/apresentação](policy-red.txt), [browser](browser-red.txt). Uma fixture antiga de cadastro com `password` foi identificada pela suíte completa e atualizada para uma senha válida, sem alterar a verificação de papéis/permissões.
- `npm run build` passou (Vite 6.4.5); `git diff --check` e Pint nos PHP modificados passaram.
- Revisão independente somente leitura não encontrou problemas. Testes e avaliação visual foram executados pelo implementador; o revisor independente avaliou o código.

Nenhuma dependência, migration, alteração de `.env`, mudança comercial, commit ou publicação foi realizada. O painel compartilhado e a interface do login permaneceram intactos neste ajuste.

O script de browser requer o harness isolado em `127.0.0.1:19118` e Chrome CDP em `:19398`, conforme a revisão anterior. Execute `node docs/design/reviews/2026-10-26-password-requirements/verify.mjs` após o build e sincronização da cópia descartável. Nunca aponte os testes para banco persistente.
