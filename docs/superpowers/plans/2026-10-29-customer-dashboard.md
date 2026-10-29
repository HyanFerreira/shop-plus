# Dashboard do cliente

**Objetivo:** implementar a composição da referência usando exclusivamente a identidade e o domínio existentes do ShopPlus. Especificação: solicitação fornecida pelo usuário em 29/10/2026.

**Arquitetura:** manter `Account\Overview`, consultas autenticadas e limite de três pedidos. O shell da Dashboard passa a usar diretamente o layout existente, permitindo que o hero fique dentro do componente. Estilos próprios ficam em `resources/css/dashboard.css`, carregados apenas nesta página. Não alterar componentes compartilhados, regras de negócio, migrations ou seeders. Sem commits ou push.

**Escolha de implementação:** reaproveitar os componentes globais de ícone, status, imagem, painel e classes oficiais de botão. O destaque será “Sua conta, do seu jeito”, com acesso à segurança; não há fidelidade. O progresso representa enums de pedido e só marca etapas anteriores com evidência no histórico. O status logístico aparece separadamente, incluindo exceções.

- [x] Ampliar testes de apresentação: favoritos por usuário, limite/ordenação de recentes, todos os status, histórico sem etapas presumidas e relações pré-carregadas. Executar em SQLite `:memory:` e confirmar falhas das novas expectativas.
- [x] Implementar hero, quatro indicadores, pedidos com acompanhamento e três atalhos. Usar Figtree, tokens e container global; secondary de 40px. Preservar link administrativo autorizado.
- [x] Executar testes relevantes e suíte completa isolada, build e revisão do diff. Validar navegador em desktop, tablet e mobile com pedidos, sem pedidos e estados excepcionais. Guardar evidências apenas em `.artifacts/visual-reviews/` via `visualReviewOutput`.
- [x] Confirmar `git check-ignore` das evidências e `git status --short --untracked-files=all`. Relatar resultados e limitações sem criar commits.

**Foco da revisão:** pedidos alheios; favorito alheio; pedidos entregues sem histórico; cancelamento/reembolso e falhas logísticas; nome/número longo em 320px. Links devem usar rotas reais, sem filtros que a página de pedidos não implementa.

## Ampliação solicitada: setas globais e alinhamento das timelines

- [x] Centralizar em `x-arrow` o desenho existente, com 20px, traço 2.5, direção e proporção uniformes; migrar ações e paginações Laravel/Livewire sem mudar destinos, comandos ou chevrons de dropdowns. Retirar desenhos/estilos redundantes.
- [x] Definir as três regiões de cada pedido no desktop com a mesma grade, reservando a região direita por token e distribuindo cinco etapas em colunas independentes do tamanho dos labels/botões. Manter adaptações para telas menores.
- [x] Build e suíte isolada; 35 combinações da Dashboard, incluindo 1280px, medindo alinhamento por região e por etapa, labels sem quebra no desktop e botões à direita. Ampliar verificação global de setas e paginação em rotas reais.

## Refinamento solicitado: contraste e apresentação dos estados

- [x] Presenter específico da Dashboard sem escritas ou novas consultas: separar concluído, próxima etapa aguardada, andamento real e interrupção; integrar a preparação antes apresentada em bloco separado, preservando exceções e histórico parcial.
- [x] Neutros legíveis, primary/azul suave, halo compacto, conectores de 2px e cinco colunas iguais; apresentação vertical abaixo de 640px, sem alterar outras seções.
- [x] Testes de espera versus execução, cancelamento durante preparo e ausência de conclusões inventadas; revisão independente e correção do caso interrompido. Build, suíte completa isolada e 72 combinações visuais aprovados; evidências no relatório existente.
