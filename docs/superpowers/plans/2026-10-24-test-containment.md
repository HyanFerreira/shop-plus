# ShopPlus — Contenção e isolamento dos testes

> **For agentic workers:** Use superpowers:executing-plans; execução nesta sessão, sem commits e sem restauração.

**Goal:** Preservar evidências e permitir somente SQLite :memory: na suíte Laravel.
**Architecture:** Bootstrap próprio, validação antes do bootstrap Laravel e após LoadConfiguration (antes de providers), factory/manager/connector protegidos durante a suíte. Arquivos exclusivamente de testes; funcionamento local/produção preservado.
**Tech Stack:** Laravel12, PHPUnit11, PDO SQLite; ferramentas MySQL8.4 já instaladas somente para preservação.
**Spec:** anexo03faacb2-cc85-45cb-b4ce-e480c4ac0356/Pasted text.txt.

## Global constraints

Sem replay, restauração, repopulação, migrations em bancos persistentes, alteração de .env, dependências, commits ou mudanças comerciais. Preservar todas as alterações5B/6A. Dados/segredos não entram no Git.

## Review focus

- URL/caches personalizados e env herdado não podem converter o destino em persistente.
- TestCase valida antes de RefreshDatabase; callback após parent::setUp é proibido.
- Conexão já resolvida, alias, build/reconnect e alterações de config devem passar pelo mesmo bloqueio.
- SQLite ATTACH/VACUUM INTO e opções de conexão não podem abrir arquivos persistentes por acidente.
- Nenhum teste negativo deve acessar MySQL ou criar PDO/migration; instrumentar conectores/preparação em processos sintéticos.

## Tasks

- [x] Preservar39 binlogs disponíveis, duas extrações raw independentes; checksums e hashes iguais; dump transacional atual; índice lógico reconstruído (índice físico sem acesso). ACL usuário/SYSTEM, fora do repositório. Arquivo ativo estável em107960 bytes durante captura.
- [x] Criar harness negativo sem Laravel/RefreshDatabase/PDO real; comprovar RED de proteção ausente.
- [x] Implementar tests/Support/{TestEnvironment,MemoryConnectionFactory,MemoryDatabaseManager,MemoryConnector}.php, tests/bootstrap.php e integração em tests/TestCase.php.
- [x] Configurar phpunit.xml/.env.testing, serviços em memória e chave sintética; não tocar .env.
- [x] Negativos: mysql/pgsql/arquivo/URL/alias/cache/default alterado/build/reconnect/opções/SQL de arquivo; comprovar zero tentativas de connector e zero preparação. Positivo isolado só depois.
- [x] Executar testes específicos e suíte normal protegida por PHPUnit direto, individual e artisan test. Comparar baseline208pass/7skip/3fail.
- [x] Revisão independente; registrar preservação, plano de recuperação sem replay, camada externa sem MySQL/rede e riscos remanescentes.

## Ledger / rulings

- Main d9b63f9, alterações5B/6A preservadas na mesma workspace autorizada. Não criar worktree nem mover arquivos locais.
- A especificação detalhada autoriza implementação; sem nova aprovação intermediária.
- Índice físico: acesso ao datadir negado e LOAD_FILE retornou NULL; SHOW BINARY LOGS preservado como índice reconstruído, explicitamente identificado.
- Não preservar APP_KEY em texto nos relatórios. Orientar custódia separada/criptografada; .env original permanece intacto.
- Cache de configuração existente será rejeitado mesmo quando aparentemente seguro, para não executar PHP cacheado inesperado. Nenhum cache será apagado automaticamente.
- Revisão encontrou cache introduzido por dotenv e resolver estático: RED sintético reproduzido e GREEN após bloqueio anterior a LoadConfiguration e ao resolver. Nenhum PDO real nas reproduções.
- Artisan também protege o pai antes dos providers. Revisão apontou --env separado e abreviação tes; detector ajustado e teste fortalecido para distinguir bloqueio no pai do filho. Casos negativos com host/credenciais fictícios, sem MySQL real.
- Gates finais:24 rejeições do harness de conexão,9 cenários de entrypoint e1 cache via dotenv; contadores zero e cache não executado. Validação positiva apenas em memória após todos os gates.
- Pint aprovado; PHPUnit final222 testes,1164 assertions:212 aprovados,7 skips, mesmas3 falhas baseline. Quatro testes novos aprovados. Artisan individual normal e abreviado aprovados; --version normal preservado.
- Limitação Collision: a execução positiva com --env separado antes do comando repassa testing como nome de arquivo ao PHPUnit na versão instalada. O guard do pai é demonstrado nesse formato; execução normal documentada é php artisan test. Não alterar vendor para corrigir esse comportamento fora do escopo.
- Hashes87 arquivos locais preservados, incluindo .env; sem diferenças. Metadata de preservação e pós-conferência do MySQL fora do Git.
