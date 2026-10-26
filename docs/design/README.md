# Referência visual

## Correções do detalhe de produto — 26/10/2026

Coração na linha da marca, sem caixa e com sucesso somente acessível; cards relacionados mantêm o componente compartilhado da Home, com hover/foco no desktop e visibilidade em touch, sem ações de carrinho. Estoque usa `InventoryItem::available()` (saldo menos reservas), com estado esgotado bloqueando a compra. Quantidade aceita inteiros de 1 até o limite comercial de 99 e o estoque; controles de 40px sem setas nativas. Adicionar mantém a página e atualiza o header. Comprar agora leva somente o produto/quantidade selecionados ao checkout existente, preserva o carrinho e reutiliza transação, reserva, idempotência e pagamento simulado.

Validação em `.artifacts/isolated-catalog`, sem banco persistente: PHPUnit completo com 348 testes, 2467 assertions, 7 ignorados e nenhuma falha; nove regressões de compra; quatro testes JS de favoritos; build e Pint aprovados. Chrome em 320, 375, 480, 768, 1024, 1280 e 1440px verificou composição, altura, overflow, favoritos sem feedback visual de sucesso, estoque zero, contador e compra direta. Dois processos PHP disputaram uma unidade no SQLite descartável: um pedido/uma reserva; o concorrente recebeu contenção de banco e a repetição foi rejeitada por estoque. Revisão independente sem defeitos importantes.

Limitações: não houve teste concorrente no MySQL nem execução visual em Firefox/Edge; as regras CSS incluem esses motores. Dois testes preexistentes de armazenamento de evidências falharam no Windows (`/tmp` ausente e symlink sem permissão); nenhum deles pertence ao fluxo de compra. Evidências e executores temporários ficam exclusivamente em `.artifacts/visual-reviews/`, com saídas ignoradas conferidas. Alterações locais anteriores preservadas; sem migrations/seeders no banco persistente, commits ou push.

Arquivos desta correção:

- Backend: `app/Actions/Checkout/PlaceOrder.php`, `app/Livewire/ProductAddToCart.php`, `app/Livewire/CheckoutManager.php`.
- Interface: `resources/views/catalog/show.blade.php`, `resources/views/livewire/product-add-to-cart.blade.php`, `resources/css/product-detail.css`.
- Testes: `tests/Feature/Checkout/ProductPurchaseTest.php` (novo), `tests/Feature/Checkout/CheckoutInterfaceTest.php`, `tests/Feature/Checkout/HeaderCartTest.php`, `tests/Feature/Catalog/ProductDetailPresentationTest.php`, `tests/Feature/Security/AcademicFlowTest.php`.
- Build e relatório: `public/build/manifest.json`, `public/build/assets/app-CymyZAwY.css`, este README. Os demais arquivos já modificados antes da tarefa foram preservados.

A imagem [ecommerce-ui-reference.png](ecommerce-ui-reference.png) é a referência aprovada para a próxima etapa de layout e design.

Ela reúne exemplos para:

- página inicial e destaques;
- catálogo, filtros e cartões de produtos;
- detalhe do produto;
- carrinho e resumo do pedido;
- login e recuperação de senha;
- área do cliente e lista de pedidos;
- rastreamento da entrega.

O arquivo serve como direção visual, não como asset para publicação. Produtos, marcas, textos e imagens exibidos no mockup não devem ser importados como dados reais da aplicação. A implementação deve preservar os fluxos, controles de segurança e dados exclusivamente fictícios já existentes.

## Refinamento da Home — 26/10/2026

Carrossel manual com os dois banners locais (`hero-headphones.png` e `hero-eletronics.png`), imagens eager e prioridade alta para a inicial. Os slides ocupam a mesma célula de grid, reservando a altura de ambos antes da interação; o slide inativo fica invisível e inert. Setas, indicadores e atalhos de teclado ficam nos controles permanentes, sem ocultar um CTA focado. Movimento reduzido desativa as transições.

Uma única faixa de categorias usa registros ativos e o filtro existente `?category=slug`; Mais abre o catálogo. A ordem segue banner, categorias, destaques, três informações da loja, mais vendidos, dispositivos e newsletter indisponível. Mais vendidos retorna até dez produtos distintos, preservando a ordenação atual por avaliação e contagem. As duas seções inferiores, header, footer e tokens foram preservados.

O card compartilhado habilita ações opcionais somente na Home: hover/foco revelam os controles no mouse, enquanto touch e dispositivos híbridos os mantêm visíveis. Controles ficam sobre a imagem, fora do link e sem alterar a altura do card; em larguras pequenas, o carrinho mantém o ícone e seu nome acessível. O modo compacto de `ProductAddToCart` adiciona uma unidade pela ação de estoque existente, desabilita durante a requisição, mantém erros visíveis e publica `cart-updated` para o badge real. Detalhes do produto mantêm o fluxo anterior. Favoritos preservam o coração 1:1, estado azul e feedback de sucesso somente acessível.

Validação em checkout isolado com SQLite `:memory:`: suíte PHP com 336 testes, 2098 assertions, 7 ignorados e zero falhas; seis testes JS aprovados; build, Pint dos PHP alterados, três verificações de isolamento e `git diff --check` aprovados. A revisão independente apontou o atalho de teclado sobre o CTA, corrigido com regressão RED→GREEN. Nenhuma migration/seed no banco persistente, alteração de `.env`, biblioteca, commit ou push.

Limitação: o novo mockup mencionado não veio no anexo; foi seguida a especificação textual. O controle de navegador não encontrou superfícies nem pipe nativo nesta sessão. Assim, desktop/mobile, overflow, layout shift, contraste, interação real e leitor de tela ainda precisam de revisão visual; não foram geradas screenshots nem declarados resultados visuais. Evidências automatizadas ficam na pasta local ignorada `.artifacts/visual-reviews/`. O [plano de implementação](../superpowers/plans/2026-10-26-home-refinement.md) registra escopo e verificações.

Correção pontual posterior: o gradiente de `::after` ficou restrito ao primeiro slide; o segundo usa a imagem original com `object-fit: contain`, sem camada clara, recorte ou filtro. Um wrapper local ancora as setas no centro vertical do banner; as bolinhas circulares ficam abaixo dele, sem cápsula de fundo, com alvo clicável de 40px. Os textos ganharam somente a reserva lateral necessária para os controles. Categorias mantêm os mesmos links e nomes fora do bloco do ícone, agora com fundo suave, borda e raios do design system no container e nos pequenos blocos. As seções a partir de Ofertas foram comparadas e permanecem integralmente iguais. Validação adicional isolada: nove testes PHP da Home (127 assertions), dois testes JS do carrossel, build e `git diff --check` aprovados. A revisão visual desktop/mobile e de overflow permanece pendente pela mesma indisponibilidade do navegador; nenhuma screenshot foi gerada. Sem commit ou push.
