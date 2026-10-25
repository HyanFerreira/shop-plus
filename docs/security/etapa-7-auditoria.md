# ShopPlus — Etapa 7: auditoria e correções SDS

> Atualizações posteriores: a [Etapa7B](7b/resultado.md) corrigiu o PHP local e o parser CSS; a [Etapa7C](7c/resultado.md) comprovou concorrência MySQL em laboratório isolado e ensaio HTTP/persistência. Este documento preserva o estado da auditoria inicial. A VM do IF continua sem inspeção; não confundir o laboratório local com homologação da VM.

Data: **24/10/2026, America/Sao_Paulo**. Prazo informado: **26/10/2026 às 23h59**.

**Resultado:** correções de aplicação implementadas e verificadas; **262 testes aprovados, 7 ignorados, zero falhas** (269 testes, 1.482 assertions). **Ainda não pronto para exposição na rede da turma:** PHP ativo segue vulnerável às duas CVEs críticas; a VM só existe no computador do IF e não foi inspecionada. Concorrência MySQL não foi executada. Não há alegação de segurança absoluta.

## 1. Preservação e evidências

- HEAD permaneceu `d9b63f93332091a422b690abac22e890d90e09b7`; nenhum commit, reset, stash ou descarte.
- Alterações locais anteriores foram preservadas. Snapshot dos arquivos modificados/não rastreados e diff binário: `C:\Users\HYANFE~1\AppData\Local\Temp\shopplus-before-security-20261024-172839`. Contém `.env.testing` sintético, não o `.env` de desenvolvimento. Backup do INI alterado: `php84-php.ini` nesse diretório. Guardar fora da pasta pública.
- Baseline reproduzido: 227 testes, 218 aprovados, 7 ignorados, 2 falhas (`ExampleTest` e `SecurityHeadersTest`, tabela `categories` ausente). Ambos agora usam `RefreshDatabase` **somente no SQLite em memória**, sem enfraquecer assertions.
- O único ajuste posterior em teste existente de catálogo passou a exigir **422**, em vez de 200, para ordenação maliciosa; a assertion de preservação dos produtos continua presente.
- Solicitações hostis que abortam Livewire podem deixar sua configuração estática de views de paginação ativa. `tests/TestCase.php` restaura as views padrão ao criar cada aplicação de teste; nenhum guard de banco foi removido.
- Saída completa e JUnit: [phpunit.txt](evidence/phpunit.txt), [phpunit.xml](evidence/phpunit.xml). Inventário PHP: [php-cli.json](evidence/php-cli.json), [php-installations.json](evidence/php-installations.json).
- Gates de isolamento: [isolation.json](evidence/isolation.json), [bootstrap-isolation.json](evidence/bootstrap-isolation.json), [dotenv-isolation.json](evidence/dotenv-isolation.json). Os probes de conexão registram **zero tentativas PDO e zero preparações**; testes funcionais abrem exclusivamente SQLite `:memory:`.

Não foram executados testes, migrations, wipes, restaurações ou seeders no MySQL local, nem em `shopplus_db`. A configuração de desenvolvimento não foi reescrita. Leia também [testing-safety.md](../testing-safety.md), inclusive o histórico de preservação/recuperação do banco local; esta auditoria não realizou recuperação nem repopulação.

## 2. PHP — P0 aberto

Fonte primária consultada: [avisos PHP de 2026](https://www.php.net/archive/2026.php), publicações de 21 e 23/10. O aviso de 21/10 fixa mínimos 8.2.35, 8.3.36, 8.4.27 e 8.5.12 para **CVE-2026-109618 e CVE-2026-109619**. O aviso de 23/10 trata **CVE-2026-115560**, ainda sem correção definitiva anunciada naquele aviso.

| Instalação local Herd | Versão executada | Uso observado | Duas primeiras CVEs |
|---|---|---|---|
| php84 | 8.4.22 | CLI do projeto e processo HTTP `php -S 127.0.0.1:8000` | Vulnerável por versão |
| php82 | 8.2.31 | Instalada; não observada no servidor do projeto | Abaixo do mínimo |
| php83 | 8.3.31 | Instalada; não observada no servidor do projeto | Abaixo do mínimo |
| php85 | 8.5.7 | Instalada; não observada no servidor do projeto | Abaixo do mínimo |
| VM do IF | Não obtida | Sem acesso nesta sessão | Não verificado |

CLI: `C:\Users\Hyan Ferreira\.config\herd\bin\php84\php.exe`. INI efetivo: `...\php84\php.ini`, sem outros INIs escaneados. Extensões completas nas evidências; presentes PDO MySQL/SQLite, mbstring, OpenSSL, sodium, fileinfo, GD e Argon2id funcional nos testes. FFI também está carregada: desabilitar na VM se desnecessária, após validar dependências.

Foi acrescentado **`session.upload_progress.enabled=0`** ao INI php84, com cópia anterior. Um novo CLI confirmou **Off** e `session.serialize_handler=php`. A revisão de `app/`, `routes/` e views não encontrou troca de serializer em runtime. A condição específica descrita para CVE-2026-115560 não foi identificada no código da aplicação; a mitigação adicional foi aplicada mesmo assim. Isso **não corrige CVE-2026-109618**, nem substitui a atualização.

O processo HTTP observado usa o mesmo executável, mas já estava aberto: **o INI carregado por esse worker após a alteração não foi atestado**. Não foi publicado `phpinfo()` ou endpoint diagnóstico. Sessões da aplicação usam Laravel/database, separadas dos defaults de sessão nativa do PHP; strict mode nativo 0 não equivale à configuração dos cookies Laravel.

### Atualização local pendente, com reversão

1. Encerrar deliberadamente o `artisan serve` do projeto antes da troca. Conservar o backup do INI e uma cópia privada do diretório php84 para reversão emergencial; não copiar executáveis de fontes aleatórias.
2. Abrir o Herd Desktop e usar **PHP → atualizar 8.4**, ou `herd php:update 8.4`, conforme [CLI oficial Herd](https://herd.laravel.com/docs/windows/advanced-usage/command-line). Nesta sessão o comando foi tentado e recusou: Desktop não está em execução / namespace `php` indisponível. Não foi iniciada atualização invasiva por outro método.
3. Confirmar PHP estável **8.4.27 ou posterior corrigido**, compatível com extensões Herd. Não voltar a uma versão vulnerável para servir à turma. Atualizar também instalações alternativas antes de utilizá-las.
4. Preservar `session.upload_progress.enabled=0` no INI novo. Reiniciar o servidor explicitamente com o PHP corrigido.
5. Rodar `php -v`, `php --ini`, `php -m`, `php scripts/security/php-runtime.php`, `composer check-platform-reqs`, suíte e build. O script retorna **exit 1 agora**, corretamente, porque 8.4.22 permanece abaixo do mínimo.
6. Reconfirmar o executável do processo HTTP reiniciado e a configuração efetiva de seu SAPI. Em FPM, conferir a versão do binário e `php-fpm8.4 -i`, pool e overrides antes de reiniciar/recarregar. CLI sozinho não prova HTTP.

Rollback: restaurar o INI a partir do backup e reverter a seleção no gerenciador apenas em ambiente isolado para diagnóstico; reinstalar/selecionar uma versão corrigida antes de liberar tráfego. Não houve troca de binários nesta sessão.

## 3. Achados confirmados e correções

Gravidade considera o efeito observado no cenário acadêmico, não uma nota CVSS inventada.

| Gravidade / prioridade | Achado | Arquivos e tratamento |
|---|---|---|
| Crítica / P0 aberto | Runtime PHP abaixo das correções oficiais | INI mitigado; atualização e reinício HTTP pendentes, conforme seção anterior |
| Média / P0 corrigido | Arrays no e-mail causavam 500 antes de validação, inclusive no limiter do Fortify | `ThrottleSensitiveAuthRequests.php`, prioridade em `bootstrap/app.php`; validação antes de normalizar/hash/limiter |
| Média / P0 corrigido | Entrada sem limite de senha/CPF/telefone e filtros hostis | `PasswordValidationRules.php`, `CreateAdministrator.php`, `PersonalData/Manager.php`, `Catalog/Browser.php`, `ValidateCatalogQuery.php` |
| Média / P0 corrigido | Arrays em propriedades escalares e parâmetros Livewire geravam TypeError/500 | `Support/LivewireInputGuard.php`, registro em `AppServiceProvider.php`; rejeição 422 antes de atribuição/chamada |
| Média / P0 corrigido | Adições repetidas passavam do limite de 99 no carrinho | `Actions/Cart/AddCartItem.php` agora verifica quantidade acumulada |
| Média / P0 corrigido | Pagamento aceito após prazo, antes do scheduler | `Actions/Payment/ProcessPayment.php` verifica expiração sob lock do pedido |
| Média / P0 corrigido | Checkout key de outro usuário causava erro de constraint | `Actions/Checkout/PlaceOrder.php` rejeita a chave com erro de validação; resultado anterior lido sob lock após serializar o carrinho |
| Média / P0 corrigido | Conta desativada conservava acesso a `/api/user` com token antigo | `routes/api.php`, `EnsureUserIsActive.php`: 403 em autenticação sem sessão; resposta ativa com no-store |
| Baixa / P1 corrigido | Headers só cobriam grupo web | `bootstrap/app.php`: headers globais, incluindo API e `/up` |
| Média / P1 corrigido | Métodos de paginação sem tipos aceitavam arrays / valores absurdos | Guard valida `page`, estrutura de paginators, nome permitido e faixa 1–10.000 |
| Regressão evitada | Guard inicialmente barrava campo numérico apagado antes do erro inline | Estado vazio transitório permitido para propriedades int; `required` continua produzindo erro do formulário |
| Média / remanescente | Log local antigo contém duas linhas com padrão de CPF | `storage/logs/laravel.log`, linhas 162/163 no momento da leitura; valores omitidos. Não foi apagado nem copiado ao relatório |

Leituras de idempotência de checkout/pagamento passaram a ocorrer com locks antes de decidir repetição/status. Os testes demonstram repetição **sequencial** e rollback. A melhoria na ordem de leitura não é apresentada como prova de concorrência MySQL.

## 4. Autenticação, autorização e superfícies privadas

| Superfície | Proteção verificada |
|---|---|
| Home, catálogo, detalhe | Públicos; catálogo limita filtros; estoque/preço válidos são conferidos novamente nas actions |
| Dashboard, `/meus-dados`, `/carrinho`, `/checkout`, `/pedidos`, detalhe do pedido | Grupo autenticado Sanctum/sessão Jetstream; dados consultados pelo usuário autenticado |
| `Account/Overview`, navegação e dados pessoais | Contagens/pedidos por `Auth::id()`; CPF/telefone/endereço por relações do proprietário; admin condicionado no servidor |
| Perfil Jetstream, senha, 2FA, sessões e exclusão | Rotas/componentes Jetstream autenticados; confirmação de senha e senha atual conforme operação; actions Fortify e senhas com hash |
| `/admin`, catálogo, abastecimento, pedidos, auditoria e usuários | Middleware admin no backend; actions administrativas checam admin; Catalog/Supply checam no boot Livewire; demais operações também checam nas actions/render |
| Pedidos/pagamentos/cancelamento | Busca pelo proprietário; pedido alheio por URL dá 404; `PaymentForm.orderId/paymentKey` Locked; ID reconsultado pelo proprietário |
| `/api/user` | Sanctum + usuário ativo + no-store; sem senha, remember token ou segredos 2FA serializados |

Não há policies customizadas; a autorização está nos middleware, escopo das consultas e actions. Rotas administrativas não dependem de esconder botões. Teste de replay de snapshot admin sob Cliente B rejeita exclusão antes de persistir. Alterar role/status no cadastro não muda os defaults protegidos; `User::$fillable` só permite nome, e-mail e senha.

Login usa Argon2id, senha errada/conta bloqueada têm falha genérica, sessão muda após login e logout invalida autenticação/sessão. Recuperação tem resposta neutra para conta inexistente. Email verification está **desativada** no Fortify e `User` não implementa MustVerifyEmail: a presença de `verified` nas rotas não comprova verificação de e-mail. Isso foi mantido para o fluxo acadêmico existente, sem alegar proteção inexistente.

Limites: login 5/min por identificador/IP; operações sensíveis têm limite adicional por IP (30/min por operação). Cadastro 10/min e recuperação/reset 5/min por identificador/IP; 2FA e passkeys têm limiters configurados. Checkout e pagamento já limitavam tentativas; admin tem throttle. Testes exercitam rejeição de formato e fluxos; não foi feito teste de carga/distribuído.

## 5. Entradas, SQLi, XSS e CSRF

- Senha: string, confirmação, mínimo Fortify (8) e máximo **128 caracteres**; administrador mínimo 12 com letras de ambas as caixas, números e símbolo. Nunca truncada. Nome/e-mail limitados a 255; administrador e perfil preservam regras existentes. Uma conta legada com senha maior que 128 precisará redefini-la.
- CPF: string até 14 + dígitos verificadores; telefone: string até 20 + formato brasileiro. Endereço: rótulo50, destinatário120, rua150, número20, complemento100, bairro/cidade100, CEP8/9 formatado, UF em allowlist; data entre 1900 e hoje.
- Produto/categoria: validação Livewire existente de nome160/120, SKU64 e formato, descrição10.000/2.000, preço decimal de até10 dígitos inteiros +2 decimais, dimensões/peso positivos limitados, enum de status e IDs existentes. Uploads de produto: imagem JPEG/PNG/WebP, máximo2MiB; perfil JPEG/PNG máximo1MiB. Arquivos ficam em paths gerados, não em nomes executáveis enviados pelo usuário.
- Carrinho: inteiros1–99, zero remove pelo proprietário; limite acumulado e disponibilidade. Checkout usa ID de endereço próprio, método em allowlist e UUID; valores financeiros vêm do servidor. Pagamento usa somente cartões fictícios conhecidos, CVV3/4, mês1–12 e ano limitado; nenhum preço recebido do cliente é aceito.
- Catálogo: busca200, filtros individuais120, listas até50 strings; preços0–9.999.999.999, rating0–5, ordenação em allowlist e paginação limitada. Forma dos parâmetros URL é validada antes das propriedades tipadas. O guard também rejeita parâmetros adicionais em actions e IDs de tipo incompatível. Dados arbitrários não entram em `fill($request->all())`.
- Casos executados: strings>1.000, arrays, campos vazios, Unicode, negativos/quantidades indisponíveis, IDs alheios/inexistentes, enum/ordenação inválidos, parâmetros extras e chave reutilizada. Há cobertura específica existente de formulários administrativos, upload e preços; não se afirma fuzzing exaustivo de todas as combinações.

**SQL Injection:** consultas Eloquent/Query Builder parametrizadas. Os raw encontrados são expressões constantes (`on_hand - reserved`, COUNT e agrupamentos); não recebem texto de usuário. Ordenação usa match/allowlist. Teste com busca `' OR 1=1 --` não retorna catálogo inteiro e preserva tabelas. Ordenação com `drop table` é rejeitada com422.

**XSS:** Blade mantém escape para nome, descrição, busca, labels, pedidos e conta. Não foram encontrados `x-html`/`innerHTML` em código da aplicação. Os usos `{!! !!}` examinados correspondem a atributos Blade, links fixos de termos, Markdown local de termos/política e SVG de QR gerado pelo Jetstream; não foi identificado HTML de produto/cliente passando cru nesses pontos. Testes de payload armazenado/refletido cobrem catálogo, dashboard, dados pessoais, pedidos e perfil. CSP existente permanece com `unsafe-inline` e `unsafe-eval` para compatibilidade Livewire/Alpine; é defesa parcial, não substituto do escape.

**CSRF:** middleware permanece habilitado. Tests comuns do Laravel ignoram CSRF por padrão; os novos testes retiram **somente esse bypass de teste** temporariamente, mantendo banco isolado. POSTs sem token ou com token errado em register/login/reset/logout/Livewire retornam419; token correto chega à validação422. Checkout, pagamento, exclusões e alterações são actions do mesmo endpoint Livewire protegido. Snapshot checksum/Locked não substituem CSRF ou autorização.

## 6. Comércio, estoque e concorrência

Comprovado em SQLite isolado:

- Reserva compara estoque disponível; dois clientes tentando sequencialmente reservar a última unidade não criam dois pedidos.
- Totais calculados com centavos e preço/frete do servidor, snapshot histórico de produto/endereço, chave de checkout idempotente.
- Aprovação converte reserva em baixa; recusa libera reserva; pagamento duplicado com a mesma chave não duplica lançamento.
- Cancelamento/expiração/reembolso repetidos não multiplicam devolução de estoque, conforme testes existentes.
- Pedido alheio não pode ser pago/cancelado; endereço alheio não pode ser usado no checkout.
- Falha artificial **após** pagamento/estoque/histórico, ao criar shipment, desfaz pagamento, baixa e histórico; a reserva anterior permanece íntegra.
- Prazo expirado rejeita pagamento mesmo antes do scheduler. É necessário executar `orders:expire`/scheduler para liberar a reserva expirada.
- Transições de entrega válidas e inválidas e ação admin/cliente estão cobertas.

`DB::transaction`, `lockForUpdate`, retries e índices únicos foram revisados. **Não houve transações simultâneas reais, teste de deadlock, isolamento InnoDB ou prova de ausência de overselling concorrente.** O guard impede testes no MySQL local; ele não foi relaxado. A matriz para homologação MySQL está no checklist da VM.

## 7. Dados sensíveis e Workbench

- Senhas Argon2id; testes comparam hash e verificação, sem senha em texto puro.
- CPF e telefones têm casts `encrypted` e índices cegos HMAC separados; campos ocultos na serialização. `PII_BLIND_INDEX_KEY` deve ser segredo independente de `APP_KEY` na VM.
- Destinatário e detalhes de endereço, snapshot de entrega do pedido e metadados de auditoria são criptografados. CPF/telefone/endereço não são enviados inteiros nas listas mascaradas; edição pelo dono contém os dados necessários à edição.
- Nome/e-mail de login, data de nascimento e rótulos de endereço permanecem texto no schema existente: não houve migração de todos os dados pessoais para cifra. Não colocar CPF/endereço em rótulos livres. Chaves, backups e banco precisam de controles de acesso.
- Pagamento persiste apenas bandeira, últimos4, valor, status e identificadores sintéticos. **Sem coluna CVV ou PAN completo.** Campos sensíveis do componente são limpos após tentativa. Os números completos mostrados como instrução são cartões de teste públicos, não cartões reais.
- Sessão Laravel local permanece `SESSION_ENCRYPT=false` no `.env`; cookies são geridos pelo framework, mas isso não cifra payload do banco de sessão. O template VM usa `SESSION_ENCRYPT=true`, HttpOnly, SameSite=Lax e Secure com HTTPS. Não foi fingida aplicação desse template.
- Scan restrito do log antigo encontrou **2 linhas com padrão de CPF**, sem PAN de teste; [evidência sem os valores](evidence/log-pattern-findings.json). O scan não prova ausência de outros segredos. Log histórico foi preservado; restringir acesso e arquivar/sanear uma cópia antes de levar ao IF, sem publicar o arquivo. Não servir `MAIL_MAILER=log` para demonstração de recuperação: contém links/tokens. Configurar SMTP privado.

Consultas **somente leitura**, executadas pelo professor na instância de demonstração já validada (não rodadas nesta sessão):

```sql
SELECT id, role, status, LEFT(password, 10) AS algoritmo FROM users;
SELECT id, user_id, LEFT(cpf_encrypted, 24) AS cifra, LENGTH(cpf_hash) AS indice_hmac FROM customer_profiles;
SELECT id, user_id, LEFT(number_encrypted, 24) AS cifra, LENGTH(number_hash) AS indice_hmac FROM phones;
SELECT id, user_id, LEFT(street_encrypted, 24) AS cifra FROM addresses;
SELECT id, user_id, public_number, status, subtotal_cents, shipping_cents, total_cents FROM orders;
SELECT order_id, product_name, quantity, unit_price_cents, total_cents FROM order_items;
SELECT order_id, amount_cents, status, brand, last_four FROM payments;
SHOW COLUMNS FROM payments;
SELECT product_id, on_hand, reserved FROM inventory_items;
SELECT order_id, status FROM shipments;
SELECT shipment_id, from_status, to_status FROM shipment_events;
```

Não consultar APP_KEY, blind-index key, tokens, CVVs, payloads de sessões ou recovery codes. Selecionar apenas prefixo dos hashes/cifras evita exportação desnecessária. Usar conta Workbench de leitura e restringir acesso remoto ao IP do professor, ou túnel SSH.

## 8. Dependências

| Auditoria | Antes | Depois |
|---|---|---|
| Composer | 0 avisos, 0 abandonadas | 0 avisos, 0 abandonadas |
| NPM | 9 entradas afetadas:2 críticas,3 altas,4 moderadas | 4 moderadas;0 altas/críticas |

Executados `composer audit --format=json`, `npm.cmd audit --json` e **`npm.cmd audit fix` sem force**. Lockfile atualizado de forma compatível. Entre versões finais: Vite6.4.5, concurrently9.2.5, shell-quote1.12.0, follow-redirects1.16.1, source-map-js1.2.2. `npm.cmd run build` passou, sem alteração de layout/código CSS.

Restam `postcss-selector-parser` e as cadeias de `postcss-nested`, Tailwind3 e Typography, relacionadas a [GHSA-rj75-hqrm-r3gf](https://github.com/advisories/GHSA-rj75-hqrm-r3gf). São dependências de desenvolvimento/build; a exploração descrita envolve parsing de CSS malicioso. O catálogo não compila CSS enviado por clientes. Isso reduz a exposição no runtime, mas **não elimina o aviso**. O fix automático propõe migração major/downgrade incompatível; não aplicado. Não expor Vite/dev server na rede da turma e construir só código confiável. [Auditoria final](evidence/npm-audit.json), [Composer](evidence/composer-audit.json).

## 9. Checklist do computador/VM do IF — pendente

O usuário confirmou que **não há VM acessível aqui**. Nenhum item abaixo foi marcado como executado na VM.

1. Identificar SO, servidor/SAPI, `command -v php`, `php -v`, `php --ini`, `php -m`, executável FPM/Apache e pools. Atualizar pelo repositório oficial da distribuição/fornecedor para release corrigida ou pacote com backport oficialmente documentado. Não instalar binários aleatórios ou PPA não revisado.
2. Desativar upload progress no INI efetivo do SAPI HTTP; preservar backup e reiniciar/recarregar o serviço escolhido. Verificar versão **efetivamente em execução**, INIs adicionais e overrides. Reconsultar aviso oficial na data da apresentação.
3. Criar configuração a partir de `.env.vm.example`, em arquivo privado. Gerar APP_KEY e blind-index key aleatórias e independentes, sem colá-las em logs/chat. Se já houver dados cifrados, preservar as chaves existentes; não executar key:generate sobre dados que precisam ser lidos. Não usar a chave sintética de testes.
4. `APP_DEBUG=false`, APP_ENV=production, APP_URL correto. Build estático; `composer install --no-dev --optimize-autoloader` e `npm ci`/build em ambiente de construção confiável; sem Vite de desenvolvimento acessível. Para testar antes do deploy sem-dev, usar dependências de dev apenas no checkout de testes isolado.
5. Raiz HTTP estritamente `public/`; nenhum acesso à raiz do GitHub/`.env`/vendor/storage privado. Template Linux/Nginx em `nginx-vm.conf.example`; adaptar socket FPM e hostname, executar `nginx -t` antes de reload. Se for Apache, usar VirtualHost com DocumentRoot public e confirmar regras `.htaccess`.
6. Habilitar HTTPS com certificado confiável da instituição/CA local e `SESSION_SECURE_COOKIE=true`. Em rede HTTP de laboratório isolada, Secure deve ser false para o login funcionar, mas não há confidencialidade de transporte: não apresentar isso como HTTPS protegido. Não configurar proxy/host confiável como `*`; cadastrar somente os IPs/hostnames reais após conhecer a topologia.
7. Sessões cifradas em repouso, cookies HttpOnly/Lax, tempo de sessão apropriado. Aplicação inicia e encerra sessão corretamente. Se mudar a chave APP_KEY ou modo de cifra de sessão, planejar encerramento das sessões antigas.
8. MySQL bind em loopback/rede privada, firewall fechado à turma; sem usuário root na aplicação. Conta runtime com SELECT/INSERT/UPDATE/DELETE apenas no schema da aplicação; conta separada para migrations/DDL. Workbench com leitura via túnel ou regra limitada. Não reutilizar `shopplus_db` local em homologação destrutiva.
9. Só storage e bootstrap/cache graváveis pelo processo web; `.env`, Git, backups, logs e chaves inacessíveis ao HTTP e outros usuários da máquina. Não copiar log local antigo para public/. Configurar SMTP privado e retenção/restrição de logs; sem e-mail de recuperação em log compartilhado.
10. Confirmar headers nas respostas reais HTTP, erros/redirects e estáticos: nosniff, frame deny, referrer, CSP e HSTS apenas com HTTPS correto. Fazer GETs de `/.env`, `/.git/config`, `/composer.json`, `/artisan`, `/storage/logs/laravel.log`: exigir403/404 sem conteúdo privado. Inspecionar aliases, backups e diretórios do servidor, não só esses cinco paths.
11. Garantir que o scheduler execute expiração (`php artisan schedule:run`, cron do usuário correto). Não executar seeders/migrations de limpeza em banco que precise ser preservado. Criar usuários fictícios/admin apenas na instância de demonstração aprovada, sem credenciais em páginas públicas.
12. Em instância **descartável, separada e identificada**, homologar MySQL real: dois clientes simultâneos na última unidade; dois checkouts com mesma chave; dois pagamentos simultâneos com mesma/chaves diferentes; pagamento contra cancelamento/expiração; reembolso contra transição de entrega; dupla recepção/ajuste de estoque. Registrar commits/resultados e verificar `0 <= reserved <= on_hand`, um único efeito financeiro, constraints e histórico consistente. Repetir com timeout/deadlock e confirmar resposta controlada. Não adaptar a suíte protegida para apontar ao MySQL local; isso exige executor/política de isolamento próprios antes de qualquer teste.

Verificação HTTP **local** de leitura após a correção: `/up`200 com nosniff; `.env`, `.git/config`, composer e artisan404; log403, sem copiar seus corpos. [Resultados](evidence/local-http.json). Testes automatizados também comprovam headers em `/up` e API. Esses resultados não atestam a VM nem o INI já carregado pelo worker HTTP.

## 10. Roteiro da apresentação

1. Antes de conectar a turma, resolver PHP P0, conferir VM/checklist e registrar versões HTTP/CLI. Não executar testes destrutivos no banco de demonstração.
2. Abrir home como visitante; tentar `/dashboard` e `/admin` e mostrar exigência de login.
3. Cadastrar cliente fictício, sair e fazer login. Mostrar perfil, CPF válido fictício e telefone/endereço; listas mascaradas e edição apenas do proprietário.
4. Abrir catálogo/detalhe, adicionar2 unidades, conferir carrinho, endereço e frete, finalizar. Mostrar total calculado no servidor e status pendente.
5. Efetuar pagamento fictício com cartão público `4111 1111 1111 1111`, CVV de teste3 dígitos e validade futura. Não usar cartão real. Mostrar pedido pago, reserva convertida em baixa e histórico.
6. Em sessão administrativa separada, avançar entrega pelas transições permitidas. Voltar ao cliente e acompanhar o rastreamento até entregue.
7. Com Cliente B, tentar URL do pedido e edição de endereço de A; mostrar404/erro sem alteração. Como cliente, `/admin` retorna403. Não tentar ataques de RCE contra o PHP vulnerável.
8. No Workbench, usar as consultas de leitura acima para mostrar relacionamento usuário/pedido/itens/pagamento/estoque/entrega e cifra/hash sem revelar segredos. A compra do cliente gera o registro de venda e baixa; abastecimento e administração completam a demonstração de estoque/atendimento existente.
9. Mostrar evidências da suíte e explicar limites: SQLite não prova concorrência MySQL; CVEs do runtime exigem atualização; quatro avisos CSS permanecem; não foi feita auditoria visual nem pentest exaustivo.

O teste `AcademicFlowTest` executa esse percurso funcional até entrega e inspeção de registros em **SQLite**, inclusive tentativa de injetar role/status no cadastro. Isso não equivale a abrir o Workbench ou navegar na VM real.

## 11. Comandos para reproduzir verificações locais

```powershell
php vendor/phpunit/phpunit/phpunit --log-junit docs/security/evidence/phpunit.xml
php tests/Safety/verify-isolation.php
php tests/Safety/verify-bootstrap.php
php tests/Safety/verify-dotenv-cache.php
composer audit --format=json
npm.cmd audit --json
npm.cmd run build
php scripts/security/php-runtime.php
```

PHPUnit usa `tests/bootstrap.php`, `.env.testing` e SQLite `:memory:`. Conflitos de ambiente/cache são bloqueados, não contornados. O script PHP deve continuar falhando enquanto o runtime estiver abaixo do mínimo. NPM audit retorna exit1 enquanto houver os quatro avisos; esse resultado não foi escondido.

## 12. Pendências e limites da entrega

- **Bloqueadores para liberação em rede:** PHP corrigido e SAPI reiniciado/verificado; inspeção/configuração real do IF; homologação concorrente MySQL isolada.
- Configuração local continua de desenvolvimento: debug=true, usuário MySQL root, sessão sem cifra em repouso e mail log. Não reutilizar esse `.env` na avaliação em rede.
- Duas linhas históricas com padrão de CPF no log precisam de tratamento privado/retention antes da apresentação; nenhum segredo foi incluído nas evidências publicáveis.
- Quatro avisos moderados CSS e CSP permissiva para scripts permanecem. Nenhum upgrade major, redesign ou alteração visual foi realizado.
- Sete testes continuam ignorados: três de gerenciamento opcional de tokens, três de verificação de e-mail desabilitada e um cenário inverso de cadastro desabilitado (o cadastro está habilitado). Nenhuma nova regressão foi excluída; nomes estão no JUnit.
- Revisão independente do código encontrou dois casos adicionais no guard; ambos tiveram reprodução RED e teste GREEN. Limites que o revisor não pôde julgar (MySQL, VM/HTTP, visual, mudanças anteriores) permanecem explicitamente descritos aqui.

As correções locais têm evidência de testes. A etapa de infraestrutura da VM permanece pendente e o projeto **não deve ser anunciado como totalmente seguro ou já pronto para ataques na rede da turma**.
