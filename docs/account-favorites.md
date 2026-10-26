# Área da Conta e Favoritos

Perfil reúne Dados pessoais, Endereços e Segurança com navegação por âncoras. Os formulários Livewire/Jetstream e suas ações permanecem os existentes; `/meus-dados` continua abrindo o Perfil. Dashboard, pedidos e detalhes usam a largura do container sem sidebar. Administradores mantêm o acesso à gestão pelo Dashboard; o dropdown segue Perfil, Pedidos, Favoritos, Visão geral e logout POST com CSRF.

Favoritos pertencem exclusivamente ao usuário da sessão. A tabela `favorites` tem chaves estrangeiras e unicidade em `(user_id, product_id)`; POST e DELETE são idempotentes. Cards reutilizados na Home, Catálogo, produtos relacionados e Favoritos, além do detalhe do produto, atualizam o coração sem recarregar a página. Visitantes vão ao login; falhas preservam o estado anterior e apresentam mensagem. A página usa paginação de 12 itens, dados atuais e estado vazio. Produtos ativos sem estoque continuam visíveis com indicação de indisponibilidade; produtos inativos, de categorias inativas ou excluídos ficam ocultos, sem divulgar dados retirados do catálogo.

## Aplicação posterior da migration

A migration foi criada, mas **não foi aplicada ao banco persistente de desenvolvimento**. Para aplicar posteriormente, no ambiente escolhido e com as migrations anteriores já presentes:

```sh
php artisan migrate --path=database/migrations/2026_10_26_120000_create_favorites_table.php
```

A interface autenticada consulta essa tabela; a migration precisa ser aplicada antes de usar a versão atual nesse ambiente. Nenhuma alteração de `.env`, commit ou push foi realizada.

## Validação em 26/10/2026

- `vendor/bin/phpunit`: 326 testes, 2012 assertions, sem falhas; 7 ignorados por condições da configuração. Executado em cópia isolada, sem configuração em cache, com as proteções existentes e SQLite `:memory:`. A configuração em cache original foi preservada.
- `node --test resources/js/favorites.test.mjs`: 3 testes aprovados, incluindo métodos/CSRF, cliques repetidos, falhas de rede e sessão expirada.
- `npm run build`: aprovado; assets locais anteriores preservados.
- Pint nos arquivos PHP novos e modelos/testes envolvidos: aprovado. `git diff --check`: aprovado.
- Chrome: 20 verificações de layout nos tamanhos 375 e 1440, sem overflow nem exceções JavaScript. Percurso real de favoritar/desfavoritar na Home, Catálogo e Produto; sincronização de cards repetidos; remoção até o estado vazio; dropdown; Dashboard; pedidos e detalhes; Perfil e suas três âncoras; `/meus-dados`; cliente, visitante e administrador. Contas desativadas e isolamento entre clientes também cobertos nos testes PHP.
- Revisão de código independente: nenhum problema crítico ou importante encontrado.

Evidências locais ignoradas: `.artifacts/visual-reviews/account-favorites-browser-gfmrtQ/` (capturas e `results.json`) e `.artifacts/visual-reviews/account-favorites-VOeJi1/` (executor, fixtures sintéticas e logs). `git check-ignore` confirmou o armazenamento. Não há capturas temporárias na documentação. Os testes visuais usam fixtures sintéticas e SQLite em memória reconstruído por requisição; o estado dos favoritos entre requisições é transportado por um arquivo de fixture local. O banco persistente não foi utilizado. Não foi validado deploy nem concorrência em um servidor MySQL real; a unicidade foi exercitada no banco isolado.
