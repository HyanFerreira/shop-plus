# Design system — Fundação e estrutura compartilhada

Este sistema segue a referência oficial [ecommerce-ui-reference.png](ecommerce-ui-reference.png): azul nas ações, navy institucional, superfícies claras, contornos discretos e cantos arredondados. A imagem é direção visual, não fonte de medidas CSS, dados comerciais ou funcionalidades.

## Escopo e compatibilidade

Tokens em `resources/css/app.css` alimentam os componentes compartilhados. A Etapa 1 estabeleceu a fundação; a Etapa 2 consolidou header/footer e o container externo do storefront. A Etapa 3A refinou exclusivamente a Home e seus assets de demonstração. Fluxos, bindings existentes e regras de negócio permanecem preservados.

As cores `--ds-*` continuam em **canais RGB separados por espaços**, inclusive os tokens antigos. Consumir com `rgb(var(--ds-brand))` ou `rgb(var(--ds-brand) / 0.1)`; não usar `var(--ds-brand)` sozinho como cor. Raios continuam em rem e sombras continuam sendo valores CSS completos.

## Cores

| Token | Valor equivalente | Uso |
| --- | --- | --- |
| `--ds-brand` | #2563EB | Ação primária, seleção e foco |
| `--ds-brand-strong` | #1D4ED8 | Hover primário; nome legado preservado |
| `--ds-brand-active` | #1E40AF | Ação pressionada e texto informativo |
| `--ds-brand-soft / --ds-brand-line` | #EFF6FF / #BFDBFE | Fundo e borda informativos |
| `--ds-navy` | #0B173F | Áreas institucionais; disponível para migração posterior |
| `--ds-ink` | #0F172A | Texto principal, títulos e logo |
| `--ds-secondary` | #475569 | Texto secundário e labels |
| `--ds-muted` | #64748B | Ajuda, placeholder e metadados |
| `--ds-canvas` | #F8FAFC | Fundo da aplicação |
| `--ds-surface / --ds-surface-alt` | #FFFFFF / #F1F5F9 | Superfície e superfície alternativa |
| `--ds-line` | #E2E8F0 | Borda estrutural de cards/painéis |
| `--ds-control-line` | #64748B | Limite visível de campos e botões secundários |
| `--ds-success` | #059669 | Token legado preservado |
| `--ds-success-strong / --ds-success-active` | #047857 / #065F46 | Botão de sucesso e texto de estado |
| `--ds-success-soft / --ds-success-line` | #ECFDF5 / #A7F3D0 | Superfície e borda de sucesso |
| `--ds-danger / --ds-danger-strong / --ds-danger-active` | #DC2626 / #B91C1C / #991B1B | Perigo, hover e active |
| `--ds-danger-soft / --ds-danger-line` | #FEF2F2 / #FECACA | Superfície e borda de perigo |
| `--ds-warning` | #92400E | Texto de aviso |
| `--ds-warning-soft / --ds-warning-line` | #FFFBEB / #FDE68A | Superfície e borda de aviso |

O botão de sucesso usa o tom #047857 para contraste do texto branco, mantendo o valor antigo de `--ds-success`. Bordas estruturais suaves não substituem o indicador de foco ou o limite de controles interativos.

## Tipografia

Figtree permanece como família principal, com fallback definido no Tailwind. `--ds-font-family` deriva de `theme('fontFamily.sans')`. Os layouts app e guest solicitam pesos **400, 500, 600, 700, 800 e 900** ao provedor já utilizado, com `display=swap`, cobrindo os pesos atuais das telas. O carregamento depende da disponibilidade do provedor.

Os componentes centrais usam `--ds-weight-regular/medium/semibold/bold` (400/500/600/700). Pesos 800/900 permanecem disponíveis para consumidores legados, sem serem o padrão da nova hierarquia.

| Par de tokens `--ds-text-* / --ds-leading-*` | Tamanho/entrelinha em rem | Equivalente com raiz 16px | Recomendação |
| --- | --- | --- | --- |
| `xs` | .75 / 1 | 12/16px | Badge e metadado secundário |
| `sm` | .875 / 1.25 | 14/20px | Label, erro, ajuda, botão, nome de produto |
| `base` | 1 / 1.5 | 16/24px | Corpo e campos |
| `lg` | 1.125 / 1.625 | 18/26px | Preço de card, texto de destaque |
| `xl` | 1.25 / 1.75 | 20/28px | Logo, preço ou subtítulo |
| `2xl` | 1.5 / 2 | 24/32px | Título de seção |
| `3xl` | 2 / 2.5 | 32/40px | Título de página e preço principal |
| `hero` | 2.75 / 3.25 | 44/52px | Hero desktop; reduzir para 3xl em telas menores |

A escala é reutilizável via propriedades CSS, por exemplo `font-size: var(--ds-text-2xl); line-height: var(--ds-leading-2xl)`. Não altera automaticamente os títulos de todas as páginas. **Não usar 9, 10 ou 11px em informação relevante**; ocorrências locais existentes ficam pendentes de migração. Preços e nomes importantes devem ter pelo menos 14px. Não reduzir fontes para preservar colunas.

## Geometria e interação

- Espaços `--ds-space-1/2/3/4/5/6/8/10/12/16`: 4/8/12/16/20/24/32/40/48/64px equivalentes, em rem.
- Raios `--ds-radius-xs/sm/md/lg`: 4/8/12/16px; `--ds-radius-pill`: 9999px.
- `--ds-border-width`: 1px.
- `--ds-shadow-control`: 0 1px 2px com ink a 4%.
- `--ds-shadow-card`: 0 1px 2px a 4% e 0 8px 24px a 5%; geometria legada preservada.
- `--ds-shadow-raised`: 0 8px 24px a 8%, disponível para superfícies elevadas.
- `--ds-control-sm/md/lg`: 36/44/48px equivalentes, como **altura mínima**, permitindo texto em múltiplas linhas.
- Foco: `--ds-focus-width` 2px, `--ds-focus-offset` 2px, azul principal.
- Transições: `--ds-duration` 150ms, `--ds-easing` ease-in-out; removidas em botões/campos com preferência por movimento reduzido.
- `--ds-disabled-opacity`: .6 em botões e checkboxes; campos desabilitados usam superfície alternativa e texto muted.

## Componentes

### Botões

Reutilize `x-button` para ações e seus aliases existentes. Não duplique cores de botão nas telas.

```blade
<x-button type="submit">Salvar</x-button>
<x-button variant="secondary">Voltar</x-button>
<x-button variant="danger">Excluir</x-button>
<x-button variant="success">Confirmar recebimento</x-button>
```

As quatro variantes são `primary` (azul), `secondary` (branco com borda), `danger` (vermelho) e `success` (verde escuro). Tamanhos: `sm` 36px, `md` 44px (padrão) e `lg` 48px mínimos. Texto: 14px em sm/md e 16px em lg; padding vertical/horizontal: 4/12, 8/16 e 12/20px respectivamente.

O tipo padrão de `x-button` continua `submit`; os aliases continuam `button`. Variante inválida mantém fallback primary; tamanho inválido mantém md. Atributos, eventos e bindings são encaminhados sem alterações.

`x-secondary-button`, `x-danger-button` e `x-success-button` continuam como aliases compatíveis. Botões têm hover/active específicos por variante, foco visível por teclado e estado nativo disabled. Sem crescimento de escala ou deslocamento no hover.

Para navegação, preserve `<a>` com `ds-button ds-button--primary` (ou outra variante); não transforme links em submits. `aria-disabled="true"` tem apresentação desabilitada, mas o consumidor continua responsável por impedir ativação pelo teclado e definir a semântica adequada.

### Formulários

Use `x-label`, `x-input` e `x-input-error for="campo"`. Para texto auxiliar, aplique `ds-help`.

- `x-input`: API `disabled=false` preservada; altura mínima 44px, texto 16/24px, padding 8/12px, largura padrão 100%, limite de 100% e min-width zero. Utilitários como `w-20` continuam podendo sobrescrever a largura.
- Hover destaca a borda; focus usa borda e outline azul. Readonly mantém leitura com fundo canvas; disabled usa fundo alternativo. `aria-invalid="true"` aplica borda de perigo.
- `x-label`: 14/20px, peso 500, distância inferior 8px.
- `x-input-error`: continua recebendo `for` e exibindo a mensagem do error bag; 14/20px, peso 500, perigo e margem superior 8px.
- `ds-help`: 14/20px, muted e margem superior 8px.
- A etapa não injeta atributos ARIA, IDs ou bindings automaticamente. Consumidores devem associar label, ajuda e erro ao campo quando apropriado.
- `x-checkbox`: API de atributos preservada, controle 16px, raio 4px, seleção azul, foco de teclado e disabled. A área clicável deve ser ampliada pelo label nas telas; não é garantida pelo checkbox isolado.

### Estrutura e estados

- `ds-page` aplica fundo canvas e altura mínima da viewport.
- `ds-container` mantém max-width 1280px e gutters 16/24/32px nos breakpoints existentes, preservando consumidores da Etapa 1.
- `ds-storefront-container` usa `--ds-storefront-width: 70rem` (1120px com raiz 16px), largura incluindo gutters de 16px e 24px a partir de 640px. Header, home, catálogo, produto e footer compartilham esse eixo externo; blocos internos podem permanecer mais estreitos.
- `ds-panel`: superfície branca, borda estrutural, raio 16px e sombra card; sem padding implícito.
- `ds-card`: mesmo contrato, raio 12px e padding 20px. Ambos usam min-width zero; sem altura/largura fixa ou hover automático.
- `x-badge` usa `neutral`, `info`, `success`, `danger` ou `warning`;
- `x-alert` usa os mesmos estados para mensagens persistentes.

Badges usam texto 12/16px, peso 600, padding 4/8px e raio pill. Alerts usam texto 14/20px, padding 12/16px, raio 12px, borda semântica e mantêm `role="alert"`. Todas as variantes públicas são preservadas na safelist do Tailwind, pois os nomes são montados dinamicamente no Blade.

`x-shop-logo` mantém `label=true`, destino home e encaminhamento de atributos. Usa texto ink 20/28px, peso 700, gap 8px e ícone azul de 1.2em. Tamanhos locais como `text-base` e `text-lg` sobrescrevem o default sem competir com outro utilitário fixo.

## Validação e limites

Validar build, renderização Blade, encaminhamento de atributos/aliases e testes existentes com banco isolado. No navegador, conferir foco, contraste, texto longo, zoom de 200%, movimento reduzido e viewports de 320 a 1440px. Texto normal deve atingir contraste 4.5:1; foco e limites essenciais, 3:1.

Controles maiores e pesos reais podem mudar altura, quebra de linhas e densidade das telas consumidoras. Estilos locais continuam prevalecendo pela camada de utilities: fontes pequenas ou cores duplicadas nas páginas ainda precisam de revisão específica. Home, catálogo, detalhe do produto, carrinho, checkout e pagamento simulado foram refinados nas Etapas 3A/3B/4/5A/5B; área geral de pedidos, rastreio e autenticação individual permanecem fora dessa revisão.

## Estrutura compartilhada — Etapa 2

- Home reutiliza `x-storefront-layout`, que contém um único `x-storefront-header` e um único `x-storefront-footer`. O slot continua responsável pelo elemento main; o shell organiza o footer após o conteúdo e preenche no mínimo a viewport.
- Wrappers principais de home, catálogo e produto usam `id="storefront-content" tabindex="-1"`. O main do layout app também oferece esse destino ao header compartilhado. O atalho “Pular para o conteúdo” aparece ao receber foco e permite ultrapassar a navegação por teclado.
- Header usa tokens da fundação, texto de 14px e controles com altura mínima de 44px. Logo continua consumindo `x-shop-logo`; busca usa `x-input` e `x-button`.
- Abaixo de 640px: logo/ações, busca e navegação ocupam três linhas. De 640px a 1023px: logo/busca/ações na primeira linha e navegação na segunda. A partir de 1024px: todos na mesma linha.
- Busca, quatro links de navegação, carrinho e conta permanecem visíveis em todas as larguras. Não há menu oculto, overlay, novo estado Alpine ou breakpoint baseado na resolução da captura.
- Busca é formulário GET para `catalog.index`, com parâmetro `q`, valor atual escapado pelo Blade e submit com nome acessível. A home passa a usar o mesmo contrato já existente no catálogo; a lógica de filtros permanece intacta.
- Conta continua direcionando visitantes a login e autenticados a dashboard. Nomes extensos recebem reticências somente na apresentação; o nome completo permanece no título e no nome acessível do link.
- Navegação identifica a página atual com `aria-current="page"`; links e ações têm foco visível. O indicador vermelho do carrinho continua decorativo e estático, sem passar a representar uma quantidade.
- Footer mantém o conteúdo institucional existente. Texto principal de 14/20px, copyright de 12/16px; uma coluna no mobile, duas a partir de 640px e cinco a partir de 1024px. Itens que eram texto não foram convertidos em links com destinos inventados.

### Evidências e pendências da Etapa 2

- Build e seis testes adicionais de estrutura/busca passaram. Suíte: 173 aprovados, 7 ignorados e as mesmas três falhas do baseline da Etapa 1.
- Falhas anteriores preservadas: link administrativo no dashboard (`AdminAccessTest`), home sem tabela categories em `ExampleTest` e `SecurityHeadersTest`.
- Navegador real headless, rotas reais e banco temporário com dados fictícios: home e catálogo, visitantes e autenticados, em 320/375/768/1024/1280/1440px CSS (24 combinações).
- Conferidos landmarks únicos, busca GET, links de conta, alinhamento externo, ausência de sobreposição no header e ausência de overflow horizontal; capturas reais produzidas sem substituir as imagens do projeto.
- Capturas usam escala de dispositivo 1 e zoom conhecido do ambiente de validação; não se inferem medidas CSS ou zoom a partir da composição oficial. A largura útil pode ser menor que a viewport por causa da barra de rolagem.
- Permanecem pendentes a tipografia local de cards/categorias, composição/corte do hero, imagens dos produtos, densidade dos filtros no tablet/mobile e refinamento das seções da home. Dispositivos físicos e leitores de tela não foram validados.

## Home — Etapa 3A

- Estilos exclusivos em `resources/css/home.css`, importados por app.css, com classes prefixadas home-. Os contratos compartilhados de header/footer, containers e componentes da fundação não foram alterados.
- `x-home-product-card :product="$item"` elimina a duplicação entre ofertas e mais vendidos. A Etapa 3B também reutiliza esse componente no catálogo, com a variante explícita de marca descrita abaixo.
- Nomes em 14/20px, peso 600, sem clamp/truncamento e com altura mínima para duas linhas; expandem quando necessário. Preços em 18/26px e 20/28px a partir de 1024px, peso 700. Avaliações/metadados em 14/20px; somente dados persistidos são exibidos.
- Grid de produtos: uma coluna abaixo de 375px, duas a partir de 375px, três em 768px, quatro em 1024px e cinco em 1280px. A largura externa continua limitada a 1120px; não se reduzem fontes para manter colunas.
- Mídia dos cards em proporção 4:3, object-contain, padding e fundo neutro. Nome/preço/avaliação compartilham o mesmo contrato nas duas seções; alturas acompanham a maior necessidade da linha. Hover usa borda/sombra e foco usa outline, sem zoom/corte da imagem.
- Hero mantém hero-headphones.png, navy, texto claro e CTA branco. No mobile, texto e arte ocupam blocos separados; no tablet/desktop, a imagem fica à direita, inteira, com contain e transição suave para o fundo. Altura mínima desktop 340px, sem prender o texto a uma altura que o corte.
- Títulos de seção 24/32px; ritmo principal de 32px. Atalhos com círculos 56/60px, ícones 28px e legendas 14/20px; grid de 4, 5 ou 10 colunas conforme a largura.
- Categorias em destaque mantêm nomes/destinos e usam ilustrações temáticas locais, com alt vazio quando decorativas e redundantes ao título do link.
- Benefícios preservam os fundos suaves azul/verde/laranja. A promessa de frete grátis acima de R$199 foi removida porque ShippingCalculator calcula modalidades pagas e retirada, sem essa regra; a promessa de 12x foi removida porque o pagamento atual é uma simulação sem parcelamento. Nenhum valor/regra de cálculo foi alterado.
- Banner inferior divulga a experiência responsiva no navegador e usa uma ilustração própria de dispositivos. Não há download, QR Code ou aplicativo móvel anunciado.
- Newsletter não possui serviço real. Campo e botão permanecem desabilitados, fora de um formulário de envio, com aviso explícito de indisponibilidade e sem coleta, sucesso simulado ou promessa de ofertas. Integração futura permanece pendente.
- Ícones de favoritos foram removidos dos cards da Home por não haver ação correspondente.

### Imagens de demonstração

Os 16 SVGs de produtos são desenhos próprios, distintos e sem marcas reais, em public/images/demo-products. O manifesto técnico é config/demo-images.php; a origem e licença CC0 estão no README/LICENSE junto aos arquivos. A ilustração responsiva tem a mesma licença; isso não relicencia o hero pré-existente.

A ordem de seleção na Home é: primeira imagem cadastrada cujo arquivo exista, respeitando a ordem da relação; ilustração local apenas para SKU demo explicitamente conhecido; fallback textual/ícone quando não houver representação disponível. Falha de carregamento posterior também mostra fallback via estado Alpine local. Renderizar a Home nunca grava dados ou associações.

O seeder independente pode persistir as associações:

```sh
php artisan db:seed --class=DemoProductImagesSeeder
```

Ele copia somente esses assets ao disco public e usa firstOrCreate para adicionar ProductImage com sort_order 100, preservando fotos existentes e todos os atributos do produto. SKUs desconhecidos são ignorados. Não altera usuários, estoques, preços, avaliações, pedidos, migrations ou o DatabaseSeeder geral. **Não foi executado no banco de desenvolvimento nesta etapa**; foi validado em banco isolado. A Home já utiliza os SVGs locais para SKUs demo sem foto, sem exigir reexecução do seeder geral.

Para novos SKUs fictícios, fornecer imagem cadastrada ou ampliar explicitamente o manifesto com um asset apropriado. Não inferir uma imagem genérica pela categoria nem usar fotos de marcas reais como se fossem o produto fictício.

## Catálogo — Etapa 3B

- Estilos exclusivos em `resources/css/catalog.css`, importados por app.css e prefixados catalog-. O container externo de 1120px e os contratos da fundação permanecem iguais.
- Catálogo reutiliza `x-home-product-card :product="$product" :show-brand="true"`. A propriedade opcional showBrand tem default false, mantendo a Home. Marca não vazia aparece em 14/20px; imagem, nome completo, preço, avaliações, hover e foco vêm do mesmo componente e CSS da Home.
- A seleção de imagens segue exatamente a prioridade documentada na Etapa 3A: arquivo cadastrado disponível, SVG do SKU demo conhecido, estado textual de indisponibilidade. Não há descontos ou favoritos simulados.
- Produtos: uma coluna abaixo de 375px, duas a partir de 375px e três a partir de 768px. Em 1024px, sidebar de 240px à esquerda e resultados flexíveis à direita, separados por 24px. Não se diminuem fontes para acomodar mais colunas.
- Abaixo de 1024px, filtros ficam em details/summary nativo, inicialmente recolhido. Em desktop, iniciam expandidos. Enter/Espaço ativam o summary; Escape recolhe em mobile/tablet e devolve o foco ao summary. Não há overlay/modal. Estado local Alpine e wire:ignore.self preservam a abertura durante atualizações Livewire; recolher não limpa filtros.
- Todos os filtros existentes permanecem: busca, categorias, preço mínimo/máximo, marcas, avaliação mínima, estoque e duas opções de entrega. Labels de 14/20px, inputs da fundação, x-checkbox e fieldsets/legends organizam os controles. Os campos de preço mantêm o funcionamento numérico real, sem slider decorativo.
- Chips usam x-badge com tipografia local de 14/20px e quebra de texto. Identificam busca e todos os filtros aplicados, inclusive o parâmetro category legado. Não têm remoção individual fictícia. Limpar filtros preserva a busca, conforme Browser.php; Limpar busca usa o setter Livewire existente.
- Ordenação e quantidade mantêm suas opções e ações. Botões de abertura expõem aria-expanded/controls; opções são botões nativos, navegáveis por Tab; Escape fecha e retorna foco. Paginação preserva pageName e ações, expõe página atual/nome acessível e usa alvos mínimos de 44px com quebra de linha.
- Estado vazio oferece as ações reais de limpeza. O contador anuncia atualizações via role=status. Nomes longos e marcas podem quebrar linhas; imagens usam contain. A nota inferior informa o caráter demonstrativo sem anunciar parcelamento não implementado.
- Evidências e limitações estão em [relatório da Etapa 3B](reviews/2026-10-23-etapa-3b/README.md). A página de detalhes do produto não faz parte desta etapa.

## Detalhes do produto — Etapa 4

- `resources/css/product-detail.css` contém classes product- e consome os tokens existentes, sem alterar seus contratos. Container externo de 1120px; composição em duas colunas iguais a partir de 1024px. Abaixo disso, galeria precede informações e ações.
- Galeria no Blade existente: imagens cadastradas com arquivo disponível, em ordem e sem repetir o mesmo path; se nenhuma estiver disponível, SVG do SKU fictício conhecido. SKU sem representação usa estado de imagem indisponível. Imagens usam contain; área 4:3 no mobile/tablet e quadrada no desktop, sem cortar o produto.
- Uma imagem não gera miniatura repetida. Duas ou mais geram botões de seleção com aria-pressed e aria-controls. Clique/Espaço/Enter selecionam; setas esquerda/direita, Home e End selecionam e movem foco entre miniaturas. Uma região aria-live informa posição. Falhas após o carregamento e falhas já concluídas antes de Alpine iniciar exibem indisponibilidade; outras miniaturas continuam acessíveis.
- Título 24/32px no mobile e 32/40px a partir de 640px, preço 32/40px, marca e metadados 14/20px. Nomes, SKU e descrições longos quebram linha; não são truncados. Média e contagem são persistidas, sem estrelas artificiais de cinco pontos nem comentários inventados. Zero avaliações usa texto explícito.
- Quantidade reutiliza x-label/x-input/x-button e preserva wire:model quantity, increment/decrement e submit add. Controles de 48px, input 16px, botão primário azul. Limites visuais consideram on_hand menos reserved e máximo existente 99. Disabled dos botões acompanha também a digitação local via estado reativo $wire.quantity, sem requisição extra ou mudança nos métodos.
- Sem estoque, ações e campo ficam desabilitados. Carregamento desabilita ações e mostra Adicionando…; erros existentes têm associação ARIA e região de anúncio. A validação autoritativa continua em AddCartItem, inclusive considerando quantidades já no carrinho; estoque pode mudar após a renderização. Sucesso mantém flash e redirecionamento reais para o carrinho; visitantes continuam direcionados ao login.
- Não há favoritos sem ação, preço anterior/desconto inventado, promessa de 12x ou frete grátis acima de um valor. O antigo link Comprar agora foi removido porque apenas abria checkout sem adicionar este produto. Pagamento é identificado como simulado, e modalidades/prazo/valor de entrega são consultados no checkout existente.
- Descrição e especificações são seções semânticas, sem abas vazias. Marca, categoria, SKU, peso positivo e dimensões positivas vêm dos registros. Não há modelo de comentários individuais para uma aba de avaliações. Descrição vazia informa ausência.
- Relacionados reutilizam x-home-product-card sem modificá-lo: uma coluna abaixo de 375px, duas a partir de 375px, três a partir de 768px e quatro em 1024px. Consultas e navegação existentes permanecem iguais.
- Relatório, capturas e limites: [Etapa 4](reviews/2026-10-23-etapa-4/README.md).

## Carrinho — Etapa 5A

- CSS exclusivo em resources/css/cart.css, prefixo cart-, tokens e container storefront de 1120px. A view conserva x-app-layout e o main compartilhado, com h1 no conteúdo em vez de outro header local; layout, header e footer não foram alterados.
- Em 1024px, itens à esquerda e resumo de 320px à direita, separados por 24px. Abaixo desse breakpoint, resumo após a lista, sem sticky/overlay. Itens usam linha própria no template existente; não forçam o card do catálogo.
- Imagens em contain, áreas de 80px no mobile e 112px a partir de 640px. Prioridade: primeira imagem cadastrada cujo arquivo existe, SVG do SKU demo conhecido, indisponibilidade. Falhas de carregamento, inclusive anteriores à inicialização de Alpine, têm fallback. Nome completo em 16/24px, marca/metadados em 14/20px e preço unitário em 18/26px.
- Quantidade usa x-label/x-input/x-button; alvos mínimos de 44px. Preservados quantities.ID, updateItem(ID), removeItem(ID), Atualizar e remoção por atualização zero. +/− ajustam somente o estado de quantidade e chamam updateItem existente. Disabled acompanha digitação via $wire.quantities; o servidor continua validando estoque, quantidade e posse.
- Todos os controles têm wire:loading.attr disabled com alvo explícito updateItem,removeItem; bloqueiam durante qualquer operação do carrinho. Região role=status anuncia atualização. Mensagens por quantities.ID e erros globais quantity/cart são apresentados com x-input-error; flash de sucesso é preservado.
- Nenhum cálculo financeiro novo na view ou JavaScript. Subtotal e Total dos produtos formatam o mesmo subtotal recebido de CartManager. Entrega aparece como A definir no checkout, com explicação de que o total final depende da modalidade; não há frete zero antecipado, desconto/cupom ou total por linha calculado na view.
- Checkout usa o destino real e continua protegido. Visitantes seguem ao login. Estado vazio usa ilustração com o x-icon shopping-cart existente, texto e link real para o catálogo. O único acréscimo ao conjunto de ícones é trash, para remoção com nome acessível.
- Evidências, limitações e pendências para 5B: [relatório da Etapa 5A](reviews/2026-10-24-etapa-5a/README.md).

## Checkout e pagamento simulado — Etapa 5B

- resources/css/checkout.css usa os tokens existentes e classes checkout-/payment-. Container storefront1120px; checkout com coluna principal flexível e resumo320px em1024px. Abaixo disso, resumo após as seções. Sem sticky/overlay.
- Etapa1: produtos, endereço e modalidade; ação continua Criar pedido e reservar estoque. Etapa2: formulário fictício somente em pending_payment, na página privada do pedido. Não há pagamento no checkout nem mudança no momento de reserva.
- Produtos usam imagens cadastradas disponíveis, assets demo do SKU e fallback; contain e nomes16/24px, quantidade14/20px e valor18/26px. CheckoutManager.render carrega imagens antecipadamente e fornece totais por linha/estimativa; Blade apenas formata valores. A ação PlaceOrder continua recalculando preços, frete, estoque e total definitivo.
- Resumo identifica Total estimado, subtotal e modalidade/valor de entrega do servidor. shippingMethod recebe .live para atualizar essa estimativa no servidor ao selecionar. Não há cálculos financeiros no navegador.
- Select nativo addressId mantém relação autenticada e mostra só rótulos. Nomes repetidos recebem ordinal de cadastro e aviso para revisar/renomear; Gerenciar endereços está sempre disponível quando há endereços. Sem endereço, há orientação/link real e a ação de criar pedido não aparece. Nenhum CEP, rua ou destinatário adicional é exposto na seleção.
- Select/radios permanecem operáveis durante consultas de estimativa, preservando foco de teclado. Desabilitam na criação confirm. O botão principal desabilita também durante addressId/shippingMethod; loading e erros têm regiões de anúncio.
- payment-form reutiliza x-label/input/button/error/alert/badge. IDs, bindings, pay/cancel, wire:confirm, autocomplete off, locks e limpeza dos campos sensíveis permanecem iguais. Aviso acadêmico explícito, apenas números públicos fictícios; total vem de Order.total_cents.
- Campos de validade ficam em colunas separadas no mobile, duas a partir de640px; área de contexto do pedido fica ao lado em1024px. Controles com pelo menos44px e submit48px. Erros de validade cardExpiry e cancelamento order, antes sem saída própria, também são exibidos. Pay/cancel bloqueiam todos os campos e ambos os botões enquanto pendentes.
- A integração em orders/show limita-se à condição pending_payment: título/conteúdo alinhados ao storefront, formulário antes dos detalhes existentes e pequenos ajustes de quebra de nome/moeda escopados nessa condição. Os demais estados e o rastreio não foram refatorados.
- Evidências, divergências e limites: [Etapa 5B](reviews/2026-10-24-etapa-5b/README.md).

## Pedidos e rastreamento — Etapa 6A

- resources/css/orders.css é restrito às classes orders-/order-/customer-/shipment-. Container externo storefront1120px. Navegação da conta208px e conteúdo flexível a partir de1024px; abaixo, navegação acima do conteúdo, com links quebrando em linhas. Integração exclusiva em orders/index e orders/show; demais áreas da conta ficam para6B.
- x-customer-navigation identifica o usuário pelo nome (sem e-mail adicional), usa somente dashboard, orders.index e personal-data.show existentes e destaca orders.*. Header/footer compartilhados permanecem únicos. Sem links fictícios, abas decorativas ou filtros de status não implementados.
- Listagem conserva paginate(10), data placed_at com fallback created_at, número público, status do pedido, total histórico, primeira imagem ilustrativa e contagens explícitas de itens/unidades. Outros itens são indicados por e mais N itens. Estado vazio oferece catálogo real.
- x-order-status mapeia separadamente OrderStatus, ShipmentStatus e PaymentStatus para português e variantes existentes de x-badge. Cancelado/reembolsado neutros, pendente warning, falha danger, processamento/transporte info e conclusão/autorização success. Rótulos e regiões identificam o domínio; nenhuma transição é criada.
- x-order-item-image usa somente produto atual com SKU igual ao snapshot para imagem cadastrada disponível; depois, asset local do SKU histórico conhecido; depois, indisponibilidade. Assim, produto alterado/excluído não substitui a identidade histórica. Erro de carregamento tem fallback, inclusive antes de Alpine inicializar. Imagens contain, 72×80px; o card comercial não é reutilizado porque lê preços e nomes atuais.
- Detalhes formatam exclusivamente unit_price_cents, total_cents, subtotal_cents, shipping_cents, total_cents e address_snapshot_encrypted históricos. Não recalculam nem consultam preços atuais. Pagamento exibe somente bandeira/final/valor/status/data, sem tokens/chaves/código de autorização. Notas existentes são escapadas por Blade; atores internos não são identificados.
- x-shipment-tracking diferencia compra e logística. Progresso usa o status atual e eventos from/to. Somente etapas anteriores ao estado atual com evidência são concluídas; etapa anterior sem evidência mostra Sem registro. Eventos de etapa adiante após recuperação mostram Registro anterior, sem concluir o futuro. Exceções failed/returned/cancelled têm aviso próprio, sem etapa normal atual nem conclusão inferida. Histórico completo ordenado por created_at e id; nenhuma movimentação sintética é adicionada.
- Progresso é ol com aria-current=step, marcadores numerados/check e rótulos textuais. Horizontal em1024px, vertical abaixo. Não depende apenas de cor; estágios não são botões. Textos relevantes≥14px, títulos32/40px, nomes16/24px, valores18/26px e ações≥44px.
- Não se calcula previsão de entrega pelo tempo: mostra prazo contratado e shipped_at/delivered_at quando existentes. Ausência de data, expedição e eventos é explícita. Não há transportadora ou link externo inventado.
- pending_payment conserva um único formulário5B antes dos detalhes, em largura inteira para preservar seu breakpoint; wire:*, ações e arquivo do formulário intactos. Checkout não foi alterado na6A.
- Controller recebeu apenas eager loading items.product.images. Filtros user_id/public_number, autenticação, no-store e paginação permanecem iguais.
- Evidências e incidente da primeira execução de testes: [Etapa 6A](reviews/2026-10-24-etapa-6a/README.md).

## Dashboard e Área da Conta — Etapa 6B

- `x-account-shell` integra dashboard, dados pessoais e perfil ao storefront, com um único header/footer, container de 1120px e heading próprio. `resources/css/account.css` contém os estilos account- e os ajustes escopados da navegação compartilhada. Não cria novos tokens nem altera contratos da fundação.
- A navegação iniciada na 6A agora atende toda a conta: visão geral, pedidos, dados, perfil/segurança e logout POST com CSRF. Administração aparece somente para administradores. Sidebar de 208px em 1024px; abaixo disso, disclosure nativo compacto, operável por teclado. Sem JavaScript, os destinos permanecem disponíveis. Página ativa usa aria-current e indicador visual; controles têm pelo menos 44px.
- Dashboard usa `Account\Overview`, exclusivamente de leitura e filtrado pelo usuário autenticado. Mostra contagens reais de pedidos, pagamento pendente e entregues, últimos três pedidos e links existentes. Reutiliza x-order-status e x-order-item-image; totais são históricos. Não há indicadores administrativos, pontos ou atividades inventadas.
- Títulos principais 32/40px, seções 20/28px, nomes 16/24px e informações auxiliares 14/20px. Estatísticas passam de uma para três colunas em 640px; atalhos passam para três colunas em 1280px. Conteúdo longo quebra linha sem diminuir fontes.
- Dados pessoais preservam Manager, seus métodos, validações, máscaras e criptografia. Cards organizam identificação, telefones e endereços; formulários têm duas colunas a partir de 640px e lista de endereços duas em 1280px. Erros são associados por aria-describedby/aria-invalid, mensagens têm anúncio e ações existentes recebem estados de carregamento/desabilitado. Principal reutiliza x-badge.
- Perfil conserva as seções e condições Jetstream existentes, com apresentação ShopPlus e textos em português. Informações do perfil, senha, 2FA, sessões e exclusão mantêm ações, confirmações e bindings. Nenhum recurso foi habilitado. O menu Jetstream deixa de duplicar a navegação em profile.show; a área de tokens mantém o comportamento anterior.
- As descrições da navegação nas notas da 6A registram o estado daquela entrega; este contrato compartilhado passa a substituí-las. Views, lógica e rastreamento dos pedidos não foram alterados na 6B.
- Evidências, comparação de baseline, capturas e limitações: [Etapa 6B](reviews/2026-10-24-etapa-6b/README.md).
