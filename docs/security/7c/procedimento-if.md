# ShopPlus — inicialização e ensaio no IF

> **Atualização de 25/10/2026:** para a instalação a partir da `main` publicada, usar o [guia atual de clone limpo](../../installation/ifsp-vm.md). O texto abaixo registra o procedimento da Etapa 7C, anterior à publicação da Etapa 8; a necessidade histórica de ZIP com alterações não commitadas não se aplica à versão final. Os assets compilados agora fazem parte do Git.

**A VM não foi inspecionada nesta sessão.** Use este roteiro no computador da faculdade antes de conectar a turma. Os comandos Linux são exemplos para uma VM Linux; identifique o SO e o servidor antes de executá-los. Não reutilize o banco original nem o `.env` do Windows.

## 1. Levar os arquivos certos

Use o pacote ZIP gerado nesta Etapa7C, que inclui o código atual **com alterações não commitadas**, assets compilados e este roteiro. Um simples `git clone` do HEAD antigo não contém todo o trabalho local. O ZIP não inclui `.env`, vendor, node_modules, logs, uploads privados, bancos ou credenciais do laboratório.

Antes de sair de casa, providencie acesso aos pacotes Composer/um cache confiável para a VM; não presuma que haverá internet. O build já vai em `public/build`. Instale extensões nativas para o SO da VM; não copie o PHP Windows para Linux. Não transportar a pasta privada do laboratório como configuração de produção.

## 2. Preflight do ambiente real

Em Linux, registrar localmente:

```sh
cat /etc/os-release
uname -m
command -v php
php -v
php --ini
php -m
composer --version
mysql --version
ss -lnt
```

Em Windows: `Get-Command php,composer`, `php -v`, `php --ini`, `php -m`, `Get-CimInstance Win32_Service` e as portas do servidor. Não divulgar credenciais ou configurações completas que contenham segredos.

Identificar Nginx/Apache/FPM/mod_php e seu executável/pool. Exigir pacote corrigido conforme aviso PHP:8.4.27+,8.3.36+,8.2.35+,8.5.12+ ou backport oficial comprovado. Rodar `php scripts/security/php-runtime.php`, mas **conferir o SAPI HTTP separadamente**. Aplicar `docs/security/7b/php-sds.ini.example` ao INI efetivo e reiniciar workers. Não publicar phpinfo. Se o HTTP continuar antigo, parar aqui.

## 3. Criar banco NOVO e contas separadas

O administrador executa o seguinte **somente na instância descartável aprovada da VM**. São instruções, não operações executadas nesta máquina. `CREATE DATABASE` deve falhar se o nome já existir; escolher outro nome novo em vez de apagar/reutilizar dados.

```sql
CREATE DATABASE shopplus_sds_demo_20261026 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'shopplus_app'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-app>';
CREATE USER 'shopplus_migrate'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-migracao>';
CREATE USER 'shopplus_professor'@'127.0.0.1' IDENTIFIED BY '<senha-exclusiva-leitura>';
GRANT SELECT, INSERT, UPDATE, DELETE ON shopplus_sds_demo_20261026.* TO 'shopplus_app'@'127.0.0.1';
GRANT ALL PRIVILEGES ON shopplus_sds_demo_20261026.* TO 'shopplus_migrate'@'127.0.0.1';
GRANT SELECT, SHOW VIEW ON shopplus_sds_demo_20261026.* TO 'shopplus_professor'@'127.0.0.1';
```

Os textos entre `<...>` são placeholders: gerar senhas próprias, armazenar privadamente e nunca colocá-las no relatório. Conta de migração tem DDL só neste schema, sem privilégio administrativo global. Aplicação e professor não usam root. MySQL deve escutar em loopback; nenhuma porta3306 aberta à turma. Se a política da VM exigir socket/host localhost, adaptar contas e DB_HOST de forma consistente, sem usar `%` como atalho.

No Workbench remoto, usar **Standard TCP/IP over SSH**: SSH para a VM, destino MySQL127.0.0.1:3306 e conta `shopplus_professor`. Não é necessário expor MySQL remotamente. Verificar que o túnel autentica com a conta prevista. Se o Workbench estiver dentro da VM, conectar direto ao loopback.

## 4. Configurar e inicializar a aplicação

1. Extrair para um diretório novo, por exemplo `/var/www/shopplus-sds`; confirmar que `.env`/`public/hot` não vieram do Windows.
2. Copiar `.env.vm.example` para `.env` privado. Definir APP_ENV=production, APP_DEBUG=false, APP_URL real, schema novo acima, DB_HOST127.0.0.1 e conta runtime `shopplus_app`.
3. Gerar APP_KEY **só para essa base nova**; preservar a mesma chave após criar dados. Gerar `PII_BLIND_INDEX_KEY` aleatória e independente. Definir DEMO_PASSWORD forte em `.env` privado para não imprimir senha efêmera do seeder. Não divulgar essas saídas.
4. Instalar dependências compatíveis no destino: `composer install --no-dev --optimize-autoloader`; `composer check-platform-reqs`. O pacote inclui assets compilados, portanto Node/Vite não são necessários para servir a apresentação. Se precisar reconstruir, usar `npm ci` e `npm run build` em fonte confiável, sem expor o servidor dev.
5. Sessão: database, `SESSION_ENCRYPT=true`, HttpOnly/Lax. Com HTTPS confiável, Secure=true. Em HTTP de laboratório **isolado**, Secure=false é necessário para os cookies funcionarem; isso não oferece confidencialidade de transporte. Priorizar HTTPS e não expor uma configuração HTTP à rede ampla.
6. Para o ensaio sem e-mail externo: `MAIL_MAILER=array`. Isso não desativa autenticação ou verificação: a verificação de e-mail já está desabilitada no projeto existente e não foi alterada nesta etapa. Cadastro/login/compra foram testados assim. Recuperação de senha não será entregue por e-mail com array; não fingir que esse fluxo foi demonstrado. Para demonstrar recuperação, configurar SMTP privado real.
7. Antes de migrations, conferir o schema selecionado e que está vazio com a conta de migração. Criar `.env.migrate` privado com as mesmas chaves e schema, alterando apenas credenciais para `shopplus_migrate`. Não ter config cache antigo nesta instalação nova.
8. Executar **na pasta nova da VM**:

```sh
php artisan --env=migrate migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

`db:seed` usa a conta runtime/DML do `.env`, depois que o schema foi criado. Não usar migrate:fresh/db:wipe. O seeder cria3 usuários fictícios,16 produtos, fornecedor/abastecimento,1 pedido pendente e1 pago,1 pagamento e1 entrega numa base vazia. Repetição agora preserva estoque/reservas, mas não é uma restauração de histórico perdido. Guardar `.env.migrate` fora do acesso HTTP e utilizá-lo somente para manutenção DDL deliberada.

9. Em Linux, ajustar dono/grupo ao usuário real de deploy e do serviço; somente storage e bootstrap/cache precisam de escrita. `.env` e `.env.migrate` devem ser legíveis apenas pelos responsáveis e pelo processo que precisa deles, sem permissão para outros usuários. Não aplicar chmod777. Não regenerar chaves depois do seed.
10. Configurar VirtualHost/servidor apontando **exclusivamente para public/**. Template Nginx: `docs/security/nginx-vm.conf.example`; adaptar hostname/socket, validar com `nginx -t` e recarregar o serviço correto. Em Apache, conferir DocumentRoot e `.htaccess`. Um `php artisan serve --host=0.0.0.0` não é o plano de publicação na rede.
11. Configurar scheduler do usuário correto com `php artisan schedule:run` a cada minuto para expiração de reservas. Cache/filas devem usar os drivers escolhidos; se queue=database e houver jobs, iniciar worker com permissões próprias. O fluxo de compra/pagamento atual é síncrono.

## 5. Ensaio e checklist de liberação

- [ ] Registrar SO, PHP CLI e HTTP corrigidos, extensões, servidor e endereço/porta reais da VM.
- [ ] APP_DEBUG=false, webroot public e INIs efetivos verificados após restart.
- [ ] `/up`200; `/.env`, `/.git/config`, `/composer.json`, `/artisan` e `/storage/logs/laravel.log`403/404 sem conteúdo.
- [ ] Cadastro → sair/login → CPF/telefone/endereço fictícios → catálogo →2 unidades → frete → checkout → pagamento fictício aprovado → pedido/entrega.
- [ ] Não depender de SMTP para cadastro/compra. Não alterar middleware de autenticação ou CSRF para o ensaio.
- [ ] Cliente em `/admin`403; Cliente B no pedido de A404; ID de endereço alheio rejeitado sem alteração.
- [ ] Workbench conecta como leitor via loopback/túnel; executar `workbench.sql` com o ID do pedido recém-criado.
- [ ] Hash de senha, cifras/HMAC, total de itens+frete, pagamento único sem CVV/PAN, estoque e movimentações conferidos.
- [ ] Scheduler/expiração e retenção de logs funcionando; nenhum dump, chave, backup ou diagnóstico acessível pela web.
- [ ] Registrar resultados da própria VM. Os resultados concorrentes locais em MySQL8.4.13 não atestam automaticamente outro ambiente/configuração.
- [ ] Somente depois dessas verificações liberar o endereço HTTP/HTTPS aprovado à turma; sem expor MySQL/Vite ou credenciais demo em páginas públicas.

Os testes PHPUnit principais continuam proibidos no MySQL. Para repetir concorrência, usar o laboratório separado documentado em `resultado.md`, nunca alterar DB_CONNECTION/DB_DATABASE dos testes protegidos para apontar à VM.
