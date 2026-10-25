# ShopPlus — instalação limpa na VM do IFSP

Preparação para **26/10/2026**. Este guia descreve ações futuras na VM; nenhuma migration, seeder, conta MySQL ou configuração de servidor foi aplicada à VM nesta preparação. O sistema operacional, os serviços e a conectividade dela ainda precisam ser verificados.

## O que pode ser preparado no Windows

- Conferir o GitHub e guardar a revisão publicada: `git fetch origin` e `git rev-parse origin/main`.
- Clonar `https://github.com/HyanFerreira/shop-plus.git`, branch `main`. O Git inclui fontes, SVGs, componentes, testes, lockfiles e **`public/build` compilado**. `vendor`, `node_modules`, `.env` privado e dados de banco não são publicados.
- Se for reconstruir os assets, usar Node compatível com o lockfile (validado com Node 24.11.1), `npm.cmd ci` e `npm.cmd run build`. Não usar `npm update` ou `audit fix --force` na véspera.
- Providenciar Git, Composer 2 e acesso aos pacotes PHP na VM. Se a VM ficar sem internet, preparar um cache Composer confiável ou dependências produzidas em Linux compatível, no mesmo commit/lockfile. O clone sozinho não inclui as bibliotecas Composer.
- Guardar senhas e chaves em local privado. **Não levar o `.env`, PHP Windows, cache de configuração, logs, banco ou diretório privado do laboratório local.**

Os comandos Linux a seguir dependem da VM e de seu administrador. Se ela usar Windows, adaptar serviços, caminhos e permissões ao ambiente real; não executar comandos `apt`, `systemctl`, `chown` ou `chmod` nela por analogia.

## 1. Inventário e pré-requisitos na VM

Antes de instalar qualquer pacote, identificar o SO e o servidor existentes:

```sh
cat /etc/os-release
uname -m
command -v php
php -v
php --ini
php -m
composer --version
git --version
mysql --version
ss -lnt
```

PHP mínimo estrutural do Laravel 12: 8.2. Para esta demonstração, preferir **PHP 8.4.27 ou posterior corrigido na linha 8.4**, versão homologada localmente. O aviso oficial de 21/10/2026 também lista 8.2.35, 8.3.36 e 8.5.12 como mínimos corrigidos das respectivas linhas. Um pacote anterior só pode ser aceito com comprovação do backport pelo fornecedor. Confirmar novamente os [avisos PHP](https://www.php.net/archive/2026.php) no dia da instalação.

Extensões: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer e XML; adicionar **pdo_mysql** para esta aplicação. PHP precisa oferecer **Argon2id** (`php -r 'var_export(in_array("argon2id", password_algos(), true));'`). Para rodar a suíte, também são necessários pdo_sqlite/SQLite e as dependências de desenvolvimento. GD é recomendado para recursos de imagens; ZIP/unzip facilita a instalação Composer. Conferir a lista completa contra o lockfile com `composer check-platform-reqs` após instalar as dependências.

Usar MySQL 8 com InnoDB; a homologação isolada anterior usou MySQL 8.4.13. Não assumir equivalência com MariaDB ou configuração desconhecida. Git, Composer 2 e Nginx/PHP-FPM ou Apache com PHP são necessários. Node só é necessário para **reconstruir** o build, não para servir os assets incluídos.

Após o clone, executar:

```sh
php scripts/security/php-runtime.php
```

Esse inventário não atesta a versão do HTTP. Identificar o pool FPM/mod_php e o INI efetivamente carregado pelo servidor, aplicar as diretivas de [php-sds.ini.example](../security/7b/php-sds.ini.example), reiniciar o serviço correto e conferir o worker real por diagnóstico administrativo privado, fora do webroot. Não publicar `phpinfo()` ou endpoint diagnóstico. Exigir `session.upload_progress.enabled=0` e `zend.exception_ignore_args=On`. Se a versão HTTP estiver vulnerável, corrigir antes de liberar acesso.

## 2. Clone e dependências

Em diretório **novo**, sob um usuário de deploy sem privilégios globais:

```sh
git clone --branch main --single-branch https://github.com/HyanFerreira/shop-plus.git /var/www/shop-plus
cd /var/www/shop-plus
git rev-parse HEAD
git status --short
test -f composer.lock
test -f public/build/manifest.json
test ! -f .env
test ! -f public/hot
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
composer audit --locked
```

O administrador deve preparar previamente as permissões do diretório pai para esse usuário. Não rodar Composer como root. Usar **install**, preservando o lockfile. Não executar `composer create-project`: seus scripts podem gerar chaves e iniciar migrations automaticamente. `composer install` não instala os dados de demonstração.

As pastas runtime vazias são preservadas por `.gitignore` internos. Confira `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views` e `bootstrap/cache`. Dependências Composer devem ser instaladas para o SO e as extensões da VM.

## 3. MySQL: schema novo e três contas

Somente o administrador da **VM de demonstração** deve executar este SQL. Use uma sessão privada, evitando salvar senhas no histórico compartilhado. Substitua os placeholders por senhas distintas. Não reutilize o banco local do desenvolvedor.

```sql
CREATE DATABASE shopplus_sds_demo_20261026
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shopplus_app'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-app>';
CREATE USER 'shopplus_migrate'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-migracao>';
CREATE USER 'shopplus_reader'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-leitura>';
GRANT SELECT, INSERT, UPDATE, DELETE ON shopplus_sds_demo_20261026.*
    TO 'shopplus_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON shopplus_sds_demo_20261026.*
    TO 'shopplus_migrate'@'127.0.0.1';
GRANT SELECT, SHOW VIEW ON shopplus_sds_demo_20261026.*
    TO 'shopplus_reader'@'127.0.0.1';
```

`CREATE DATABASE` deve falhar se o schema existir. Nesse caso, escolher outro nome **novo** e ajustar todas as referências; não apagar nem reutilizar dados. A conta de migração tem DDL somente nesse schema; aplicação e leitor não recebem DDL ou privilégios globais. Conferir a resolução da conta com `SELECT CURRENT_USER()` e `SHOW GRANTS`. Se o MySQL exigir conta `@localhost`, adaptar conta e host conscientemente, sem liberar `@'%'`.

MySQL deve escutar em loopback/rede privada restrita, sem porta 3306 disponível para a turma. Acesso Workbench remoto será por túnel SSH.

## 4. `.env` e chaves próprias

Na instalação nova, **antes de existir dado cifrado**:

```sh
umask 077
cp .env.vm.example .env
php artisan key:generate --force
```

Editar `.env` privadamente, ajustando:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` para o endereço real acessível pelo navegador.
- `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, porta real, schema novo, `DB_USERNAME=shopplus_app` e senha própria. Não configurar `DB_URL` herdada.
- `PII_BLIND_INDEX_KEY`: chave aleatória independente, de 32 bytes representados em hexadecimal. Para gerá-la em terminal privado: `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Guardar o valor sem incluí-lo em logs, capturas ou Git.
- `DEMO_PASSWORD`: senha forte exclusiva das contas fictícias. Sem esse valor, o seeder imprime uma senha efêmera; evitar essa exposição durante a apresentação.
- `HASH_DRIVER=argon2id`, `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`.
- `LOG_LEVEL=warning`, logs privados com rotação; `MAIL_MAILER=array` para a demonstração sem SMTP.

O exemplo usa HTTPS e `SESSION_SECURE_COOKIE=true`. Configurar TLS válido no servidor. Se o administrador aprovar **HTTP apenas em laboratório isolado**, ajustar `APP_URL` para `http://...` e `SESSION_SECURE_COOKIE=false`; isso permite o cookie, mas não cifra o transporte. Não compartilhar essa configuração em rede ampla.

Preservar as mesmas `APP_KEY` e `PII_BLIND_INDEX_KEY` depois de criar dados. Não rodar `key:generate` novamente sobre banco existente. Essas chaves são necessárias para decifrar os dados e localizar índices cegos; guardar cópia privada separada do Git. A chave repetida de `.env.testing` é sintética e **não serve para a VM**.

## 5. Migrations e seeders, somente nesse schema novo

Antes de gerar caches, copiar `.env` para `.env.migrate`, manter exatamente as mesmas chaves/schema e trocar **somente** `DB_USERNAME`/`DB_PASSWORD` pela conta `shopplus_migrate`:

```sh
cp .env .env.migrate
chmod 600 .env.migrate
```

Editar esse arquivo privadamente. Na conexão da conta de migração, conferir:

```sql
SELECT DATABASE(), CURRENT_USER();
SELECT COUNT(*) AS tabelas
FROM information_schema.tables
WHERE table_schema = 'shopplus_sds_demo_20261026';
```

Só prosseguir se o schema for o **novo aprovado**, estiver vazio e a instalação não tiver cache de configuração antigo. Confirmar que variáveis exportadas no shell não sobrescrevem o arquivo. Na pasta nova da VM:

```sh
php artisan --env=migrate migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan migrate:status
php artisan config:cache
php artisan view:cache
```

O seed roda com a conta DML de `.env`, após as migrations da conta separada. Não usar `migrate:fresh`, `db:wipe`, drop ou restauração. O seeder cria dados exclusivamente fictícios: 3 usuários, 16 produtos, fornecedor/estoque, 2 pedidos, 1 pagamento e 1 entrega em base vazia. A repetição preserva estoque/reservas, mas não deve substituir o ensaio de uma instalação nova. Não rodar seed após gerar config cache para tentar alterar `DEMO_PASSWORD`: o seeder lê `env()` antes do cache, e senhas existentes não são um mecanismo de recuperação.

Contas: `admin@comercio.example.test`, `cliente@comercio.example.test` e `cliente2@comercio.example.test`, com a senha privada definida para o seed. Um novo cliente pode cadastrar-se e comprar. Usar somente dados fictícios também durante o ensaio.

`MAIL_MAILER=array` descarta as mensagens. O cadastro/compra atual não exige confirmação de e-mail: esse recurso já estava desativado no código e não deve ser habilitado/desabilitado às pressas. Recuperação de senha não entrega e-mail nesse modo. Para demonstrar o envio, usar transporte privado controlado; a recuperação e os tokens foram testados separadamente com notificações falsas em SQLite. Não usar `MAIL_MAILER=log` em logs compartilhados, pois pode registrar links/tokens.

## 6. Assets, servidor e permissões

`public/build/manifest.json` e seus CSS/JS estão versionados. Imagens e SVGs de demonstração são locais. **Não iniciar `npm run dev` ou expor Vite/5173.** Se for reconstruir na VM, instalar Node compatível e rodar `npm ci && npm run build` com fontes confiáveis. Fonte externa Figtree é opcional: há fallback CSS, sem impedir cadastro/compra quando indisponível.

Configurar o webroot exclusivamente como **`/var/www/shop-plus/public`**. O Nginx pode partir do [template existente](../security/nginx-vm.conf.example); adaptar hostname, certificado e socket real do FPM. Validar com `nginx -t` antes de recarregar. O template é HTTP e não provisiona TLS automaticamente. Para Apache, usar `DocumentRoot` em `public/`, módulo rewrite e permissão de override para o `.htaccess` existente, ou regras equivalentes no VirtualHost; testar a configuração antes do reload.

PHP deve executar somente o front controller autorizado; uploads, `.git`, `.env`, logs, dumps e backups ficam fora do acesso HTTP. Não usar `php artisan serve --host=0.0.0.0` como servidor de apresentação à turma.

Em Linux, supondo serviço **realmente identificado** como grupo `www-data` e usuário de deploy dono do checkout, a partir de `/var/www/shop-plus`:

```sh
sudo chgrp -R www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 2770 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 660 {} \;
sudo chgrp www-data .env
chmod 640 .env
chmod 600 .env.migrate
DEPLOY_USER="$(id -un)"
sudo setfacl -R -m "u:$DEPLOY_USER:rwX,u:www-data:rwX,m::rwX,o::---" storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec setfacl -m "d:u::rwx,d:u:$DEPLOY_USER:rwx,d:u:www-data:rwx,d:g::---,d:m::rwx,d:o::---" {} \;
umask 007
```

Adaptar o usuário/grupo ao serviço real, inclusive no arquivo `.env`. `setfacl` exige suporte a ACL e o utilitário do SO; conferir com `getfacl storage/logs bootstrap/cache`. As ACLs de acesso e padrão mantêm leitura/escrita para deploy e PHP em arquivos futuros, inclusive logs diários; setgid e chmod pontuais sozinhos não garantem isso. Se ACL não estiver disponível, o administrador deve configurar uma política equivalente de umask/permissões nos serviços e cron e provar acesso cruzado sem expor arquivos a outros usuários. O `umask 077` usado para criar segredos não deve ser herdado pelas tarefas runtime.

O PHP precisa ler o código e atravessar os diretórios pais; somente `storage` e `bootstrap/cache` precisam de escrita. A conta web não precisa ler `.env.migrate`. Não aplicar `chmod 777` nem dar posse do checkout inteiro ao servidor. Windows exige ACL equivalente, não esses comandos.

Scheduler no crontab do usuário de deploy autorizado, com permissões e PHP corretos:

```cron
* * * * * umask 007; cd /var/www/shop-plus && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Substituir `/usr/bin/php` pelo executável conferido. Isso executa a expiração de pedidos/reservas. O fluxo comercial atual é síncrono; se houver jobs enfileirados, configurar worker supervisionado sob usuário restrito.

## 7. Workbench e demonstração de leitura

No Windows, abrir MySQL Workbench e escolher **Standard TCP/IP over SSH**:

| Campo | Valor |
| --- | --- |
| SSH hostname/port | Endereço e porta SSH reais da VM |
| SSH username | Conta autorizada da VM, preferencialmente com chave |
| MySQL hostname/port | `127.0.0.1` e porta interna real, normalmente 3306 |
| MySQL username | `shopplus_reader` |
| Default schema | Nome novo aprovado para a demonstração |

Inserir a senha leitora privadamente, sem colocá-la em capturas. Dentro da VM, Workbench pode usar TCP direto ao loopback. Não abrir MySQL na rede para facilitar essa conexão. Verificar `SELECT DATABASE(), CURRENT_USER(), VERSION();` e `SHOW GRANTS;`; devem aparecer somente SELECT/SHOW VIEW no schema da demonstração, sem privilégios de escrita.

Executar [workbench.sql](../security/7c/workbench.sql), ajustando `@pedido_demo` ao ID criado na própria VM. É um roteiro de SELECTs, não um dump. Mostra prefixos de hash/cifra, totais históricos, itens, pagamentos sem PAN/CVV e saldo/reserva. Não selecionar senhas completas, tokens, códigos 2FA, sessões ou documentos pessoais. Registrar ID público, total e estado do pedido fictício ensaiado.

## 8. Conferência antes da turma

- [ ] Commit do clone igual à revisão publicada; dependências instaladas sem ignorar requisitos.
- [ ] PHP CLI **e HTTP** corrigidos; INI efetivo e reinício confirmados na própria VM.
- [ ] HTTPS/cookie compatíveis, URL e relógio corretos, `APP_DEBUG=false`.
- [ ] `/up` retorna 200; `/.env`, `/.git/config`, `/composer.json`, `/artisan` e `/storage/logs/laravel.log` retornam 403/404 sem conteúdo.
- [ ] Home, login, cadastro, catálogo e conta carregam CSS/JS pelo servidor, sem conexão ao Vite.
- [ ] Cadastro novo → logout/login → dados/endereço fictícios → carrinho → checkout → pagamento fictício → pedido/rastreio.
- [ ] Cliente em `/admin`: 403; pedido de outro cliente: 404. Não remover CSRF ou 2FA para facilitar o ensaio.
- [ ] Scheduler ativo; estoque/reservas e totais coerentes no Workbench com conta leitora.
- [ ] MySQL/Vite e ferramentas administrativas fora da rede da turma; logs, chaves e backups privados.

Cartões **públicos de teste** do simulador: `4111 1111 1111 1111` (aprovação Visa), `5555 5555 5555 4444` (aprovação Mastercard), `4000 0000 0000 0002` (recusa); validade futura e CVV fictício de três/quatro dígitos. Nunca usar cartão real.

## Diagnóstico rápido

| Sintoma | Conferência |
| --- | --- |
| 500 ou chave ausente | Permissão de `.env`, `APP_KEY`, `PII_BLIND_INDEX_KEY`, extensões e log privado; sem ligar debug para a turma |
| CSS ausente / Vite manifest not found | `public/build/manifest.json` e arquivos referenciados presentes; webroot correto; nenhum `public/hot` herdado |
| Assets tentam porta 5173 | Arquivo `public/hot` copiado de desenvolvimento; não deveria existir no clone limpo |
| 419, login retorna à página | URL/protocolo, cookie Secure, domínio, sessão e relógio; não desativar CSRF |
| Access denied MySQL | Schema, usuário/host reais e grants; `127.0.0.1` e socket `localhost` podem selecionar contas diferentes |
| Migration falha por DDL | Conferir `.env.migrate`, ausência de config cache antigo e conta de migração; nunca conceder root ao runtime |
| Arquivo sem permissão | Dono/grupo/ACL e travessia dos diretórios; escrita só em storage/cache |
| SMTP não recebe | `array` descarta mensagens por projeto; isso não impede cadastro/compra atuais |
| Dado cifrado não abre | Chaves diferentes daquelas do seed; recuperar a configuração privada correta, não gerar outra chave |
| Mudança em `.env` não aparece | `config:cache` existente; após conferir a configuração da VM, usar `php artisan config:clear` e reconstruir o cache |
| Pedidos não expiram | Crontab, PHP selecionado, permissões e timezone do scheduler |

A suíte principal **nunca** deve apontar para MySQL. Para validação na VM, usar checkout de testes separado, dependências dev, `.env.testing` versionado, sem config cache e variáveis explícitas `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=` antes de `php vendor/phpunit/phpunit/phpunit`. Não alterar os guards para contornar falhas. Não executar a suíte sobre a instalação runtime em uso.

Referências: [deployment Laravel 12](https://laravel.com/framework/docs/12.x/deployment), [avisos oficiais PHP](https://www.php.net/archive/2026.php), [resultados da homologação isolada anterior](../security/7c/resultado.md) e [validação final do repositório](../release/2026-10-25-sds.md). A homologação Windows/local não atesta que a VM esteja pronta.
