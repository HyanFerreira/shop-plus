# Home — revisão visual da Etapa 3A

## Evidência visual

- [Desktop: dobra inicial, 1440px](home-top-visitor-1440.png)
- [Desktop: Home inteira, 1440px](home-visitor-1440.png)
- [Mobile: dobra inicial, 375px](home-top-visitor-375.png)
- [Mobile: Home inteira, 375px](home-visitor-375.png)
- [Tablet autenticado, 768px](home-authenticated-768.png)
- [Autenticado, 1024px](home-authenticated-1024.png)
- [Nome longo, 375px](home-long-name-375.png)
- [As 16 ilustrações originais](demo-assets-contact-sheet.png) — prancha de inspeção de assets, não uma página do produto.
- [Medições completas das 12 combinações](validation.json)

## Comparação com a referência e a Etapa 2

| Aspecto | Antes (Etapa 2) | Resultado da Etapa 3A |
| --- | --- | --- |
| Imagens de produtos | Mesma sacola quando não havia associação | 16 ilustrações próprias específicas por SKU, com fotos cadastradas disponíveis tendo precedência |
| Nomes/preços/avaliações | 10px / 14px / 10px na Home | 14px / 18–20px / 14px; nomes completos, espaçamento e altura natural |
| Hero desktop | 1072 × 260px, proporção ~4.12:1, recorte por cover | 1072 × 340px, ~3.15:1; mais próximo da proporção ~3:1 da referência, headphone inteiro por contain |
| Atalhos | Círculos de 40px e legendas de 9px | Círculos de 56/60px, ícones 28px e legendas 14px |
| Cards | Implementações diferentes entre as duas seções | Um componente compartilhado, mídia 4:3, imagem inteira, bordas e foco consistentes |
| Categorias em destaque | Ícones sobre fundos simples | Representações temáticas locais de eletrônicos, informática, casa, moda, esportes e beleza |
| Banner mobile | Blocos representando aparelhos e QR fictício | Ilustração original de dispositivos com vitrine e link real para o catálogo |
| Newsletter | Formulário sem serviço real | Campo/botão desabilitados e aviso de indisponibilidade, sem coleta ou sucesso simulado |
| Benefícios | Frete grátis acima de R$199 e 12x sem suporte nas regras | Informações sobre entrega, pagamento de demonstração e acesso à conta, nas mesmas famílias de cores |

A referência mantém autoridade sobre a direção azul/navy/branco e a composição. Os novos assets são ilustrações vetoriais de produtos fictícios, **não fotografias**: preservam o tipo de objeto e evitam marcas reais, mas não pretendem reproduzir os produtos fotografados no mockup. Não foram inventados descontos, preços antigos ou avaliações.

O header/footer da Etapa 2 e os dados/ordem do HomeController foram preservados. A diferença dos produtos visíveis em relação às capturas da Etapa 2 decorre do uso dos 16 produtos do seeder oficial no banco temporário, em vez dos nove produtos genéricos daquela validação.

## Condições e resultados

- Chrome headless, zoom 100% confirmado por visualViewport.scale=1, DPR 1, altura de viewport 1000px.
- Viewports CSS: 320, 375, 768, 1024, 1280 e 1440px; visitantes e autenticados por login real (12 combinações).
- Capturas de página inteira podem ter largura útil 15px menor que a viewport por causa da barra de rolagem. Não se presume o zoom da referência oficial.
- Grids de produto medidos: 1/2/3/4/5/5 colunas respectivamente, com nomes >=14px e preços >=18px.
- Todas as imagens da Home carregadas e com object-fit contain; os 16 assets também renderizados e inspecionados na prancha.
- Zero overflow horizontal, zero sobreposição de cards, exatamente um header e um footer; CTA do hero dentro de sua área.
- Nome longo expandiu sem corte; falha de carregamento acionou fallback visível; teclado validado no atalho para conteúdo e na abertura do produto pelo card.
- Newsletter indisponível, sem formulário de envio ou promessa de inscrição; sem ações fictícias de favoritos, lojas de apps ou QR.
- Build passou. Cinco testes de apresentação passaram (96 assertions), cobrindo prioridade de fotos, primeira imagem ausente, fallback, preservação de atributos, 16 assets válidos, idempotência e conteúdo honesto.
- Suíte: 188 testes, 178 aprovados, 7 ignorados e 3 falhas idênticas à Etapa 2, com 789 assertions. Falhas: AdminAccessTest::test_administrator_navigation_shows_the_administration_link; ExampleTest::test_the_application_returns_a_successful_response; SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers.
- Os dois testes da home acima não preparam a tabela categories no banco em memória. Não foram corrigidos nesta etapa.

## Dados e limites

A validação usou SQLite e disco public temporários, com os seeders executados apenas nesse ambiente. Nenhum seeder geral ou operação destrutiva foi executado no banco de desenvolvimento. A Home já resolve ilustrações dos SKUs demo sem precisar gravar associações; o seeder exclusivo é opcional e documentado no design system.

Todos os dados comerciais foram preservados. O catálogo não foi refatorado. Não há fotos pendentes para os 16 SKUs cobertos; novos SKUs ou produtos reais precisam de imagens próprias cadastradas. Newsletter, eventual aplicativo móvel e parcelamento continuam fora do escopo funcional.

Não validado em dispositivos físicos, Safari/Firefox ou leitores de tela. A composição mobile adapta a referência para leitura; não é uma reprodução pixel a pixel do mosaico original.
