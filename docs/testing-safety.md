# ShopPlus — Segurança dos testes e contenção do incidente

Registro de24/10/2026, horário de Brasília. Escopo: preservação e isolamento, sem recuperação. Nenhum replay, restauração, repopulação ou migration em banco persistente foi executado nesta contenção. .env e as alterações5B/6A foram preservados; nenhum commit ou dependência nova.

## Evidências e custódia

Diretório protegido fora do Git:

`C:/Users/Hyan Ferreira/.shopplus-recovery/2026-10-24-containment-02593ae2567e41a88d15517251f46af7`

ACL da raiz sem herança: somente usuário atual e SYSTEM com acesso. Não publicar o conteúdo, não anexar dumps/binlogs a issues e não enviar a serviços externos. O diretório contém dados recuperáveis e deve ser tratado como confidencial. ACL não equivale a criptografia de disco nem impede acesso por administrador da máquina.

- binlogs/:39 arquivos .000216–.000254, extraídos via mysqlbinlog8.4 em formato raw pelo protocolo do servidor. Foram preservados todos os disponíveis, não somente os dois candidatos iniciais.
- verification-copy/: segunda extração independente, com hashes iguais aos da primeira em todos os39 arquivos.
- metadata/binlog-manifest.json: origem, bytes informados pelo servidor, bytes copiados, SHA-256, hash da segunda extração e horário UTC de captura/mtime local de cada cópia. Datas originais do filesystem não puderam ser lidas; isso está explícito no manifesto.
- metadata/checksum-verification.json: leitura offline com --verify-binlog-checksum;39 aprovados. Tamanhos das39 cópias coincidem com os tamanhos informados pelo servidor.
- metadata/preservation.json e source-before.json: versão, inventário, estado e parâmetros relevantes do servidor, sem credenciais.
- metadata/server-index-reconstructed.txt: sequência obtida por SHOW BINARY LOGS. **É reconstrução lógica, não cópia byte a byte do índice físico.** Acesso ao datadir foi negado e LOAD_FILE retornou NULL. Para preservar também o índice físico, um administrador deverá copiá-lo para esta mesma custódia restrita e registrar origem/data/hash; sem alterar o MySQL.
- evidence/current-state.sql: dump lógico transacional do estado atual,42962 bytes; --single-transaction, --skip-lock-tables, --no-tablespaces e --set-gtid-purged=OFF. Sem FLUSH, rotação, parada, replay ou lock global solicitado. Todas as tabelas eram InnoDB. Não houve bloqueio contra DDL concorrente; este dump não é um backup anterior ao incidente nem um ponto de recuperação coordenado com binlog.
- evidence/: saída original do incidente, baseline JUnit/log, configuração PHPUnit/TestCase anteriores e hashes da árvore local.
- metadata/recovery-analysis.json e transaction-scan.json: análise offline de eventos/limites, contagens agregadas e transações, sem valores pessoais.
- validation/: verificações de isolamento, suite e comparação posterior do MySQL. Logs de testes ficam protegidos, pois erros futuros podem conter fixtures.

O binlog ativo .000254 permaneceu na posição107960 durante as duas extrações. Foi usado backup estático raw não bloqueante suportado pelo MySQL8.4; nenhuma opção de parada por posição/data foi usada. Todas as cópias terminaram em eventos completos; a varredura de grupos não encontrou transações/grupos abertos ao fim ou sequência de grupos interrompida. Isso não substitui uma restauração de validação.

| Arquivo prioritário | Bytes | SHA-256 da cópia |
| --- | ---: | --- |
| .000253 | 368148 | 9291439d9ce76e2a65a231e111d9c0d51747a1d088ea0b7031dd650edf0c56b9 |
| .000254 | 107960 | cd6b82681c4f44d4bef1dc012475f2251ba88ee0085b8a8cef1feba136f9ba16 |
| current-state.sql | 42962 | 3ce49e51ba159f94e20a2c3449863bae9d49ce7afd5bd4f7aa21a94bd0e74963 |

Os hashes são das extrações; não houve acesso para calcular hash diretamente nos arquivos físicos originais. A conferência combina duas extrações idênticas, tamanhos do servidor e checksums internos.

**APP_KEY e chave de índice cego:** não foram exportadas nem incluídas em relatório/manifesto. Preserve a configuração original em cofre criptografado ou mídia protegida separada das cópias; não regenere as chaves. .env não foi alterado. A chave sintética versionada em .env.testing é somente para fixtures descartáveis e não serve para descriptografar dados recuperados.

## Causa comprovada

Em24/10 às11:59:03, PHPUnit foi executado diretamente sem variáveis explícitas de isolamento. O XML tinha SQLite comentado, .env.testing não existia e TestCase não tinha bloqueio. A configuração MySQL local foi usada por RefreshDatabase, que recriou as tabelas antes de iniciar transações de testes. O DROP foi registrado às11:59:04; rollback dos testes não desfaz esse DDL.

Contagens derivadas do log imediatamente antes do DROP:3 usuários,6 categorias,16 produtos,16 inventários,2 pedidos,2 itens de pedido,1 pagamento,1 entrega,2 endereços e2 perfis. Após o incidente, essas tabelas estão vazias;32 tabelas e20 migrations do lote1 existem. A comparação deriva eventos registrados, não uma recuperação já conferida registro a registro.

## Proteção implementada

1. phpunit.xml usa tests/bootstrap.php, APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory: e DB_URL vazio. Variáveis de conexão não usam force: conflito herdado é rejeitado, em vez de ocultado. Chaves sintéticas/URL local/log sem saída usam force quando apropriado.
2. .env.testing é dedicado e versionado, sem credenciais do MySQL. TestCase seleciona explicitamente esse arquivo; não há fallback para .env local.
3. TestEnvironment.preflight exige os valores corretos em getenv/$_ENV/$_SERVER e rejeita URLs e caches existentes, inclusive personalizados/relativos. Nenhum cache é executado ou apagado pelo bloqueio.
4. TestCase.createApplication valida antes do bootstrap do kernel. protect registra verificação antes de carregar ambiente e **antes de LoadConfiguration**, após dotenv poder introduzir outro caminho de cache. A configuração resolvida é validada após LoadConfiguration, antes de RegisterProviders/BootProviders e de RefreshDatabase. Não se depende de verificação após parent::setUp.
5. Somente a conexão em memória permanece registrada inicialmente. MemoryDatabaseManager, MemoryConnectionFactory e MemoryConnector validam antes de criar conexão/PDO e nas consultas futuras. Alias persistente, default alterado, URL, build/connectUsing, reconexão, read/write overrides e opções PDO são rejeitados. Resolvers por DB::extend e Connection::resolverFor também são bloqueados no caminho protegido.
6. Consulta SQLite ATTACH, VACUUM e load_extension é rejeitada antes da execução. Banco deve ser exatamente :memory:, sem URI alternativa, arquivo ou opções/pragmas customizados.
7. Cache/sessão/mail são array; fila sync; broadcast null; log null; Redis e transportes externos são removidos da configuração de testes. Filesystem usa raízes temporárias; HTTP Laravel bloqueia solicitações não simuladas. Chaves de fixtures são sintéticas.
8. artisan detecta test e prefixos t/te/tes antes de bootar o processo pai, considerando --env separado/equivalentes. Somente nesse caminho carrega o ambiente dedicado e instala a proteção. Outros comandos e o bootstrap web local/produção não recebem esses hooks.

São permitidas migrations e seeders existentes **somente dentro do SQLite descartável já validado**. Nenhuma permissão geral de operação destrutiva foi adicionada a bancos persistentes. Não há alteração nos fluxos de checkout/pagamento/estoque ou regras comerciais.

Arquivos desta correção:

| Arquivo/grupo | Alteração |
| --- | --- |
| artisan | Proteção do processo pai exclusivamente para o comando test e seus prefixos |
| phpunit.xml e .env.testing | Configuração explícita e sintética do ambiente descartável |
| tests/bootstrap.php e tests/TestCase.php | Preflight e integração anterior ao lifecycle/providers |
| tests/Support/TestEnvironment.php | Validação de ambiente/cache/configuração e isolamento dos serviços |
| tests/Support/MemoryDatabaseManager.php | Validação de seleção, aliases, build, reconexão e default |
| tests/Support/MemoryConnectionFactory.php e MemoryConnector.php | Bloqueio antes dos resolvers e do PDO; SQL de arquivo proibido |
| tests/Support/UnsafeTestEnvironment.php | Exceção sem incluir valores de configuração/segredos |
| tests/Safety/verify-isolation.php, verify-bootstrap.php e verify-dotenv-cache.php | Gates negativos sintéticos |
| tests/Safety/fixtures/IsolationProbeTest.php | Tripwires de preparação/resolver, fora da suíte normal |
| tests/Unit/Safety/DatabaseIsolationTest.php | Três regressões do bloqueio |
| tests/Feature/Testing/MemoryEnvironmentTest.php | Um positivo sem RefreshDatabase, com PDO somente em memória |
| docs/testing-safety.md e docs/superpowers/plans/2026-10-24-test-containment.md | Relatório, operação segura, plano e evidências de validação |

## Execução e verificação

Primeiro, os gates standalone (não usam RefreshDatabase):

```powershell
php tests/Safety/verify-isolation.php
php tests/Safety/verify-bootstrap.php
php tests/Safety/verify-dotenv-cache.php
```

Depois, execução normal:

```powershell
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit tests/Feature/Testing/MemoryEnvironmentTest.php
php artisan test tests/Feature/Testing/MemoryEnvironmentTest.php --compact
```

Remova conflitos de ambiente na sessão de teste ou use um executor limpo; não altere .env de desenvolvimento. Não contorne o bloqueio para reutilizar MySQL. Cache existente deve ser inspecionado e tratado deliberadamente antes de rodar; o guard não o remove.

Os gates usam configurações fictícias e conectores/resolvers com contadores e exceções, sem abrir PDO real. O bootstrap usa fixtures sem RefreshDatabase ou migrations e marcadores temporários para detectar preparação e execução de cache. Os contadores de resolver **não interceptam arbitrariamente todo new PDO nativo de PHP**; são tripwires dos caminhos Laravel exercitados. O cache venenoso é código sintético que só escreveria um marcador temporário; a versão corrigida impede sua execução.

Revisão independente encontrou e reproduziu em RED o cache introduzido por dotenv, resolver estático e detecção incompleta de argumentos do Artisan. Os testes foram fortalecidos para distinguir bloqueio no processo pai do bloqueio apenas no filho. Correções passaram em GREEN.

Resultados finais:

- Harness de conexão:24 cenários rejeitados e validação segura aceita sem PDO, com zero tentativas do conector/resolver e zero preparação.
- Entrypoints:9 cenários negativos, incluindo PHPUnit sem bootstrap do projeto e Artisan com --env separado/abreviação; nenhum marcador de preparação/PDO acionado. Cache introduzido pelo dotenv também rejeitado antes de executar seu PHP.
- Teste positivo: aplicação realmente conectada a SQLite :memory:, driver PDO sqlite, PRAGMA database_list sem caminho, criação/inserção somente em memória; serviços auxiliares conferidos.
- Suíte final:222 testes,1164 assertions;212 aprovados,7 ignorados e3 falhas iguais ao baseline6A. Quatro testes novos aprovados. Sem falhas novas.
- Falhas preservadas: AdminAccessTest::test_administrator_navigation_shows_the_administration_link; ExampleTest::test_the_application_returns_a_successful_response; SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers. As duas últimas encontram categories ausente em seu contexto de teste; não foram corrigidas nesta contenção.
- Pint e git diff --check aprovados. PHPUnit direto e Artisan individual normal/abreviado aprovados. Comando normal artisan --version preservado. Os87 arquivos/artefatos locais registrados antes das mudanças, incluindo .env, mantiveram seus hashes.
- Pós-conferência somente leitura: MySQL manteve as contagens comerciais vazias anteriores, mas o binlog ativo avançou de107960 para110792. Os eventos adicionais são duas atualizações em sessions, sem DDL ou alterações comerciais no trecho. A origem dessas atualizações não foi determinada; não se afirma que o servidor inteiro ficou sem escritas durante o trabalho. Uma captura suplementar foi preservada em supplemental-* sem sobrescrever as duas extrações originais. A segurança dos testes é demonstrada pelos guards/tripwires e pelo positivo SQLite, não por suposta imobilidade de uma instância que continua ativa.

Limitação do Collision instalado: a forma positiva com --env separado antes do comando repassa testing como arquivo para PHPUnit. O bloqueio preventivo do processo pai foi comprovado também nesse formato; a forma de execução recomendada é php artisan test, sem esse argumento global. Nenhuma alteração foi feita em vendor.

## Camada externa independente

Para a proteção mais forte, execute numa VM/container descartável ou conta de execução isolada:

- Prepare uma cópia de trabalho sem .env local, credenciais, backups, binlogs ou caches da máquina. Inclua somente .env.testing sintético, código, assets e vendor aprovado. Não monte o perfil do usuário, datadir ou sockets do MySQL.
- Use uma imagem previamente aprovada com PHP compatível e pdo_sqlite. Nenhuma imagem, Docker ou dependência foi instalada nesta execução.
- Desabilite a rede do container (--network none), sem host networking e sem portas/sockets de banco montados. Na VM/conta alternativa, bloqueie também loopback/host MySQL por política externa adequada; limpar variáveis sozinho não isola rede.
- Forneça filesystem temporário para storage/bootstrap cache/resultados, sem acesso de escrita aos dados locais. Passe apenas variáveis sintéticas e execute PHPUnit com --do-not-cache-result se a raiz for somente leitura.
- Valide a política de rede sem usar credenciais ou consultas de escrita no banco real. O isolamento externo deve continuar protegendo mesmo que um teste use PDO/curl diretamente ou o código PHP do guard seja removido.

Essa camada está documentada, **não provisionada/validada nesta máquina**. Os guards PHP cobrem acesso acidental pelas APIs Laravel e os entrypoints implementados; não são sandbox para código malicioso que abra sockets/PDO nativos ou substitua as próprias proteções.

## Plano de reconstrução — NÃO EXECUTADO

1. Verificar novamente hashes e trabalhar em cópia dos arquivos preservados. Preservar as chaves em custódia separada antes de qualquer validação de criptografia.
2. Criar instância MySQL8.4 compatível, separada, sem acesso da aplicação local e sem sobrescrever o servidor original. Compatibilizar charset/collation, lower_case_table_names e demais metadados preservados.
3. A sequência necessária registrada começa em .000253, com CREATE DATABASE shopplus_db na posição235,23/10 às17:54. Inclui schema das32 tabelas e operações posteriores; prossegue sem lacuna de nomes para .000254. Todos os39 logs foram guardados como contexto adicional. Existem dois DROPs anteriores no dia23; fazem parte da história e não devem ser arbitrariamente removidos para reconstruir o estado imediatamente anterior ao incidente6A.
4. O commit Xid em .000254 começa em49946 e termina em49977,24/10 às11:43:54. O grupo destrutivo Anonymous_Gtid começa em49977; o DROP começa em50056 e termina em50655,às11:59:04. A análise de eventos valida49977 como **candidato** ao limite antes do grupo destrutivo. Nenhum --stop-position/--stop-datetime ou replay foi executado; a aplicação desse corte exige autorização de recuperação e revisão das cópias/limites na instância separada.
5. A leitura offline não encontrou referências de foreign keys a outro schema no intervalo relevante. Isso não exclui dependências semânticas externas, operações com binary logging desabilitado ou dados não registrados. A integridade de bytes/grupos observada não garante reconstrução completa da aplicação.
6. Após recuperação autorizada em outra instância, comparar contagens agregadas, migrations, constraints e relacionamentos; verificar usuários/produtos/estoque e snapshots/totais históricos de pedidos/pagamentos/entregas. Validar descriptografia local sem imprimir dados pessoais, enviar e-mails ou acionar pagamentos/jobs. Comparar com3 usuários/16 produtos/2 pedidos/1 pagamento/1 entrega derivados dos logs.
7. Manter ambas as instâncias intactas até revisão humana. Qualquer substituição do banco original, integração da aplicação, restauração de arquivos ou repopulação exige outra autorização.

Pendências: índice físico original e datas do filesystem exigem acesso administrativo; cópia criptografada externa/offline e custódia das chaves ainda dependem do usuário. VSS/backups físicos não foram confirmados. Recuperação não foi tentada. Testes MySQL específicos continuam fora desta suíte; se necessários no futuro, exigem arquitetura descartável e política separada, sem relaxar este guard.

Fontes primárias: [mysqlbinlog raw/static backup](https://dev.mysql.com/doc/refman/8.4/en/mysqlbinlog-backup.html), [métodos de backup InnoDB](https://dev.mysql.com/doc/refman/8.4/en/backup-methods.html), [recuperação por posições](https://dev.mysql.com/doc/refman/8.4/en/point-in-time-recovery-positions.html), [ambiente de testes Laravel](https://laravel.com/framework/docs/12.x/testing), [configuração PHPUnit11](https://docs.phpunit.de/en/11.5/configuration.html).
