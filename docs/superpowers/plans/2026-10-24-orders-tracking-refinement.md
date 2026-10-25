# ShopPlus — Etapa 6A: pedidos e rastreamento

> **For agentic workers:** Use superpowers:executing-plans. Execução nesta sessão, sem commits, conforme solicitação.

**Goal:** Refinar exclusivamente listagem, detalhes e rastreamento privado dos pedidos.
**Architecture:** Blade e CSS escopado, componentes pequenos de navegação, status, imagem histórica e rastreio. Controller recebe somente eager loading de relações de apresentação; autorização e paginação continuam iguais.
**Tech Stack:** Laravel, Blade, Livewire, Alpine, Tailwind, Figtree e tokens --ds-* existentes.
**Spec:** solicitação Etapa 6A no anexo ea909c25-e4e7-44c2-8311-05505e5b0c82/Pasted text.txt; referência docs/design/ecommerce-ui-reference.png.

## Restrições e decisões

- Main em d9b63f9; alterações locais da 5B preservadas. Sem commit, push, dependências ou alterações em .env/banco de desenvolvimento.
- Ruling: manter esta workspace/main autorizada na conversa, sem mover alterações locais para outra árvore.
- Ruling: implementar a especificação detalhada diretamente; não introduzir outra aprovação de design para o escopo já autorizado.
- Não alterar actions, models, enums, rotas, segurança, checkout, formulário de pagamento ou telas anteriores.
- Snapshots de nome/SKU/preço/quantidade e totais do pedido são autoridade. Produto atual fornece somente imagem quando o SKU corresponde; SKU histórico conhecido pode usar asset demo mesmo sem produto atual.
- Navegação da conta somente nas telas de pedidos: nome, dashboard, pedidos e dados pessoais existentes; sem repetir navegação global ou criar destinos.
- Não há filtro de status no controller: sem abas decorativas.
- Status do pedido, pagamento e entrega são mapeados separadamente para português em componente de apresentação; nenhuma transição nova.
- Progresso usa status atual e evidência nos eventos (from/to). Etapas sem evidência não são concluídas. Recuperação após falha não apaga histórico nem considera etapas futuras concluídas. Exceções têm mensagem explícita.
- Sem data calculada de entrega: informar prazo contratado e datas reais registradas, sem inferir dias úteis/corridos ou conclusão pelo tempo.
- Pagamento pendente mantém formulário único antes dos itens; ocupa largura inteira para preservar responsividade 5B.

## Review Focus

1. Produto alterado/excluído: snapshots intactos, imagem nunca de SKU diferente.
2. Falha/devolução/cancelamento ou recuperação: não marcar entrega concluída por inferência.
3. Evento ausente/desordenado/mesmo instante: ordenação por created_at e id, ausência explícita.
4. Outro usuário/visitante: isolamento e cache no-store preservados.
5. Pagamento pendente e nomes/números/notas longos: formulário único, ações preservadas e sem overflow.

## Task 1 — contratos de apresentação

- [x] Criar testes em tests/Feature/Orders/OrderPresentationTest.php: listagem própria/paginação/vazio; snapshots/imagens; todos os enums; rastreio incompleto/excepcional/recuperação; pagamento único/privacidade.
- [x] Executar testes e registrar RED para recursos ausentes.

## Task 2 — telas e componentes

**Files:** resources/views/orders/{index,show}.blade.php; resources/views/components/{customer-navigation,order-status,order-item-image,shipment-tracking}.blade.php; resources/css/orders.css; resources/css/app.css; app/Http/Controllers/OrderController.php (somente eager loading).
**Interfaces:** consome Order/OrderItem/Shipment e enums existentes; não produz comandos ou transições.

- [x] Implementar listagem e navegação; cards com contagem de itens/unidades e link real; paginação existente.
- [x] Implementar detalhes, resumo histórico, entrega, pagamento mascarado e históricos separados.
- [x] Implementar progresso acessível, exceções, eventos cronológicos, código fictício e estados sem rastreio/eventos.
- [x] Executar testes direcionados (GREEN) e build.

## Task 3 — verificação e entrega

- [x] Testes completos em SQLite isolado; comparar as três falhas anteriores da 5B.
- [x] Navegador real em 320/375/768/1024/1280/1440; vazio/um/vários/paginação/todos os estados/rastreio/pendente; teclado, foco, imagens e overflow.
- [x] Capturas desktop/mobile, revisão independente e correções justificadas.
- [x] Conferir hashes dos arquivos protegidos, git diff --check e atualizar docs/design/design-system.md e relatório com limites/pendências 6B.

## Ledger

- Pre-flight: status e imagens são componentes compartilhados entre index/show; progresso consome os mesmos enums, mas não representa status financeiro. Pagamento é independente e permanece intacto.
- Baseline 5B: 208 testes, 988 assertions, 198 aprovados, 7 skips, 3 falhas conhecidas (AdminAccessTest, ExampleTest, SecurityHeadersTest).
- Incidente: primeiro comando PHPUnit omitiu DB_CONNECTION/DB_DATABASE. Conectou MySQL shopplus_db e RefreshDatabase executa migrate:fresh. Pode ter removido dados anteriores; sem snapshot, impacto não mensurável. Informado ao usuário durante a execução. Não restaurar/repopular sem solicitação. Comandos seguintes explicitamente SQLite; navegador com guarda de driver e caminho temporário.
- RED isolado: nove falhas de apresentação ausente; fixtures corrigidas para processed_at obrigatório e created_at não mass-assignable. GREEN final direcionado:20 testes,171 assertions.
- Revisão independente sem bloqueantes; ajustado teste de recuperação para sequência válida Shipped→OutForDelivery→Failed→Preparing, mantendo eventos e futuro não concluído.
- Navegador:96 combinações em seis larguras, sem overflow, landmarks únicos, controles≥44px; paginação10+3, teclado/foco, fallback de imagem, outro usuário404 e visitantes/login. Capturas e métricas no relatório.
- Hashes:71 arquivos protegidos sem diferenças (incluindo actions/models/enums, PaymentForm PHP/Blade, checkout, layout e rotas).
- Suíte final SQLite:218 testes,1115 assertions;208 aprovados,7 skips, mesmas3 falhas da5B. Nenhuma falha nova.
