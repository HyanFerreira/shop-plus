# ShopPlus — Etapa 4: detalhes do produto

Escopo autorizado no anexo da Etapa 4. Execução inline, sem commits, dependências, banco de desenvolvimento ou alterações em regras de negócio. Git já contém as etapas aprovadas; seus arquivos são protegidos por hashes em diretório temporário.

## Decisões e contratos

- Manter controller/queries e classe ProductAddToCart/AddCartItem intactos. Alterar somente show.blade.php, template de compra, importação CSS, testes e documentação.
- Galeria Alpine no Blade existente: imagens cadastradas cujo arquivo exista, deduplicadas por path e em ordem; quando não houver, SVG do SKU demo conhecido. Sem fotos repetidas ou nova biblioteca. Uma imagem não gera miniaturas. Mais de uma usa botões com seleção aria-pressed, mouse, setas/Home/End e foco visível. Erro de carregamento mostra o estado de indisponibilidade existente.
- Descrição e especificações reais em seções, sem abas vazias: não há avaliações individuais no modelo. Mostrar média/contagem persistidas somente quando existirem.
- Remover favoritos, selo de oferta sem desconto e promessa de 12x/frete grátis acima de R$199. Remover link Comprar agora, que só navega ao checkout sem adicionar o produto atual. Não criar outro fluxo.
- Template de compra reutiliza x-button/x-input/x-label/x-input-error e os métodos increment/decrement/add. Limites e disabled da apresentação usam estoque disponível e limite existente 99; validação autoritativa continua no servidor, inclusive itens já no carrinho. Sucesso continua flash e redirecionamento reais para o carrinho.
- CSS product-detail.css prefixado product-, tokens existentes, container1120px, galeria primeiro no mobile, duas colunas a partir de1024px, relacionados com x-home-product-card. Não modificar Home/catálogo/header/footer ou card compartilhado.

## Tarefas e evidências

- [x] Testes RED: prioridade/deduplicação de fotos, demo/ausência, relacionados, conteúdo honesto, controles/estoque e add real/erros/visitante. Ajustar fixture de PublicCatalogTest para foto cadastrada efetivamente existente.
- [x] GREEN: template do detalhe, galeria, seções reais, template de compra e CSS responsivo.
- [x] Build e testes completos em SQLite isolado. Comparar com baseline3B:192testes,836assertions,182aprovados,7ignorados,3falhas(AdminAccessTest,ExampleTest,SecurityHeadersTest).
- [x] Navegador real em320/375/768/1024/1280/1440px: galeria mouse/teclado, fotos/demo/ausência/erro, disponível/indisponível, nomes/descrições longos, quantidade/adição/erros, visitante/autenticado, overflow, capturas.
- [x] Revisão independente somente leitura, hashes de preservação, documentação e relatório com divergências/limites. Sem commits.

Critério: acabamento igual à Home/catálogo, sem funcionalidade inventada. Captura oficial orienta composição, sem presumir seu zoom. Contratos compartilhados permanecem inalterados.


Revisão: disabled reativo ao digitar quantidade e fallback de imagem anterior ao Alpine foram reproduzidos no navegador (RED), corrigidos apenas nos templates e validados (GREEN). Build e 12 testes/77 assertions passaram; suíte197/885 manteve3falhas anteriores e7skips. Matriz navegador12combinações passou novamente após correções. Evidências: docs/design/reviews/2026-10-23-etapa-4/README.md.
