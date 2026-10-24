# ShopPlus — Etapa 3B: Catálogo

**Especificação:** solicitação anexada da Etapa 3B; referência visual oficial, design system e Home aprovada.
**Escopo:** somente apresentação do catálogo; preservar Home/header/footer, queries, modelos, controllers, rotas e todos os dados.

## Decisões

- Reutilizar x-home-product-card com showBrand=false por padrão e true no catálogo; manter resultado da Home inalterado.
- CSS exclusivo catalog.css importado em app.css, sem duplicar os estilos do card da Home.
- Sidebar à esquerda a partir de 1024px; abaixo disso, details/summary expansível e acessível, sem drawer/modal. Estado Alpine local persiste em atualizações Livewire; Escape recolhe no mobile e devolve foco ao summary.
- Preservar todos os wire:model, ações de ordenação/perPage/paginação e lógica de Browser.php; expandir somente apresentação de filtros ativos.
- Limpar filtros mantém comportamento existente (não apaga busca); oferecer Limpar busca separadamente usando o setter Livewire já disponível.
- Paginação com botões legíveis, foco e quebra de linhas, sem alterar número de itens ou consultas.
- Não repetir promessas de 12x sem suporte no pagamento real do projeto.

## Tarefas

- [x] Registrar baseline e adicionar verificações de apresentação/compatibilidade.
- [x] Reutilizar card e refinar template, filtros, chips, menus e paginação.
- [x] Aplicar CSS responsivo e documentar contrato.
- [x] Build e testes existentes; comparar as três falhas anteriores.
- [x] Navegador: seis larguras, sessões, filtros reais, ordenação, paginação, estados vazios, nomes longos, imagem ausente, teclado, overflow e capturas.
- [x] Revisão final e entrega sem commits ou avanço ao detalhe do produto.

Evidências: docs/design/reviews/2026-10-23-etapa-3b/README.md. Build aprovado; quatro novos testes aprovados; suíte com as mesmas três falhas anteriores. Navegador aprovado nas 12 combinações de largura/sessão e nas interações funcionais.
