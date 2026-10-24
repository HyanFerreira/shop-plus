# ShopPlus — Etapa 2: estrutura compartilhada

**Objetivo:** consolidar header/footer e tornar a navegação e busca utilizáveis em todas as larguras, preservando a Etapa 1 aprovada.
**Especificação:** solicitação da Etapa 2 nesta conversa e referência oficial em docs/design/ecommerce-ui-reference.png.
**Execução:** nesta sessão, sem commits, instalações ou alterações de backend.

## Decisões e escopo

- Criar apenas uma classe de container do storefront, 70rem (1120px com raiz 16px), com gutters de 16/24px. Manter ds-container e larguras internas existentes.
- Home usa x-storefront-layout; header/footer compartilhados preservam destinos existentes, busca GET q, carrinho e estados visitante/autenticado.
- Header com navegação sempre visível: três linhas no mobile, duas no tablet e uma a partir de 1024px. Sem menus ocultos, overlays ou bibliotecas.
- Adicionar busca com submit nomeado e atalho de teclado para o conteúdo. Não alterar bindings Livewire/Alpine ou lógica dos filtros.
- Integrar container somente nos wrappers de home, catálogo e produto; não modificar conteúdo, hero, imagens, cards ou filtros.
- Footer mantém conteúdo existente, com tipografia legível e colunas responsivas, sem inventar destinos para itens que hoje são texto.
- Comparar as três falhas conhecidas com o baseline; não corrigi-las.

## Tarefas

- [x] Registrar Git/baseline e escrever verificações de estrutura e busca.
- [x] Implementar header/footer compartilhados e CSS responsivo com tokens existentes.
- [x] Integrar home e containers; documentar o contrato de estrutura.
- [x] Build e suíte existente isolada em SQLite em memória.
- [x] Validar home/catálogo reais em 320/375/768/1024/1280/1440px para visitantes e autenticados; teclado, foco, busca e landmarks.
- [x] Capturar telas, revisar diff/escopo e relatar pendências.

## Evidências

- Baseline inicial: alterações aprovadas da Etapa 1 preservadas, sem commit.
- Seis testes de estrutura/busca falharam antes e passaram após a implementação (66 assertions).
- Build passou; suíte: 173 aprovados, 7 ignorados, mesmas 3 falhas conhecidas; banco em memória.
- 24 combinações no Chrome headless passaram, incluindo login real e busca GET com banco temporário separado.
- Revisão independente identificou ordem inadequada dos overrides da busca no CSS; movidos após os estilos base e verificados padding/radius no navegador.
- Capturas e relatório preservados em docs/design/reviews/2026-10-23-etapa-2.
- Limites: sem dispositivos físicos, outros navegadores, leitor de tela ou zoom real de 200%.

## Riscos a observar

- Nomes longos não podem empurrar logo, carrinho ou busca.
- Exatamente um header e footer em home/catálogo, com foco visível e ordem de Tab coerente.
- Conteúdo interno pré-existente pode transbordar; medir sem mascarar com overflow-x:hidden e registrar problemas fora do escopo.
- Não usar dimensões da composição aprovada como pixels CSS; registrar viewport CSS e escala das capturas.
