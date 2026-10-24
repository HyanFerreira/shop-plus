# ShopPlus — Etapa 3A: Home

**Especificação:** solicitação anexada da Etapa 3A; referência oficial e contratos das Etapas 1/2.
**Execução:** somente Home, sem commits/dependências; preservar header/footer, catálogo e dados comerciais.

## Decisões

- Ilustrações vetoriais locais próprias, específicas para os 16 SKUs demo; sem fotos/logos reais, hotlinks ou dependência de geração remota. Documentar autoria e licença.
- Manifesto visual único para Home e seeder exclusivo de imagens. A Home reconhece somente os SKUs demo; fotos cadastradas existentes têm precedência. Produtos desconhecidos sem imagem mantêm fallback explícito.
- Seeder de imagens idempotente; só associa imagens de demonstração, sem alterar produtos, estoques, preços, usuários ou pedidos. Não executar o DatabaseSeeder completo no banco de desenvolvimento.
- Um componente de card para ofertas e mais vendidos; imagens contain, nomes completos >=14px, preços 18–20px, metadados legíveis e sem favoritos fictícios.
- Hero navy com asset original inteiro, texto separado no mobile e composição com imagem à direita no desktop.
- Categorias e banners refinados com estilos exclusivos da Home e tokens existentes.
- Benefícios limitados às informações suportadas pelo projeto; newsletter explicitamente indisponível e seção mobile divulga a experiência responsiva, sem app/QR/download inventados.

## Tarefas

- [x] Registrar baseline e adicionar verificações de imagens/idempotência e conteúdo honesto.
- [x] Criar ilustrações, manifesto, seeder de associação e documentação de origem.
- [x] Refatorar Home e card reutilizável com CSS restrito à Home.
- [x] Build, testes e comparação com as três falhas anteriores.
- [x] Validar 12 combinações de viewport/sessão, imagem ausente, nomes longos, teclado e proporções; capturar e inspecionar visualmente.
- [x] Documentar diferenças visuais, resultados, pendências e limites sem avançar ao catálogo.

## Evidências

- Testes escritos antes da implementação; 5 testes finais passaram com 96 assertions.
- Revisão identificou prioridade incorreta quando a primeira imagem cadastrada estava ausente: caso reproduzido em teste, corrigido para procurar a primeira disponível.
- Build passou; suíte final 178 aprovados/7 ignorados/3 falhas anteriores, sem novas falhas.
- 12 combinações no navegador passaram; 16 assets renderizados, imagens inteiras, tipografia e grid medidos, sem overflow ou sobreposição.
- Capturas inspecionadas e relatório preservado em docs/design/reviews/2026-10-23-etapa-3a.
- Header/footer e alterações aprovadas anteriores preservados; banco de desenvolvimento, dados comerciais e catálogo não foram alterados nesta etapa.
