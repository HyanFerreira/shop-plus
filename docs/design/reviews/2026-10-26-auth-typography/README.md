# Login e cadastro: padronização tipográfica — 26/10/2026

## Causa medida antes da alteração

As duas páginas já usavam Figtree, peso 600 nos botões e links, peso 400 no texto inferior e letter-spacing normal. Não havia classes Tailwind conflitantes. A diferença estava na cascata CSS:

- O botão e os inputs do login herdavam 14px/20px dos componentes globais `.ds-button` e `.ds-input`.
- O bloco inferior compartilhado e seu link declaravam 14px/20px em `auth.css`.
- Uma regra exclusiva `.auth-page--register :is(...)` corrigia esses elementos somente no cadastro para 16px/24px. Outra regra exclusiva ajustava o padding vertical dos controles para mantê-los em 40px.
- Labels, descrições e links auxiliares já computavam 16px nas duas páginas. Títulos respeitavam o máximo de 32px. Mensagens individuais e requisitos computavam 14px.
- A regra genérica de feedback ainda usava 16px; foi alinhada a 14px para incluir mensagens fora dos componentes individuais.

Valores completos de `getComputedStyle()` antes/depois, incluindo família, tamanho, peso, line-height, letter-spacing, classes, padding e geometria: [antes](before/computed.json) / [depois](after/computed.json).

## Correção na origem

Alterado apenas `resources/css/auth.css`, além do build e evidências. A tipografia de input/botão agora é compartilhada em `.auth-page--branded`, com 16px/24px e padding vertical de 7px para preservar altura de 40px. O bloco inferior e seus links usam 16px/24px nas regras compartilhadas existentes. Removidas as duas regras tipográficas exclusivas do cadastro. Não há `!important` novo.

Figtree, peso 600 nos botões e links, peso 400 nos textos normais, ícones, gaps, cores, gradientes, bordas, larguras e arredondamentos foram preservados. Requisitos permanecem em 14px/20px e não receberam alterações. Backend, Blade, JavaScript, validações e painel institucional não foram editados neste ajuste.

## Verificação

- Chrome headless, DPR1, viewport de mesma largura/altura em ambas as páginas; fontes carregadas antes de medir/capturar.
- Seis larguras: 320, 375, 768, 1024, 1280 e 1440px, com altura de 1000px.
- Igualdade exata de font-family, font-size, font-weight, line-height e letter-spacing nos botões, textos inferiores e links entre as duas páginas.
- Padding, gap e alinhamento dos botões idênticos; inputs e botões de 40px.
- Textos esperados em 16px, mensagens/requisitos em 14px, títulos até 32px e ausência de overflow horizontal.
- Cadastro: estilos computados e geometria coletados integralmente preservados. Cards desktop, larguras e painel institucional preservados.
- Login mobile: a mudança de 14px/20px para 16px/24px aumenta naturalmente a altura do footer e do card em 28px a 320px (quebra em duas linhas) e 4px a 375/768px. **O usuário autorizou manter 16px e permitir esse crescimento natural.** Nenhuma compensação de espaçamento foi aplicada.
- [Comparação de geometria](geometry-comparison.json), [resultado do browser](browser.txt), [script](verify.mjs).
- `npm run build` passou. Testes pertinentes em cópia protegida exclusivamente SQLite `:memory:`: **31 testes, 197 assertions, 1 ignorado, nenhuma falha**. [Resultado](tests.txt). `git diff --check` passou.

## Capturas

| Tela | Desktop antes/depois | Mobile antes/depois |
| --- | --- | --- |
| Login | [Antes](before/login-1440.png) / [Depois](after/login-1440.png) | [Antes](before/login-375.png) / [Depois](after/login-375.png) |
| Cadastro | [Antes](before/register-1440.png) / [Depois](after/register-1440.png) | [Antes](before/register-375.png) / [Depois](after/register-375.png) |

Sem testes em banco persistente, alterações de `.env`, dependências ou commits.
