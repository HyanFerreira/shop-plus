# Refinamento da Home — plano de implementação

> Execução inline com superpowers:executing-plans; sem commits ou push.

**Objetivo:** reorganizar somente a Home, com dois banners existentes, categorias filtradas e ações reais nos cards.
**Arquitetura:** Alpine controla apenas a apresentação do carrossel. Os atalhos usam o parâmetro `category` existente. O card recebe ações opcionais usadas na Home; o modo compacto de `ProductAddToCart` chama `AddCartItem` com uma unidade e publica `cart-updated`, já ouvido pelo header.
**Stack:** Laravel, Blade, Livewire, Alpine, Tailwind e design system existente.
**Especificação:** pedido anexado em `C:/Users/Hyan Ferreira/.codex/attachments/138d3551-933a-4b5a-89ae-34c70a2ec5df/Pasted text.txt`.

## Restrições globais

- Preservar alterações locais; não alterar `.env`, banco persistente, checkout, pagamento ou estoque.
- Não instalar bibliotecas, inventar descontos, coletar e-mails, duplicar dados ou redesenhar outras páginas.
- Reutilizar `hero-headphones.png` e `hero-eletronics.png`; não há nova imagem de referência no anexo recebido.
- Manter Figtree, títulos até 2rem, texto 16px/14px, controles de 40px e container de 1280px.
- Evidências temporárias somente em `.artifacts/visual-reviews/`, via `visualReviewOutput` nos executores Node.

## Pontos de revisão

- Categoria arbitrária/inativa: atalhos apenas para registros ativos, filtro real de slug.
- Menos de dez produtos: mostrar apenas elegíveis, na classificação atual por avaliação/contagem.
- Estoque alterado entre renderização e clique: validar novamente na ação existente e não atualizar badge em erro.
- Touch, teclado e movimento reduzido: ações acessíveis sem hover, foco revela controles, carrossel manual e transições reduzíveis.
- Mesmo produto nas duas seções: chaves Livewire distintas e um único carrinho real.

## Tarefa 1 — Estrutura, categorias e carrossel

Arquivos: `HomeController.php`, `home.blade.php`, `home.css`, `home-carousel.js`, `app.js`, `HomePresentationTest.php`, `home-carousel.test.mjs`.

- [x] Testes de duas imagens estáticas, controles, ordem das seções, ausência da categoria duplicada, atalhos com slug, filtro efetivo e dez mais vendidos únicos/ordenados; executar e observar falhas.
- [x] Alpine com navegação manual anterior/próximo/indicador e primeiro slide estático. Imagens eager, dimensões explícitas, primeira com prioridade alta; altura do carrossel estável por breakpoint.
- [x] Usar categorias reais e ícones existentes em uma faixa horizontal; manter Mais para o catálogo. Ampliar somente limite de mais vendidos para dez, preservando ordenação.
- [x] Manter as três informações verdadeiras e as duas seções inferiores. Executar testes da Home e do carrossel.

## Tarefa 2 — Ações compactas de produto na Home

Arquivos: `ProductAddToCart.php`, sua view, `home-product-card.blade.php`, `home.css`, `HomeCardActionsTest.php`.

- [x] Testar adição de uma unidade sem redirecionar, evento e contagem por usuário; convidado, estoque indisponível, produto oculto, adulteração da quantidade e modo compacto bloqueado; observar falhas.
- [x] Modo compacto opcional/bloqueado no Livewire; manter o fluxo de detalhes existente, autenticação e proteção CSRF.
- [x] Opção `quickActions` somente na Home; controles fora do link, sobre a área da imagem, sem alterar altura nem cobrir informações. Ocultar visualmente apenas com mouse/hover confiável; revelar em hover/focus-within e sempre em touch.
- [x] Sucesso discreto acessível; erros visíveis, loading desabilitado e indisponibilidade honesta. Executar testes pertinentes de carrinho/favoritos/segurança.

## Tarefa 3 — Verificação e entrega

- [x] Build e suíte PHP/JS em checkout isolado com SQLite `:memory:`; verificar Pint dos PHP alterados e isolamento.
- [x] Tentar navegador via ferramenta disponível. Se indisponível, registrar explicitamente a limitação sem declarar screenshots ou inspeção visual realizadas.
- [x] Uma revisão independente do diff final; corrigir problemas importantes e executar testes de regressão necessários.
- [x] Copiar somente alterações autorizadas ao workspace original, preservando assets anteriores. Atualizar relatório existente, conferir `git diff --check`, `git check-ignore` e `git status --short --untracked-files=all`.
