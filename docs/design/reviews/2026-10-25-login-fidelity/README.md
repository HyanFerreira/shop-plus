# ShopPlus — correção da fidelidade visual do login

25/10/2026, America/Sao_Paulo. Escopo exclusivamente visual. Trabalho anterior preservado, nenhum commit criado.

## Comparação visual

[Abrir comparação antes/depois lado a lado](comparison.html).

| Condição | Antes | Depois |
| --- | --- | --- |
| Desktop equivalente à área de página da referência, 1672×843 CSS | [Antes](before/login-1672.png) | [Depois](login-1672.png) |
| Desktop 1440×900 CSS | [Antes](before/login-1440.png) | [Depois](login-1440.png) |
| Mobile 375×900 CSS | [Antes](before/login-375.png) | [Depois](login-375.png) |

As capturas são reais do Chrome headless, DPR1, zoom 100%. A referência anexada tem 1672×941px incluindo aproximadamente 98px de interface do navegador; a comparação principal usa a área de página 1672×843. Conferidas as dimensões efetivas dos PNGs antes/depois, sem redimensionar ou editar as capturas. Na captura neutra, o campo é desfocado; foco por teclado é validado separadamente. A referência foi comparada visualmente com a renderização real; não foi usada como fundo ou recortada para construir a página.

## Diferenças corrigidas

| Elemento | Correção |
| --- | --- |
| Card | Divisão 48%/52%, largura máxima local de 1184px, raio externo 48px e transição de 40px à esquerda do formulário; sombra ampla e difusa derivada dos tokens |
| Fundo | Superfície azul muito clara, iluminação sutil, círculo parcial superior esquerdo e curva inferior direita; pseudoelementos não interativos, contidos na página |
| Painel navy | Gradientes azul/navy, curvas sobrepostas atrás do conteúdo, linha de luz e iluminação inferior |
| Marca | Logo maior, destaque de Plus e selo informativo Loja Online com carrinho; o selo não é um link |
| Hierarquia | Título institucional e boas-vindas em 44/52px no desktop amplo, destaque azul nas palavras finais e descrições com maior presença |
| Benefícios | Ícones em blocos de 48px, títulos 18/26px, subtítulos 14/20px e alinhamento consistente; funcionalidades reais preservadas |
| Ilustração | Uso direto de `public/images/login.png`, fornecido pelo usuário: duas sacolas, celular e plataforma com luz/sombra; substitui as sacolas vetoriais simples somente no login |
| Formulário | Envelope/cadeado internos, campos de 56px mínimos, labels mais fortes, toggle por ícone com o comportamento acessível existente, ícone de entrada e botão com gradiente primário/hover |
| Cadastro | Superfície azul clara e ícone no desktop; versão simples e legível no mobile |

Feita uma segunda passagem após inspecionar as capturas reais: suavizada a curva superior, ampliados os títulos dos benefícios, ajustados o respiro do formulário, sua largura e posição vertical, a altura total do card e o bloco de cadastro mobile. As capturas finais foram novamente verificadas. A composição permanece reconhecível em relação à referência: marca e selo no topo esquerdo, título em duas linhas, benefícios alinhados, arte inferior, painel direito arredondado e formulário na mesma região visual.

## Arquivos e componentes

- `resources/css/auth.css`: acabamentos e dimensões locais sob `.auth-page--login`, além de classes auxiliares exclusivas do login. Nenhum token global alterado.
- `resources/views/auth/login.blade.php`: títulos com destaques, campo com envelope, variante de senha com ícones, ícone de entrada e cadastro.
- `resources/views/components/auth-shell.blade.php`: slots opcionais `heading`/`intro` e logo com destaque somente quando `login` é verdadeiro.
- `resources/views/components/auth-benefits-panel.blade.php`: marca/selo, mensagem, benefícios detalhados e asset fornecido na variante de login; ramo original mantido para cadastro.
- `resources/views/components/auth-password.blade.php`: propriedade `icons=false`; login ativa a variante visual. Tipo, autocomplete, validações, erros, nome acessível, `aria-pressed`, `aria-controls` e handlers Alpine existentes preservados.
- `resources/views/components/shop-logo.blade.php`: propriedade `accented=false`; o logo original permanece como padrão para todo o site.
- `resources/views/components/login-icon.blade.php`: ícones SVG locais e decorativos para olho, entrada e cadastro.
- `public/build/manifest.json` e CSS compilado `app-BkcV7Hww.css`: resultado do build. JavaScript compilado permanece igual.
- `public/images/login.png`: asset preexistente fornecido pelo usuário, usado sem alteração. SHA-256 `f99525fa6fde0d3398bba22436ad65fef297ef9b4ffb64baf655ca3dd1a9fb0f`.
- Plano e este diretório: documentação, executor de verificação e evidências.

Continuam reutilizados os componentes de formulário e feedback: `x-auth-form`, `x-auth-feedback`, `x-label`, `x-input`, `x-input-error`, `x-checkbox`, `x-auth-password`, `x-auth-submit` e seu `x-button`. As propriedades novas são exclusivamente de apresentação e têm padrão desativado. A ilustração antiga permanece nos outros consumidores.

## Tokens e identidade preservados

Paleta: `--ds-brand`, `--ds-brand-strong`, `--ds-brand-active`, `--ds-brand-soft`, `--ds-brand-line`, `--ds-navy`, `--ds-surface`, `--ds-canvas`, `--ds-ink`, `--ds-secondary` e `--ds-muted`. Gradientes e sombras usam esses canais com transparência; não há nova paleta. Figtree, pesos semibold/bold e escala existente xs–hero permanecem globais. Espaços de 8/12/16/20/24/32/40/48/64px e seus múltiplos definem a composição local. Raios maiores consomem os tokens de espaço somente neste login, conforme autorização do usuário.

Inputs mantêm borda visível, foco e estados de erro existentes. No desktop amplo, fonte local de 18/26px e ícones com espaço de 64px; abaixo de 1280px, fonte 16/24px e espaço de 48px. Botão mantém a base primária, foco, disabled e loading, com acabamento local. Todos os tokens em `resources/css/app.css` e os padrões globais de interação permanecem sem diff.

## Build, testes e navegador

| Verificação | Resultado |
| --- | --- |
| Build final Vite 6.4.5 | Aprovado, 58 módulos — [saída](build.txt) |
| Gates existentes de isolamento | Todos aprovados — [conexão](isolation.json), [bootstrap](bootstrap-isolation.json), [dotenv/cache](dotenv-isolation.json) |
| Suíte completa PHP 8.4.27 / PHPUnit | **262 aprovados, 7 ignorados, zero falhas; 1484 assertions** — [saída](phpunit.txt), [JUnit](phpunit.xml) |
| Login real, larguras CSS | **320/375/768/1024/1280/1440/1672px aprovadas**, sem overflow horizontal, colunas48/52 a partir de1024, controles dentro do painel, imagem carregada e um único h1 |
| Estados e acessibilidade do login | Teclado Enter, mostrar/ocultar senha, label dinâmico, aria-pressed, foco visível, checkbox, validação nativa e carregamento aprovados; nenhum submit HTTP enviado pelo harness |
| Erros sintéticos 375/1440px | Erros associados aos campos e sem overflow — [mobile](login-error-375.png), [desktop](login-error-1440.png) |
| Sem bundles JavaScript | Campo nativo de senha e envio continuam utilizáveis; toggle inativo oculto |
| Reflow equivalente a200% | 640×450 CSS / DPR2, sem overflow — [captura](login-reflow200.png) |
| Cadastro, redefinição e recuperação | Variante padrão preservada, sem arte/marca especial do login; controle textual Mostrar/Ocultar mantido |
| Home, Catálogo, Dashboard e Perfil | Oito combinações 375/1440px aprovadas, sem vazamento do shell ou overflow — [smoke](storefront-smoke.json) |
| Revisão independente do diff | Nenhum problema concreto encontrado |
| `git diff --check` | Aprovado |

Executor e medições: [verify-login.mjs](verify-login.mjs), [login-layout.json](login-layout.json). Capturas adicionais: [320px](login-320.png), [768px](login-768.png), [1024px](login-1024.png), [1280px](login-1280.png).

Suíte e previews usam as proteções existentes e SQLite `:memory:` com `.env.testing` sintético. O harness temporário externo da Etapa 8 recria o banco em memória por requisição, sem rotas de preview adicionadas ao projeto. OPcache foi desativado apenas no comando do servidor temporário para evitar a falha ASLR do PHP local. Não foi provisionada VM/container adicional. Não houve teste ou escrita no banco persistente de desenvolvimento, alteração de `.env`, rotas, autenticação, Jetstream/Fortify, regras, CSRF, sessões, rate limiting, policies, hashing, migrations ou dependências.

## Diferenças ainda perceptíveis

Os azuis seguem os tokens ShopPlus, sem copiar o ciano elétrico da referência. As curvas são reconstruídas em CSS, sem equivalência pixel a pixel. Textos e ícones dos benefícios continuam descrevendo recursos reais. O PNG indicado pelo usuário possui símbolo de sacola na tela do celular e pequenos raios decorativos; foi preservado, embora a referência mostre um carrinho no celular. O asset original determina esses detalhes.

O painel institucional é ocultado abaixo de1024px para priorizar o formulário. No mobile de320px, lembrar acesso e recuperação podem ocupar linhas separadas, preservando áreas clicáveis e legibilidade. Validação em Chrome headless, sem dispositivos físicos, Safari/Firefox ou leitor de tela; reflow200% é emulado, não zoom manual do menu.
