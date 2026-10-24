# ShopPlus — Etapa 5A: carrinho

Execução inline da especificação anexada. Sem commits, dependências, alterações em .env, banco de desenvolvimento, checkout ou regras comerciais. Preservar trabalho anterior registrado no Git; hashes de arquivos fora do escopo estão em diretório temporário.

## Decisões

- Manter x-app-layout e seus recursos autenticados. Retirar somente o slot header local do carrinho, colocando h1 no conteúdo alinhado ao container storefront1120px; não alterar layout compartilhado.
- Alterar cart/show.blade.php, livewire/cart-manager.blade.php, CSS cart.css/import, testes e documentação. Não modificar CartManager, UpdateCartItem, modelos, rotas ou consultas.
- Linha de carrinho própria no template atual, sem forçar o card de catálogo. Seleção de imagem igual ao padrão: primeiro arquivo cadastrado disponível, SVG do SKU demo conhecido, fallback de indisponibilidade. Reutilizar componentes de campo/botão/erro/ícone existentes.
- Preservar quantities.ID, updateItem(ID), removeItem(ID) e ação Atualizar. Botões +/− alteram apenas a quantidade local e chamam updateItem existente. Limites visuais por estoque e99; validação autoritativa continua no servidor. Input min0 mantém remoção por atualização zero. Disabled acompanha digitação e carregamento.
- Exibir também erros globais quantity/cart já retornados pela ação. Erros por quantities.ID e flash existentes permanecem. Não refatorar tratamento de erros ou regras no PHP.
- Usar somente subtotal já calculado por CartManager: sem criar cálculos financeiros na view ou JavaScript. Total dos produtos repete esse valor com rótulo preciso; total final/frete dependem do checkout. Sem taxas/cupons/descontos inventados; total por linha omitido por não haver valor calculado pelo componente.
- Duas colunas em1024px com resumo de320px; antes disso resumo abaixo. Sem sticky. Vazio com ilustração feita com ícone existente, fundo azul suave, texto e CTA real.

## Tarefas

- [x] RED: testes de imagens, resumo, vazio, autenticação/escopo, atualização/subtotal, remoção/zero e erros de estoque; contratos dos controles.
- [x] GREEN: templates e CSS exclusivo com tokens, estados de foco/loading/disabled e responsividade.
- [x] Build e suíte completa em SQLite isolado, comparando com Etapa4 (197testes,885assertions,187aprovados,7skips,3falhas anteriores).
- [x] Navegador autenticado: vazio/um/vários, seis larguras320/375/768/1024/1280/1440px, teclado, +/−/digitação, loading, estoque, remoção, subtotais, checkout e visitante→login; capturas reais.
- [x] Revisão independente, hashes, documentação, relatório de diferenças/limites e problemas funcionais preexistentes; somente5A.


Resultado: build aprovado; 14 testes/73 assertions específicos; suíte202/935 com as mesmas3falhas e7skips; matrizes18principais+12bordas e18finais somente leitura aprovadas. Loading corrigido após reprodução com requisição pausada. Revisão independente sem bloqueadores. Evidências em docs/design/reviews/2026-10-24-etapa-5a/README.md.
