# Área da Conta e Favoritos — plano de implementação

**Objetivo:** aplicar a especificação fornecida pelo usuário, reutilizando Laravel, Livewire, Alpine e o design system atual.

**Decisões:** Perfil terá seções internas com âncoras (sem duplicar formulários); `/meus-dados` continuará funcional. Favoritos serão uma relação Eloquent muitos-para-muitos com unicidade no banco, POST/DELETE idempotentes e usuário obtido exclusivamente da sessão. Produtos inativos, de categorias inativas ou excluídos não serão expostos; produtos ativos sem estoque continuam salvos e indicam indisponibilidade.

**Restrições:** preservar alterações locais; não modificar `.env`, regras de compra ou proteções existentes; não executar migrations persistentes, `migrate:fresh`, `db:wipe`, commits ou push. Evidências temporárias somente em `.artifacts/visual-reviews/`.

- [x] Backend: testes de autenticação, isolamento por usuário, IDs inválidos, repetição, unicidade, produtos indisponíveis/excluídos, preços atuais e paginação; migration, relação e controller.
- [x] Interface: dropdown na ordem Perfil/Pedidos/Favoritos/Visão geral/Sair; remover renderização da sidebar e sua coluna; reunir formulários em Perfil e preservar acesso administrativo pelo dashboard.
- [x] Coração: componente reutilizado em Home/Catálogo/Produto/Favoritos, POST/DELETE com CSRF, atualização assíncrona, sincronização dos cards repetidos, erro acessível e login para visitantes.
- [x] Validar: testes PHP em SQLite `:memory:`, build, testes de browser em desktop/mobile com dados sintéticos isolados; revisar diff e documentar resultados e comando de migration posterior.

**Revisão:** cobrir CSRF real, sessão expirada, ID de outro usuário em payload, filtros/paginação Livewire e remoção do último favorito. Logout continua POST com CSRF. Formulários e ações pessoais existentes continuam sob os testes atuais.

Resultados e comando para aplicação posterior: [Área da Conta e Favoritos](../../account-favorites.md).
