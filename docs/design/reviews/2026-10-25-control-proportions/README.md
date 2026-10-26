# ShopPlus — proporções, raios e bordas dos controles

25/10/2026, America/Sao_Paulo. Revisão visual solicitada pelo usuário, incorporando o ajuste posterior de labels600 e a referência de borda do portal. Sem commits.

## Resultado visual

| Estado do campo | Captura real |
| --- | --- |
| Normal: borda cinza suave, label600 | [Campo normal](field-normal.png) |
| Clique: borda azul com aparência de2px, sem outline externo | [Foco por clique](field-pointer-focus.png) |
| Tab: borda1px + outline1px, offset0 | [Foco por teclado](field-keyboard-focus.png) |

Capturas completas: [desktop1440px](login-1440.png), [mobile375px](login-375.png), [foco por teclado mobile](login-keyboard-focus-375.png). Antes preservado: [desktop](before/login-1440.png), [mobile](before/login-375.png). Mesma viewport, DPR1 e zoom100%, sem editar/redimensionar imagens. [Estados e medidas](field-states.json).

## Contratos finais

- Campos/selects/botões tradicionais mantêm **40px** reais, com texto **14/20px** e ícones **20px**, proporcionais à altura. Textos longos ou ampliados podem expandir, sem corte.
- Superfícies e controles retangulares usam **8px**, via tokens e `rounded-lg`. O card principal e a transição dos painéis do login preservam **32/48/40px**. Círculos, avatares, badges e checkbox pequeno mantêm geometria própria.
- Prefixo do login: ícone **20×20px**, a **12px** da esquerda; padding do texto **40px**, com intervalo visual próximo de **8px**. Mesmo contrato em todos os breakpoints, removendo a antiga distância maior no desktop.
- Toggle de senha: ícone20px, área interna32px e padding reservado40px, mantendo os nomes acessíveis e handlers existentes.
- Botão Entrar: ícone20px e gap8px, reduzido de12px. Marca, títulos, parágrafos, benefícios e ilustração permanecem na hierarquia anterior.
- Labels: **peso600**, distância inferior **4px**. Login conserva fonte16px; labels compartilhados mantêm fonte14px. Margens explícitas dos consumidores podem acrescentar seu próprio espaço.
- Borda neutra: `rgb(var(--ds-control-line) / .4)`, aproximadamente#C1C7D1 sobre branco; hover com alpha.65. A paleta-base não foi substituída.
- Foco por clique: borda1px + reforço interno1px, sem outline externo. Foco por Tab/Shift+Tab: borda1px + outline1px colado, **offset0**, sem reforço interno acumulado. Aparência de2px em ambos, sem alterar largura/altura ou deslocar conteúdo.

Foi necessário registrar apenas a modalidade Tab/ponteiro porque navegadores também consideram um input clicado como `:focus-visible`. O módulo não cancela eventos, não lê valores dos campos e não altera submissão ou validações. Sem JavaScript, permanece o fallback nativo acessível com outline1px/offset0.

## Arquivos

- `resources/css/app.css`: raios, foco, tipografia/geometria dos controles, ícones, gap de label, borda suave e auxiliares `ds-action`/`ds-focus` para controles legados.
- `resources/css/auth.css`: prefixos, padding do texto, toggle, ícones/gaps, labels600 e remoção das antigas sobrescritas maiores no desktop. Cards/painéis do login preservados.
- `resources/css/account.css` e `orders.css`: removida a troca de raio ao receber foco, evitando que botões mudassem de8px para4px no Tab.
- `resources/js/app.js` e novo `focus-modality.js`: apresentação de foco conforme Tab/ponteiro.
- Componentes `banner`, `dropdown`, `form-section`, `modal`; `navigation-menu`, perfil, API tokens e templates administrativos: migração de classes retangulares para `rounded-lg`. Cores, eventos, bindings e regras preservados; ações legadas usam a tipografia compacta compartilhada.
- `docs/design/design-system.md`: contratos atualizados.
- `public/build/manifest.json`, CSS `app-jxJEv2ZK.css` e JS `app-BJxAfeoG.js`: build final.
- Plano e este diretório: evidências e executores.

## Verificação

| Verificação | Resultado |
| --- | --- |
| Build | Aprovado,59 módulos — [saída](build.txt) |
| Gates de isolamento existentes | Aprovados — [conexão](isolation.json), [bootstrap](bootstrap-isolation.json), [dotenv](dotenv-isolation.json) |
| Suíte protegida PHP8.4.27 / SQLite em memória | **262 aprovados,7 ignorados,zero falhas;1484 assertions** — [saída](phpunit.txt), [JUnit](phpunit.xml) |
| Login em320/375/768/1024/1280/1440px | Controles40px, texto14px, raio8px, ícones20px, inset12px, padding40px, label-gap4px, label600, cards preservados e nenhum overflow |
| Clique versus Tab/Shift+Tab | Verificados por eventos reais do Chrome: clique sem outline, Tab com outline1px/offset0, altura40px constante; retorno ao ponteiro remove outline externo |
| Estados existentes | Toggle de senha por Enter, aria-pressed/label dinâmico, loading, checkbox16px, validação nativa, erros associados e fallback sem JS aprovados |
| Componentes e reflow | Todas as variantes/tamanhos, campos/selects legados, readonly/disabled, textarea e expansão de texto aprovados; reflow640×450 CSS/DPR2 sem overflow |
| Consumidores comerciais/conta/admin | **26 combinações em375/1440px**, sem overflow nos resultados atuais, sem regressões de controles ou navegação |
| Revisão independente | Correções de raio residual aplicadas; contratos de foco documentados. Exceção de borda suave registrada abaixo |
| `git diff --check` | Aprovado |

Evidências: [login e componentes](login-layout.json), [executor](verify.mjs), [estados aproximados do campo](field-states.mjs), [consumidores](consumers.json), [executor](consumers.mjs).

Os controles mais compactos também deixaram de reproduzir o overflow administrativo de2px observado na revisão anterior: a medição atual de `/admin/pedidos` a375px é zero. O JSON preserva as medidas de todas as rotas, sem depender de uma supressão visual.

A suíte e os previews utilizam as proteções existentes e SQLite `:memory:` com `.env.testing` sintético. Nenhuma operação foi executada contra banco persistente. Não houve alteração em autenticação, CSRF, rate limiting, validações, sessões, policies, middlewares, migrations, `.env`, regras comerciais ou dependências. Mudanças de JavaScript limitadas à apresentação do foco.

## Limites

A borda neutra suavizada segue a escolha expressa do usuário e tem contraste aproximado de1.69:1 sobre branco, abaixo da antiga meta geral de3:1 para limites essenciais; o foco azul mantém5.17:1. Isso está documentado como exceção visual, sem alegar auditoria WCAG integral. Textos e estados de foco continuam com os contrastes medidos no relatório anterior.

Validação em Chrome headless, sem dispositivos físicos, outros navegadores ou leitor de tela. Zoom200% validado por reflow equivalente, não pelo menu manual do navegador. Sem JavaScript, o navegador decide quando aplicar `:focus-visible`; o outline continua1px e sem distância da borda.
