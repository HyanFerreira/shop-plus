# ShopPlus — controles de 40px e ajuste fino do login

25/10/2026, America/Sao_Paulo. Composição anterior preservada; revisão de contratos visuais e dos pontos marcados pelo usuário. Nenhum commit criado.

## Antes e depois

[Comparação lado a lado](comparison.html). Capturas reais, mesma viewport CSS, DPR1 e zoom 100%. Os arquivos de antes foram preservados da entrega anterior, correspondente ao estado inicial desta revisão.

| Viewport | Antes | Depois |
| --- | --- | --- |
| Desktop 1440×900 | [Antes](before/login-1440.png) | [Depois](login-1440.png) |
| Mobile 375×900 | [Antes](before/login-375.png) | [Depois](login-375.png) |

Outras capturas: [320px](login-320.png), [768px](login-768.png), [1024px](login-1024.png), [1280px](login-1280.png), [reflow equivalente a 200%](login-reflow200.png).

## Contratos e medidas finais

| Categoria | Contrato e dimensão real verificada |
| --- | --- |
| Input de uma linha / select | **40px**; fonte 16/24px; borda incluída no cálculo |
| Botão tradicional | **40px** nas variantes primary/secondary/danger/success e tamanhos sm/md/lg; md/lg com fonte 16px, sm mantém texto compacto de 14px |
| Checkbox | **16×16px**, mesmas cores, borda e foco |
| Radio | **16×16px**, preservado |
| Link textual no login | Fonte **16px**, altura natural **24px**, sem altura mínima de botão |
| Lembrar de mim | Fonte **16px**, linha natural **24px**, label clicável |
| Títulos do login | **32/40px no desktop**, 24/32px no mobile/tablet de formulário único |
| Texto, descrição, labels, campos, botão, links e feedback do login | **16/24px** |
| Benefícios | Título **16/24px**, descrição **14/20px** |
| Marca e ícones dos benefícios | Proporções anteriores preservadas; blocos de ícones **48px** |
| Busca por ícone / navegação | Alvos próprios de **44px**, preservados |
| Quantidade | Input de **40px** centralizado com os botões especiais existentes de **44/48px** |
| Textarea / conteúdo ampliado | Sem altura rígida de 40px; probe com textarea **90px**, texto ampliado **64px** e botão com texto em múltiplas linhas **160px**, sem corte |

O padding vertical é calculado por `(altura - line-height) / 2 - borda`: 7px com controle de 40px, linha de 24px e borda de 1px. Assim, altura mínima de 40px também resulta em **40px visuais reais**, sem virar 42px/50px pela soma de conteúdo, padding e borda. Não há `height` rígido que corte texto.

Tokens `--ds-control-sm/md/lg` passam a 2.5rem. Os novos tokens semânticos `--ds-target-sm/md/lg` preservam as medidas antigas de 36/44/48px exclusivamente nos consumidores especiais. Isso evita reduzir automaticamente navegação, ícones, escolhas e paginação. O token legado `--ds-text-hero` passa a ser alias de `--ds-text-3xl`, com 32px; sua entrelinha acompanha 40px. Home é o outro consumidor ativo identificado que ultrapassava esse teto; sua estrutura não foi redesenhada.

O selo Loja Online continua com 14px como badge informativo, fora da regra de texto comum, e a marca conserva suas proporções. Labels globais permanecem com o contrato anterior; o login usa explicitamente 16px.

## Espaçamento e decoração

Removidos os mínimos de 44px de `.auth-link` e `.auth-remember`, corrigindo a causa da linha alta. Classes explícitas de links textuais nas telas consumidoras também passam a altura natural e fonte de 16px; itens de navegação continuam com seus alvos próprios.

Ritmo real do formulário: **16px** entre senha e opções; **16px** entre opções e Entrar; **24px** entre Entrar e cadastro. Não foram usados margens negativas ou posicionamento para disfarçar o excesso de altura. O toggle e os ícones internos mantêm o posicionamento próprio dos campos. O botão isolado de busca do header permanece centralizado e com 44×44px contra o campo de 40px.

A partir de 375px, lembrar/recuperação ficam na mesma linha com centros alinhados. Em 320px, a recuperação segue para a linha seguinte com altura natural de 24px e gap de 8px, evitando compressão. No mobile de 375–767px, o padding horizontal interno usa 16px para acomodar os textos de 16px.

Removidos os pseudoelementos externos `.auth-page--login::before` e `::after` e o oval superior `.auth-benefits::before`. As três propriedades `content` são `none` nas seis larguras. Preservados fundo claro, card e arredondamentos, painel navy, painel branco, curva inferior não marcada, logo, benefícios e `public/images/login.png`. Nenhum elemento funcional foi removido; nenhuma decoração nova foi criada.

## Arquivos desta revisão

- `resources/css/app.css`: tokens de controles/alvos/títulos; geometria de botões e campos; classe `ds-field` para geometria de campos legados; exceções de textarea/arquivo/múltipla escolha; preservação da navegação e busca por ícone.
- `resources/css/auth.css`: tipografia, controles, links naturais, espaçamentos e remoção das decorações marcadas.
- `resources/css/account.css`, `cart.css`, `catalog.css`, `checkout.css`, `home.css`, `orders.css` e `product-detail.css`: migração dos alvos especiais aos tokens próprios, links textuais e alinhamento mínimo de quantidade. Sem redesenho das páginas.
- `resources/views/livewire/admin/{audit-manager,catalog-manager,order-manager,supply-manager,user-manager}.blade.php`: apenas opt-in da classe de geometria `ds-field` nos inputs/selects legados. Bindings, eventos, validações, larguras e pintura existentes preservados.
- `docs/design/design-system.md`: novos contratos, teto de títulos e exceções documentados.
- `public/build/manifest.json` e CSS `app-Dzm4pZ3Q.css`: build atualizado; bundle JavaScript igual.
- Plano e este diretório: comparação, capturas, medições e executores.

Alterações Blade do login e de seus componentes da entrega anterior continuam no checkout; esta revisão não modificou novamente os comportamentos desses componentes.

## Validação

| Verificação | Resultado |
| --- | --- |
| Build final | Aprovado, Vite 6.4.5, 58 módulos — [saída](build.txt) |
| Gates de isolamento existentes | Aprovados — [conexão](isolation.json), [bootstrap](bootstrap-isolation.json), [dotenv](dotenv-isolation.json) |
| Suíte PHP 8.4.27 / PHPUnit | **262 aprovados, 7 ignorados, zero falhas; 1484 assertions** — [saída](phpunit.txt), [JUnit](phpunit.xml) |
| Login em 320/375/768/1024/1280/1440px | Medidas, tipografia, checkbox, links, alinhamento, espaçamento e remoção das decorações aprovados, sem overflow horizontal |
| Componentes Blade reais | Inputs/selects normais e legados, todas as variantes/tamanhos de botão, disabled/readonly, link com aparência de botão e exceções aprovados |
| Texto ampliado / zoom | Texto ampliado expande sem corte; reflow 640×450 CSS / DPR2 sem overflow; não é zoom manual do menu do navegador |
| Teclado e estados | Enter no toggle, nome acessível, aria-pressed, foco visível, loading, checkbox, validação nativa, erros associados e fallback sem JS aprovados |
| Movimento reduzido | Transições dos controles desativadas conforme preferência |
| Contraste dos pares de tokens usados | Botão 5.17:1; hover 6.70:1; corpo 7.58:1; placeholder 4.76:1; erro 4.83:1; logo grande 3.36:1 — limites 4.5:1 para texto normal e 3:1 para marca grande |
| Consumidores reais | **26 combinações** de rotas comerciais/conta/admin em 375/1440px, sem novas regressões; ressalva de overflow administrativo preexistente abaixo |
| Revisão independente | Detectou a necessidade de preservar 44×44px na busca por ícone; correção aplicada e medida no navegador |
| `git diff --check` | Aprovado |

Evidências: [medidas e checks do login/componentes](login-layout.json), [executor](verify.mjs), [consumidores](consumers.json), [executor dos consumidores](consumers.mjs).

Os consumidores conferidos foram Home, Catálogo, Produto, Carrinho preenchido, Checkout preenchido, Dashboard, Meus Dados, Perfil e administração de catálogo/abastecimento/pedidos/auditoria/usuários. Além das alturas, foram conferidos títulos ≤32px, ausência de novos overflows, preservação da busca/navegação e alinhamento dos controles especiais de quantidade. No mobile, botões com textos longos podem crescer por quebra de linha, conforme a exceção de legibilidade solicitada.

Suíte e previews usam SQLite `:memory:` com as proteções existentes e `.env.testing` sintético. O harness externo temporário da Etapa 8 foi estendido somente fora do projeto com uma rota de prova dos componentes e fixtures fictícias em memória. Não foram acrescentadas rotas de preview à aplicação. Não houve execução contra banco persistente nem alterações em autenticação, Fortify/Jetstream, validações, CSRF, rate limiting, sessão, policies, middlewares, migrations, `.env`, dependências ou regras comerciais.

## Impactos e limites

Os formulários consumidores ficam mais compactos; links textuais deixam de reservar espaço de botão; títulos do hero da Home ficam em 32px. Ícones, marca, badges, chips, radios, textareas, escolhas e navegação não recebem indiscriminadamente a altura de 40px. O ajuste visual dos campos administrativos se limita à geometria.

Foi observado **overflow de 2px em `/admin/pedidos` a 375px**. A comparação com o CSS compilado anterior de HEAD reproduziu exatamente os mesmos 2px (client360/scroll362), com larguras dos campos iguais; somente suas alturas mudaram de 42px para 40px. Não foi introduzido por esta revisão e não foi corrigido por exigir trabalho fora dos contratos solicitados. [Prova antes/atual](admin-orders-baseline.json), [executor](compare-admin-baseline.mjs). Os demais consumidores medidos e o login não apresentaram overflow.

Validação em Chrome headless, sem dispositivo físico, Safari/Firefox, leitor de tela ou auditoria WCAG integral. Contraste calculado para pares de tokens usados, não análise de cada pixel da composição. Nenhuma VM/container adicional foi provisionada; o isolamento utilizado foi o guard de testes existente.
