# Design do Sistema de Comércio

## Objetivo

Construir um e-commerce monolítico de loja única para fins acadêmicos. O sistema deve demonstrar cadastro e proteção de dados pessoais sintéticos, autenticação e recuperação de senha, compras da loja junto a fornecedores, vendas a clientes, pagamento e entrega simulados, administração, auditoria e controles alinhados às boas práticas OWASP.

Nenhum dado pessoal ou financeiro real será utilizado. O sistema preservará formatos realistas para demonstrar tratamento adequado de nomes, CPFs, telefones, endereços e cartões de teste.

## Arquitetura

A aplicação permanecerá um monólito Laravel 12 com Livewire e MySQL. O código será organizado por domínios internos com limites claros, mantendo uma única implantação e uma única transação de banco quando um fluxo atravessar módulos.

Domínios:

- Identidade e acesso
- Clientes e dados pessoais
- Catálogo
- Fornecedores e compras
- Estoque
- Carrinho e vendas
- Pagamentos simulados
- Entregas simuladas
- Auditoria e segurança

Controllers e componentes Livewire serão finos. Regras de negócio ficarão em Actions/Services focados, autorização em Policies, validação em Form Requests ou regras Livewire, estados em enums e persistência em modelos Eloquent.

## Perfis e autorização

### Cliente

O cliente pode:

- cadastrar-se, autenticar-se e recuperar sua senha;
- ativar 2FA e gerenciar sessões;
- manter somente seus próprios dados pessoais, telefones e endereços;
- consultar catálogo e disponibilidade;
- manter carrinho, concluir checkout e pagar de forma simulada;
- consultar somente seus próprios pedidos, pagamentos e entregas;
- cancelar pedidos apenas quando o estado permitir.

### Administrador

O administrador pode:

- gerenciar categorias, produtos, imagens e fornecedores;
- emitir e receber ordens de compra;
- acompanhar e ajustar estoque com justificativa;
- gerenciar pedidos, pagamentos simulados e entregas;
- consultar auditoria com dados sensíveis mascarados;
- criar ou desativar outros administradores conforme política explícita.

O perfil será persistido como enum controlado na tabela de usuários. Toda ação protegida terá Policy ou Gate; esconder links na interface nunca substituirá autorização no servidor.

## Dados pessoais sintéticos

### Regras gerais

- Seeders e factories produzirão somente identidades declaradamente fictícias.
- CPF aceitará o formato `000.000.000-00`, validará dígitos verificadores e rejeitará sequências repetidas.
- Dados exibidos serão mascarados quando o valor completo não for necessário.
- Campos criptografados usarão a criptografia autenticada do Laravel.
- Campos que exigem unicidade ou busca terão blind index HMAC separado, sem persistir o texto puro.

### Entidades

`customer_profiles`:

- `user_id` único;
- `cpf_encrypted`;
- `cpf_hash` único;
- data de nascimento opcional;
- timestamps.

`phones`:

- proprietário;
- número criptografado;
- hash normalizado;
- tipo e indicador principal.

`addresses`:

- proprietário;
- rótulo, destinatário, CEP, logradouro, número, complemento, bairro, cidade e UF;
- conteúdo pessoal criptografado;
- indicador principal e timestamps.

Pedidos manterão um snapshot criptografado do endereço de entrega. Alterações posteriores na agenda do cliente não modificarão pedidos existentes.

## Catálogo

`categories` terá nome, slug, descrição, status e exclusão lógica.

`products` terá categoria, nome, slug, SKU único, descrição, preço em centavos, peso e dimensões, status e exclusão lógica.

`product_images` terá caminho gerado pelo servidor, texto alternativo e ordenação. Uploads aceitarão apenas tipos de imagem permitidos, tamanho limitado e conteúdo validado pelo MIME real.

O catálogo público terá busca textual, filtro por categoria, ordenação e paginação. Produtos inativos ou excluídos não poderão ser adicionados ao carrinho.

## Fornecedores e compras da loja

`suppliers` armazenará razão/nome fictício, documento sintético, contato e endereço comercial protegidos.

`supplier_products` relacionará fornecedor e produto com código externo e custo em centavos.

`purchase_orders` terá número público não sequencial, fornecedor, estado, totais e datas. Estados:

- `draft`
- `placed`
- `partially_received`
- `received`
- `cancelled`

`purchase_order_items` manterá produto, quantidade solicitada, recebida e custo unitário congelado.

O recebimento poderá ser parcial. Cada recebimento criará movimentos de estoque idempotentes. Uma ordem recebida ou cancelada não poderá voltar para rascunho.

## Estoque

`inventory_items` manterá por produto:

- quantidade física (`on_hand`);
- quantidade reservada (`reserved`);
- disponibilidade derivada como `on_hand - reserved`;
- nível mínimo.

`stock_movements` será o livro razão imutável. Tipos:

- compra recebida;
- reserva de venda;
- liberação de reserva;
- baixa por venda;
- devolução;
- ajuste positivo ou negativo.

Reservas, baixas, recebimentos e cancelamentos ocorrerão em transações MySQL com bloqueio pessimista da linha de estoque. Chaves idempotentes impedirão duplicidade. Ajustes manuais exigirão justificativa e administrador responsável.

## Carrinho, checkout e pedidos

`carts` pertencerá ao usuário autenticado e terá um único carrinho ativo.

`cart_items` armazenará produto e quantidade. Preço, disponibilidade, descontos e frete sempre serão recalculados no servidor; valores enviados pelo navegador nunca serão confiados.

O checkout terá quatro etapas:

1. confirmação dos itens;
2. seleção ou criação de endereço;
3. seleção de entrega simulada;
4. pagamento simulado e confirmação.

`orders` terá número público aleatório, cliente, estado, valores em centavos, snapshot do endereço e timestamps relevantes. Estados:

- `pending_payment`
- `paid`
- `processing`
- `shipped`
- `delivered`
- `cancelled`
- `refunded`

`order_items` congelará nome, SKU, preço, quantidade e totais. Nenhuma alteração posterior no produto mudará o histórico.

`order_status_histories` registrará todas as transições, ator e observação sanitizada.

Ao criar um pedido, o sistema reservará estoque. Falha ou expiração do pagamento liberará a reserva. Pagamento aprovado converterá a reserva em baixa. Cancelamento será idempotente e respeitará o estado do pedido.

## Pagamento simulado

O checkout aceitará dados de cartão exclusivamente fictícios. O número completo e o CVV existirão apenas na memória da requisição, serão validados e descartados antes da persistência ou logging.

`payments` armazenará:

- pedido;
- valor em centavos;
- estado;
- token aleatório fictício;
- bandeira;
- últimos quatro dígitos;
- código de autorização fictício;
- chave idempotente;
- timestamps.

Estados:

- `pending`
- `authorized`
- `failed`
- `cancelled`
- `refunded`

Números de teste documentados produzirão respostas previsíveis de aprovação ou recusa. CVV nunca será salvo, criptografado, registrado ou incluído em exceções.

## Entrega simulada

O frete será calculado internamente por faixa de CEP, peso e modalidade. Modalidades iniciais:

- econômica;
- expressa;
- retirada.

`shipments` terá pedido, modalidade, valor, prazo estimado, estado e código de rastreio fictício. Estados:

- `awaiting_processing`
- `preparing`
- `shipped`
- `out_for_delivery`
- `delivered`
- `failed`
- `returned`
- `cancelled`

`shipment_events` formará o histórico imutável. Transições inválidas serão recusadas. Apenas administradores atualizarão o fluxo; clientes terão leitura dos próprios eventos.

## Interface

### Cliente

- home e catálogo;
- detalhe de produto;
- carrinho;
- checkout em etapas;
- confirmação de pedido;
- pedidos e rastreamento;
- perfil, CPF, telefones e endereços;
- segurança da conta, 2FA e sessões.

### Administrador

- dashboard com vendas, compras abertas e estoque baixo;
- categorias, produtos e imagens;
- fornecedores e custos;
- ordens de compra e recebimentos;
- estoque e ajustes;
- pedidos, pagamentos e entregas;
- auditoria.

Todas as telas serão responsivas, acessíveis por teclado, terão estados de carregamento, erros junto aos campos e mensagens que não revelem informações internas.

## Segurança

O baseline será o OWASP Top 10:2025 e orientações aplicáveis do OWASP Cheat Sheet Series.

Controles obrigatórios:

- Argon2id para senhas;
- recuperação de senha com resposta neutra contra enumeração;
- rate limiting em login, cadastro, recuperação, checkout e administração;
- 2FA disponível e confirmação de senha para operações sensíveis;
- sessões server-side, rotação de ID após autenticação e encerramento remoto;
- cookies `HttpOnly`, `Secure` em produção e `SameSite` adequado;
- CSRF em todas as mutações web;
- Policies contra IDOR e elevação de privilégio;
- validação allow-list no servidor;
- consultas parametrizadas via Eloquent/Query Builder;
- escaping de saída e CSP para reduzir XSS;
- cabeçalhos `X-Content-Type-Options`, `Referrer-Policy`, proteção contra framing e política de permissões;
- `Cache-Control: no-store` em respostas com PII ou dados de pagamento;
- mass assignment restrito;
- criptografia de PII e blind indexes com chave separada;
- segredos somente no `.env`;
- tratamento de exceções sem detalhes sensíveis;
- auditoria de autenticação, autorização e operações críticas;
- sanitização contra log injection;
- exclusão de senha, CPF, endereço, telefone, PAN, CVV, tokens e IDs de sessão dos logs;
- `composer audit` e `npm audit` no processo de verificação.

## Auditoria

`audit_logs` armazenará ator, ação, tipo/ID do recurso, resultado, IP minimizado, user-agent reduzido e metadados allow-list. O registro será append-only para a aplicação.

Eventos mínimos:

- autenticação bem-sucedida ou falha;
- recuperação e troca de senha;
- alteração de perfil sensível;
- falha de autorização;
- CRUD administrativo;
- ajuste e recebimento de estoque;
- criação, pagamento, cancelamento e reembolso de pedido;
- mudanças de entrega.

## Testes

### Unitários

- normalização e validação de CPF;
- objetos de dinheiro;
- cálculo de frete;
- transições de estados;
- disponibilidade de estoque;
- interpretação de cartões de teste.

### Feature e integração

- autenticação e recuperação de senha;
- autorização cliente/admin e isolamento entre clientes;
- CRUDs administrativos;
- compra junto a fornecedor e recebimento parcial;
- carrinho e checkout;
- pagamento aprovado, recusado e repetido;
- cancelamento e liberação de estoque;
- entrega e rastreamento;
- migrations e fluxos no MySQL.

### Segurança

- IDOR;
- mass assignment;
- CSRF;
- enumeração de conta;
- rate limiting;
- XSS persistente;
- upload inválido;
- concorrência pela última unidade;
- duplicidade por repetição de requisição;
- ausência de PII e dados de cartão em logs.

## Dados de demonstração

Factories e seeders criarão:

- um administrador conhecido para demonstração, com senha fornecida por variável de ambiente ou exibida apenas durante o seeding local;
- clientes fictícios;
- CPFs sintéticos válidos e formatados;
- endereços e telefones fictícios;
- categorias, produtos e imagens placeholder;
- fornecedores e ordens de compra;
- pedidos em diferentes estados;
- cartões de teste documentados.

Nenhuma factory usará informação copiada de uma pessoa real.

## Incrementos de entrega

1. Fundação de segurança, papéis e infraestrutura de domínio.
2. Perfis de cliente, CPF, telefones e endereços.
3. Catálogo, imagens e administração.
4. Fornecedores, ordens de compra e estoque.
5. Carrinho, frete e checkout.
6. Pagamento simulado e ciclo do pedido.
7. Entrega e rastreamento.
8. Auditoria, hardening, seeders e documentação final.

Cada incremento terá migrations reversíveis, testes automatizados, autorização, interface mínima funcional e um commit independente. O incremento seguinte só consumirá interfaces explicitamente produzidas pelo anterior.

## Fora do escopo

- dados reais de qualquer pessoa;
- gateway de pagamento real;
- armazenamento de PAN completo ou CVV;
- integração real com transportadora, Correios ou consulta de CEP;
- marketplace, múltiplos lojistas ou comissões;
- microsserviços;
- emissão fiscal;
- cupons, promoções complexas e programa de fidelidade;
- aplicativo móvel ou API pública.

## Critérios de conclusão

- Todos os fluxos obrigatórios funcionam no monólito Laravel.
- Migrations executam do zero no MySQL.
- Testes automatizados passam.
- Frontend compila sem erro.
- Auditorias de dependência não apresentam vulnerabilidade conhecida bloqueante.
- Checklist de segurança documenta os controles e as evidências.
- O sistema inicia e responde localmente.
- O repositório não contém segredos nem dados pessoais reais.
