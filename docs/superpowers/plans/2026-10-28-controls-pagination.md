# ShopPlus: selects, botões e paginação

**Objetivo:** executar o pedido anexado, preservando identidade visual, bindings, confirmações, regras comerciais e alterações anteriores. Sem commits ou push.

**Abordagem:** manter selects nativos acessíveis em um componente Blade compartilhado; reutilizar `x-button` e tokens existentes. A paginação compacta será calculada na apresentação a partir do paginator real, sem alterar consultas ou estado Livewire.

- [x] Registrar testes da janela de páginas (início, meio, fim e poucos resultados), mudança de quantidade/filtros e remoção do aviso apenas no Catálogo.
- [x] Reorganizar o rodapé com flexbox e paginação unida, com primeira/última página, vizinhas e reticências somente para intervalos omitidos.
- [x] Criar `components/select.blade.php`, migrar os 16 selects nativos encontrados em Perfil, Checkout, Monitoramento e Administração; preservar atributos e opções. O Catálogo usa dropdowns Alpine: manter seu comportamento e padronizar o chevron/teclado do seletor de quantidade.
- [x] Revisar `x-button` e wrappers secondary/danger/success; adicionar variantes text/ghost e tamanho icon. Migrar somente botões administrativos com estilos paralelos. Perfil e Carrinho já usam o componente e devem continuar com as dimensões atuais.
- [x] Rodar testes completos, build isolado e verificações de navegador (layout desktop/mobile, estados de controles e teclado); guardar evidências exclusivamente em `.artifacts/visual-reviews/` com `visualReviewOutput` e conferir Git/ignore.

**Limitação esperada:** menus abertos de selects nativos seguem o sistema operacional; aparência do campo, chevron e estados CSS serão compartilhados, preservando acessibilidade nativa.

**Valida??o:** 442 testes, 3.622 assertions, 7 ignorados; build isolado aprovado. HTML real renderizado de Cat?logo, Perfil e Administra??o validado no navegador, incluindo teclado e estados. Evid?ncias tempor?rias ignoradas em `.artifacts/visual-reviews/controls-pagination-1KywIZ/`.

**Corre??o posterior solicitada:** no Cat?logo, `x-catalog-dropdown` foi extra?do da Ordena??o e reutilizado por `sort` e `perPage`; o select nativo de quantidade foi removido. Os outros selects permaneceram intactos. A pagina??o tem bot?es fixos de 40?40px e janela responsiva, com teste de sele??o efetiva para valores de URL fora das op??es. Valida??o atual: 443 testes, 3.666 assertions, 7 ignorados; build e 15 cen?rios de layout/teclado aprovados. Evid?ncias em `.artifacts/visual-reviews/catalog-custom-dropdown-rLz0Zb/`.
