# ShopPlus — Etapa6B: Dashboard e conta

> **For agentic workers:** Use superpowers:executing-plans; implementação nesta sessão, sem commits.

**Goal:** Unificar apresentação do Dashboard, dados pessoais e perfil/segurança com o storefront aprovado.
**Architecture:** Dashboard Livewire somente leitura; navegação6A reutilizada; wrapper Blade simples para páginas da conta; CSS escopado. Formulários/ações Jetstream e PersonalData.Manager preservados.
**Tech Stack:** Laravel, Blade, Livewire, Alpine, Tailwind, Figtree e tokens existentes.
**Spec:** pedido Etapa6B desta conversa e docs/design/ecommerce-ui-reference.png.

## Restrições e decisões

- Trabalho na main atual, sem descartar alterações anteriores. R1 interrompida por mudança explícita de escopo; não continuar investigações, backups ou operações MySQL/infraestrutura.
- Somente SQLite :memory: com os guards existentes; nenhum teste em banco persistente. Dados de demonstração exclusivamente fictícios, em memória.
- Não alterar actions/models/migrations/rotas/regras de negócio/auth/segurança ou guardas de testes. Não tocar Home/Catálogo/Produto/Carrinho/Checkout nem views de pedidos6A.
- Reutilizar customer-navigation e order-status/order-item-image; adicionar wrapper simples e Overview de leitura, sem novos comandos comerciais.
- Navegação compartilhada usa somente rotas existentes, active por rota, admin condicionado a isAdmin e logoutPOST/CSRF. Mobile usa details nativo compacto; desktop sidebar208px.
- Perfil troca apenas shell Jetstream por storefront na rota profile.show; api-tokens permanece como antes. Seções Jetstream recebem classes/Português, preservando componentes/bindings/condições/confirmações.
- Dashboard deriva contagens de estados reais e últimos3 pedidos do usuário, sem valores globais/estatísticas fictícias.

## Review focus

- Outro usuário não aparece em contagens/recentes; cliente não vê administração.
- Remoção do menu Jetstream não pode remover logout nem duplicar menus.
- Máscaras de telefone/CEP e validações/criptografia preservadas; só edição mostra valores já autorizados.
- Modais, confirmações por senha e 2FA não perdem bindings, foco ou leitura de erros.
- Sidebar mobile, títulos longos e cards/formulários não provocam overflow em320px.

## Tasks

- [x] Testes de apresentação/posse/nav/mascaramento/contratos e RED, somente ambiente protegido.
- [x] Wrapper, Overview e navegação compartilhada; integrar dashboard/dados/perfil sem duplicar header/footer.
- [x] Refinar templates existentes, loading/erros/estados e CSSaccount; preservar componentes e métodos.
- [x] Build, testes existentes, comparação baseline222 testes/212pass/7skip/3falhas.
- [x] Capturas reais em seis larguras, fixtures SQLite em memória; testes funcionais Livewire no PHPUnit; revisão independente.
- [x] Documentar arquivos, contratos, impacto6A, limites e pendências. Sem avançar autenticação ou outras telas.
