# ShopPlus — Etapa 6A: pedidos e rastreamento

Refinamento limitado à listagem, detalhes e acompanhamento privado de pedidos. Referências: ecommerce-ui-reference.png, design-system.md, contratos/capturas das etapas anteriores e implementação local atualizada da5B. Trabalho na main, base d9b63f9, preservando os arquivos não commitados da5B. Nenhum commit ou push nesta execução.

## Incidente na primeira execução de testes

**O primeiro comando PHPUnit foi executado sem forçar a conexão isolada.** phpunit.xml possui DB_CONNECTION/DB_DATABASE comentados; a conexão usada foi MySQL, banco shopplus_db. A suíte usa RefreshDatabase, que executa migrate:fresh na primeira preparação: portanto, houve uma operação de recriação de tabelas nessa conexão, contrariando a restrição do usuário. A evidência da conexão está na exceção da primeira execução. Essa execução terminou com8 falhas de apresentação e1 erro de fixture, antes das alterações de produção.

O erro foi informado ao usuário durante a execução. **Pode ter ocorrido perda dos dados anteriores do MySQL. Não existe um snapshot anterior desta execução para medir o impacto ou restaurá-los.** Não foi tentada restauração, repopulação ou outra operação no MySQL após identificar o problema. Não se deve interpretar as verificações seguintes como prova de preservação desse banco. Nenhum arquivo .env foi alterado.

Todas as execuções seguintes de PHPUnit usaram explicitamente SQLite em memória, DB_URL vazio. O navegador usou outro SQLite em diretório temporário, armazenamento e sessões temporárias, usuários/dados fictícios e chave sintética. O preparador do navegador verifica driver e caminho antes das migrations; recusa reutilizar arquivo existente. Não houve dependências instaladas.

Comando seguro usado nas verificações seguintes, em PowerShell:

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
php vendor/phpunit/phpunit/phpunit
```

Endurecer a configuração padrão de testes contra conexões não isoladas é uma pendência operacional separada. Não foi alterado phpunit.xml nesta revisão visual.

## Arquivos da6A

| Arquivo | Responsabilidade |
| --- | --- |
| app/Http/Controllers/OrderController.php | Somente eager loading de items.product.images; consultas de posse, paginação e retorno preservados. |
| resources/views/orders/index.blade.php | Cards, contagens, imagens, estados, navegação e paginação existente. |
| resources/views/orders/show.blade.php | Detalhes históricos, integração intacta de pagamento, rastreio e históricos separados. |
| resources/views/components/customer-navigation.blade.php | Nome do usuário e destinos existentes da conta, integrado somente em pedidos. |
| resources/views/components/order-status.blade.php | Tradução/variante de badges, separada pelo tipo do enum. |
| resources/views/components/order-item-image.blade.php | Imagem ilustrativa com proteção do SKU histórico e fallback. |
| resources/views/components/shipment-tracking.blade.php | Progresso com evidência, exceções, contexto e eventos reais ordenados. |
| resources/css/orders.css | Composição, tipografia, responsividade e foco escopados. |
| resources/css/app.css | Uma importação adicional de orders.css sobre a versão local5B. |
| tests/Feature/Orders/OrderPresentationTest.php | Dez testes de apresentação e contratos históricos/privados. |
| docs/design/design-system.md | Contratos implementados da6A, preservando documentação5B. |
| docs/superpowers/plans/2026-10-24-orders-tracking-refinement.md | Plano, decisões, progresso e incidente. |
| docs/design/reviews/2026-10-24-etapa-6a/ | Relatório,33 capturas e métricas do navegador. |

As alterações em CheckoutManager, checkout/show, checkout-manager, payment-form e checkout.css já existiam antes da6A. Não foram modificadas nesta etapa. orders/show e design-system foram atualizados sobre suas versões locais recentes, sem restaurar versões antigas.

## Resultado visual

- Container1120px, navegação208px a partir de1024px; conteúdo flexível. Mobile/tablet empilham navegação e conteúdo, sem menus sobrepostos. Identificação por nome, sem exposição adicional de e-mail. Apenas Visão geral, Meus pedidos e Dados pessoais, com rotas existentes; página atual destacada por aria-current.
- Cards brancos, bordas discretas, raios/sombras/tokens da fundação; navy em títulos e azul nas interações/foco. Títulos32/40px, nomes16/24px, valores18/26px, informações relevantes14px. Fallback de imagem usa12px como informação auxiliar.
- Listagem informa número público, data, status do pedido, total persistido, imagem ilustrativa e contagem de itens/unidades. Primeira imagem não sugere item único: e mais N itens fica explícito. Estado vazio tem orientação e catálogo real. Paginação continua10 por página; não há abas decorativas sem suporte funcional.
- Detalhes separam andamento da compra, logística, produtos, totais, endereço histórico, modalidade, registros financeiros fictícios e histórico do pedido. Pendentes mantêm pagamento5B antes dos detalhes, uma única vez e com largura inteira.
- Linha de progresso horizontal em1024px e vertical abaixo; números/checks e textos distinguem etapa atual, conclusão registrada, registro anterior, ausência e futuro. Não depende apenas das cores.

## Dados e fluxos preservados

1. Valores exibidos vêm de Order/OrderItem: nomes, SKUs, quantidade, preço unitário/total por item, subtotal, frete e total persistidos. Nenhum cálculo com preços atuais ou novas condições comerciais.
2. Endereço é o snapshot criptografado já autorizado ao dono. Não se expõem checkout_key, tokens de pagamento, idempotency_key, código de autorização ou dados de cartão completos. Bandeira/final/valor/status/data são os únicos campos financeiros exibidos nos registros.
3. Imagens cadastradas só são usadas quando o produto atual corresponde ao SKU histórico. Depois, asset demo conhecido pelo SKU histórico; por último, indisponibilidade. Produto soft-deleted ou SKU alterado não apaga o nome/SKU/preço histórico. Todas as imagens são identificadas como ilustrativas, pois não existe snapshot da imagem na compra.
4. Eventos logísticos são ordenados por created_at e id; históricos do pedido permanecem separados e recebem a mesma ordenação determinística. Blade escapa notas; nenhum ator interno adicional é mostrado.
5. Progresso usa apenas enum atual e evidência from/to dos eventos. Não preenche caminho completo por índice ou tempo. Etapas anteriores sem registro não são concluídas. Após recuperação de falha, etapas adiante com registro anterior não são marcadas como concluídas no percurso atual. Exceções failed/returned/cancelled têm aviso explícito e não mostram uma etapa normal como atual.
6. Não há transportadora, link externo, rastreio inventado ou data de entrega calculada. Código exibido somente do registro, identificado como fictício. Prazo contratado não é apresentado como data confirmada; shipped_at/delivered_at aparecem somente quando existem.
7. Pedido sem expedição e expedição sem eventos têm mensagens distintas. Devolução não implica reembolso; cancelamento logístico não altera o status financeiro ou da compra.
8. Formulário PaymentForm PHP/Blade, actions de pagamento/cancelamento/idempotência/estoque e todos os bindings permanecem intactos. Testes existentes de pagamento continuam aprovados.
9. Controller recebeu apenas eager loading; where user_id, public_number, firstOrFail e paginate(10) permanecem iguais. Rotas auth/no-store intactas. Outro usuário recebe404; visitante segue ao login.
10. Hashes de71 arquivos protegidos confirmaram ausência de mudanças em actions, models, enums, rotas, layout, checkout e formulário5B. Isso comprova preservação dos arquivos, não preservação do MySQL afetado pelo incidente inicial.

## Verificação

- npm.cmd run build: aprovado, CSS146,16KB (gzip21,75KB); JS51,52KB. Sem dependências novas.
- OrderPresentationTest + PaymentPresentationTest + PaymentInterfaceTest + ShipmentLifecycleTest: **20 testes,171 assertions, aprovados**.
- Testes cobrem: lista própria/paginação/vazio, sete estados do pedido, oito estados logísticos, snapshots após alteração/exclusão do produto, prioridade de imagens, eventos incompletos/desordenados, conclusão com evidência, exceções e recuperação válida, ausência de eventos/expedição, mascaramento, formulário único e isolamento entre usuários.
- Suíte completa SQLite:218 testes,1115 assertions,208 aprovados,7 ignorados,3 falhas conhecidas. Comparação dos nomes de falha com JUnit5B: nenhuma nova.
  - AdminAccessTest::test_administrator_navigation_shows_the_administration_link — navegação administrativa anterior.
  - ExampleTest::test_the_application_returns_a_successful_response — tabela categories ausente no contexto desse teste.
  - SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers — mesma ausência de categories.
- Revisão independente sem problemas bloqueantes. Sugestão aceita: substituir fixture inconsistente de recuperação por Shipped→OutForDelivery→Failed→Preparing e conferir histórico preservado/futuro não concluído.
- git diff --check: sem erros.

## Navegador e capturas

Chrome headless em rotas Laravel reais, login real, viewport CSS320/375/768/1024/1280/1440, altura1000, DPR1/zoom conhecido100%. A barra de rolagem pode reduzir a largura útil em15px. Não foi inferido zoom ou medida CSS da referência oficial.

**96 combinações:** listagem com13 pedidos (primeira página10), um pedido e vazio; detalhes nos oito estados de entrega, pedido pendente/cancelado/reembolsado, expedição sem eventos e conteúdo longo, em seis larguras. Sem overflow horizontal, exatamente um header/main/footer/h1 e controles≥44px dentro da viewport. Paginação conferida10+3. Imagens carregadas em contain; falha de URL em runtime mostra fallback.

Tab move entre ações com outline sólido2px; Enter em Ver detalhes navega ao pedido real. Não foi alterado comportamento de foco ou loading do pagamento5B. Nenhuma cobrança, transição de estoque ou cancelamento novo foi executado no navegador6A; esses contratos são cobertos pelos testes PHP existentes em SQLite.

[Métricas e verificações](validation.json).

| Cenário | Desktop | Mobile |
| --- | --- | --- |
| Lista de pedidos | [1440px](orders-many-1440.png) | [375px](orders-many-375.png) |
| Um pedido / imagem indisponível | [1440px](orders-single-1440.png) | [375px](orders-single-375.png) |
| Sem pedidos | [1440px](orders-empty-1440.png) | [375px](orders-empty-375.png) |
| Em transporte | [1440px](order-shipment-shipped-1440.png) | [375px](order-shipment-shipped-375.png) |
| Aguardando preparação | [1440px](order-shipment-awaiting_processing-1440.png) | [375px](order-shipment-awaiting_processing-375.png) |
| Em preparação | [1440px](order-shipment-preparing-1440.png) | [375px](order-shipment-preparing-375.png) |
| Saiu para entrega | [1440px](order-shipment-out_for_delivery-1440.png) | [375px](order-shipment-out_for_delivery-375.png) |
| Entrega concluída | [1440px](order-shipment-delivered-1440.png) | [375px](order-shipment-delivered-375.png) |
| Falha | [1440px](order-shipment-failed-1440.png) | [375px](order-shipment-failed-375.png) |
| Devolvida | [1440px](order-shipment-returned-1440.png) | [375px](order-shipment-returned-375.png) |
| Entrega cancelada | [1440px](order-shipment-cancelled-1440.png) | [375px](order-shipment-cancelled-375.png) |
| Pedido cancelado | [1440px](order-cancelled-1440.png) | [375px](order-cancelled-375.png) |
| Pedido reembolsado | [1440px](order-refunded-1440.png) | [375px](order-refunded-375.png) |
| Pagamento pendente único | [1440px](order-pending_payment-1440.png) | [375px](order-pending_payment-375.png) |
| Sem eventos | [1440px](order-no-events-1440.png) | [375px](order-no-events-375.png) |
| Conteúdo extenso | [1440px](order-long-1440.png) | [375px](order-long-375.png) |

[Falha de imagem em runtime](order-image-fallback-375.png). As capturas contêm exclusivamente dados fictícios de teste. Foram inspecionadas visualmente as composições desktop/mobile de listagem, transporte e pagamento, além das medições automáticas de todos os cenários.

## Divergências e pendências6B

- A identidade, o alinhamento, os cards e o progresso seguem a referência; conteúdo e estágios são os do ShopPlus. Não foram copiados links externos, transportadora ou previsão sem dados. A navegação tem somente destinos implementados; fotos de avatar e produtos da referência não foram fabricadas. Assets locais são ilustrações.
- Cards da listagem são mais altos que as linhas compactas da referência para acomodar imagens, contagens e domínios de status distintos com fontes legíveis. Ausência de filtros reais impede abas funcionais nesta etapa.
- A data contratada não possui regra inequívoca de calendário/dias úteis ou deadline persistido; previsão calculada exige decisão funcional posterior. Imagem histórica exata também exigiria snapshot próprio, fora do escopo.
- Navegação compartilhada foi integrada somente às telas de pedidos. Dashboard, dados pessoais, configurações e segurança permanecem para6B, sem novos links ou reorganização global.
- A paginação herda o idioma/tema existente da aplicação; não houve tradução global. As três falhas anteriores dos testes permanecem pendentes.
- Não foram validados dispositivos físicos, Safari/Firefox, leitores de tela ou conformidade WCAG integral. A confirmação nativa de cancelamento mantém a limitação documentada na5B; não foi revalidada manualmente nesta etapa.
- Incidente do MySQL requer avaliação do usuário e eventual restauração a partir de backup externo, se existir. Não foi tentada nenhuma ação de recuperação automática.
