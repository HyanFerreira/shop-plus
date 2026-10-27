# ShopPlus — implantação na VM, 26/10/2026

**Implantado e validado:** [https://10.81.211.127](https://10.81.211.127). HTTPS autoassinado; confiança do navegador não foi instalada automaticamente. NAT administrativo e acesso alternativo preservados. Nenhum comando acessou o banco original do Windows; não houve commit, push ou alteração de dependências.

**Atualiza??o posterior, solicitada pelo usu?rio:** verifica??o de e-mail desativada no c?digo local e na VM (`User` sem `MustVerifyEmail` e recurso Fortify desabilitado), com caches reconstru?dos. Nenhum status de verifica??o de contas existentes foi alterado. Cadastro novo n?o verificado acessou dashboard/checkout/pedidos/favoritos com HTTP 200; visitante continuou redirecionado ao login e cliente continuou impedido de acessar admin. Su?te isolada ap?s a mudan?a: 349 testes, 2.513 assertions, zero falhas e 7 condicionais ignorados. Os resultados de verifica??o de e-mail descritos abaixo s?o da implanta??o inicial; esta atualiza??o prevalece para o comportamento atual. Backup dos dois arquivos anteriores na VM: `/home/server/shopplus-deploy/before-email-disabled-o6brn5`.

## Acessos e rede

| Item | Configuração comprovada |
| --- | --- |
| VM | VirtualBox `server`, UUID `72326722-60b9-4dce-9212-ae797c92f39f`; Pop!_OS 24.04 LTS x86_64; 4096 MB, 3 CPUs, 21 GB livres após instalação |
| Fuso Linux | `America/Sao_Paulo` |
| Adaptador 1 | NAT preservado; `enp0s3`, `10.0.2.15/24`, MAC `08:00:27:00:55:7C`; única rota padrão via `10.0.2.2` |
| Adaptador 2 | Bridge na Realtek PCIe GbE Family Controller; `enp0s8`, `10.81.211.127/24`, MAC `08:00:27:ED:4A:86` |
| Perfil Bridge | `shopplus-lab`, vinculado pelo MAC, `ipv4.never-default yes`, sem gateway/DNS, IPv6 desativado e detecção de endereço duplicado em 5000 ms |
| Host Windows | `10.81.211.27/24`; gateway `10.81.211.254`; DNS `10.81.211.200` e `10.81.211.254`; rede Windows não alterada |
| SSH | Alias `shopplus-vm`; `server@127.0.0.1:2222`, chave exclusiva Ed25519; novas conexões confirmadas após reboot |
| ShopPlus principal | `https://10.81.211.127`; HTTP redireciona com 301 para HTTPS |
| Alternativa local | `https://127.0.0.1:8443`; HTTP `127.0.0.1:8080` redireciona para 8443 |
| Mailpit | SMTP `127.0.0.1:1025`, web `127.0.0.1:8025`, somente na VM/túnel; serviço `mailpit` dedicado, sem relay externo |
| MySQL | Schema novo `shopplus_sds`; 3306/33060 em loopback; contas limitadas de aplicação, migração e leitura |

O usuário autorizou `.127` conforme orientação do professor de somar 100 ao IP do host. Não foi apresentada reserva formal do TI. As consultas direcionadas anteriores não encontraram resposta ICMP/MAC; isso não provou disponibilidade. A interface subiu com detecção de duplicidade, e o Windows comprovou acesso TCP/HTTPS. Recomenda-se registrar o IP e o MAC com o TI. Não houve varredura da rede.

SSH Windows, sem administrador:

```powershell
& "C:\Program Files\Git\usr\bin\ssh.exe" shopplus-vm
```

A chave privada possui ACL restrita a `IFSP\cg3034704`, mas foi criada **sem passphrase**. Fingerprint público: `SHA256:F4TIwUX4+Pt6b83desXrW97yR519j9CVkr4KgW8Iq4c`. Pode receber passphrase interativamente com `ssh-keygen -p -f "$env:USERPROFILE\.ssh\shopplus_vm_ed25519"`. Login root, senha SSH e autenticação interativa SSH foram desativados após validar a chave e `sshd -t`; nenhum `NOPASSWD` foi criado.

## Fonte, versões e dados

- Workspace: `C:\Users\cg3034704\Documents\GitHub\sistema-comercio`, branch `main`, HEAD `36f442ccaef4a07cb81bc0fb0202da8f94697f73`. HEAD e `origin/main` estavam iguais; consulta aos dois endereços GitHub retornou esse commit. O `origin` cadastrado usa `HyanFerreira/git-remote-add-origin-https---github.com-HyanFerreira-shop-plus.git`; o solicitado usa `HyanFerreira/shop-plus`. Nenhum remote foi alterado.
- Deploy de `/var/www/shopplus` inclui as mudanças locais não commitadas de CSS/views/assets. A alteração especificamente autorizada habilitou verificação de e-mail em `app/Models/User.php` e `config/fortify.php`; o teste acadêmico foi ajustado para seguir o link assinado antes da compra e comprovar o bloqueio anterior à verificação.
- Snapshot: `%LOCALAPPDATA%\ShopPlusDeploy\2026-10-26T23-22-21-602Z`; pacote remoto `/home/server/shopplus-deploy/shopplus-deploy.tar`, com 363 arquivos. SHA-256: `b53055fc1d64fc4d35570bb8494fea999b836022158c20d37a6f667773969b10`; manifesto `9a37bd3226b5451b6414b34587e6c8d8a2cf04529b8f7837a14e437c4ffd531b`. Pacote e arquivos extraídos conferidos. `.env`, bancos, backups, logs, vendor Windows e evidências de review foram excluídos.
- Instalados: **PHP CLI/FPM 8.4.27** (`8.4.27-1+ubuntu24.04.1+deb.sury.org+1`), Nginx `1.24.0-2ubuntu7.19`, MySQL `8.0.46-0ubuntu0.24.04.4`, Composer `2.10.3`, Mailpit `1.32.0`, cron `3.0pl1-184ubuntu2`. Laravel `12.69.2`, Livewire `3.8.10`, Vite `6.4.5`.
- PHP 8.3 dos repositórios do SO não atendia às dependências Symfony do lockfile, que exigem PHP >=8.4.1. PHP 8.4 foi instalado pelo PPA de Ondřej Surý com `Signed-By`, após conferir a chave `B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6`, assinatura válida do InRelease e hash do índice. FPM 8.3 desativado. Lockfile preservado, sem `composer update` ou ignorar requisitos.
- Atualizações do SO concluídas e VM reiniciada. Composer oficial instalado com verificação SHA-384; dependências de produção instaladas como `server`, com autoload otimizado e requisitos de plataforma aprovados. Audit sem advisories/pacotes abandonados. Mailpit oficial conferido pelo digest SHA-256 do asset GitHub.
- Assets compilados em cópia isolada no Windows com Node `24.11.1`, npm `11.6.2`, `npm ci` e build. Templates Blade exigidos pelo Tailwind incluídos e conferidos com o lockfile. Sem Vite de desenvolvimento nem `public/hot` na VM.
- O script exigiu schema/contas inexistentes e comprovou a conexão efetiva e zero tabelas antes de `migrate --force`. Banco `utf8mb4`, conta runtime `shopplus_app@127.0.0.1` com DML apenas no próprio schema; migração e leitura separadas. Nenhum reset, wipe, truncate ou restauração.
- Seeders de dados e imagens concluídos sem Faker/dev dependencies no site. Baseline: 3 usuários fictícios, 6 categorias, 16 produtos/estoques/imagens, 2 pedidos, 1 pagamento e 1 entrega. Após cadastros e fluxos de validação: 6 contas, 4 pedidos, 3 pagamentos e 3 entregas; produtos/categorias preservados e invariantes de estoque aprovados. Dados persistiram após reboot.
- `.env` exclusivo da VM: produção, debug desativado, `APP_URL=https://10.81.211.127`, APP_KEY/PII blind index/DEMO_PASSWORD aleatórios e independentes; sessões cifradas em banco com cookies Secure/HttpOnly, cache e filas em banco. `storage:link`, caches e permissões aplicados; `www-data` escreve somente nos diretórios runtime. `server` integra o grupo de manutenção `www-data`.

## Mailpit, Workbench e credenciais

As credenciais de demonstração e leitura estão no arquivo **privado, fora do Git**, `%USERPROFILE%\.ssh\shopplus-setup-20261026\access.json`, ACL restrita ao usuário Windows. Cópia privada na VM: `/home/server/shopplus-deploy/access.json`. Não incluir o conteúdo em relatórios ou screenshots. As contas demo são `admin@comercio.example.test`, `cliente@comercio.example.test` e `cliente2@comercio.example.test`.

Abrir túneis somente no loopback Windows e manter o terminal aberto:

```powershell
& "C:\Program Files\Git\usr\bin\ssh.exe" -N -L 127.0.0.1:8025:127.0.0.1:8025 -L 127.0.0.1:13306:127.0.0.1:3306 shopplus-vm
```

Mailpit: `http://127.0.0.1:8025`. Se a porta já estiver ocupada pelo túnel de validação, reutilizá-lo ou encerrá-lo antes de abrir outro. Mensagens de recuperação/verificação contêm tokens sensíveis.

Workbench: **Standard TCP/IP**, hostname `127.0.0.1`, porta **13306**, usuário `shopplus_reader`, schema `shopplus_sds`; senha no arquivo privado. O túnel foi testado do Windows: servidor `pop-os`, schema correto, 16 produtos e somente SELECT/SHOW VIEW. Nunca usar a porta MySQL original do host como destino dos comandos de implantação.

## HTTPS, isolamento e operação

- Nginx serve somente `/var/www/shopplus/public`; executa apenas `index.php` via socket PHP 8.4; bloqueia dotfiles, PHP arbitrário, logs/backups e caminhos privados; directory listing e server tokens desativados; cache para assets versionados. CSP/CSRF/autenticação preservados.
- Certificado autoassinado com SAN `127.0.0.1` e `10.81.211.127`, chave root restrita. Expira **24/01/2027 às 21:07:41 (America/Sao_Paulo)**. Fingerprint SHA-256: `40:DB:58:6B:16:3C:5B:20:78:6A:E9:6A:43:ED:5A:D7:98:FB:B4:FE:0C:B7:39:29:5B:48:6E:8B:88:D9:AC:56`.
- Certificado público local: `%USERPROFILE%\.ssh\shopplus-setup-20261026\shopplus.crt`. Conferir com `openssl x509 -in <arquivo> -noout -fingerprint -sha256` e comparar com o fingerprint acima. Somente em dispositivo autorizado e após essa conferência, pode-se importar manualmente no repositório de certificados do usuário atual, autoridades raiz confiáveis, conforme política do laboratório. Nenhuma confiança global nem desativação global de TLS foi aplicada. HSTS foi removido pelo Nginx enquanto o certificado for autoassinado; HTTP redireciona somente após comprovar HTTPS.
- UFW ativo: entrada negada por padrão; SSH apenas pelo NAT; HTTP/HTTPS pelo NAT e pela origem `10.81.211.0/24` para `.127`. Testes TCP do Windows confirmaram 80/443 acessíveis e 22/3306/33060/1025/8025/631 fechados pela Bridge; 2222 local acessível.
- Scheduler único: `/etc/cron.d/shopplus`, `www-data`, `php artisan schedule:run` a cada minuto; `orders:expire` listado e execuções reais registradas como DONE. Regra original de 30 minutos preservada. Zero jobs pendentes; código próprio não implementa `ShouldQueue`, e notificações testadas foram entregues sincronamente. Worker separado não foi instalado.
- FPM efetivo conferido: `session.upload_progress.enabled=Off`, `zend.exception_ignore_args=On`, `expose_php=Off`; nenhum phpinfo/diagnóstico público.
- Reinício: `sudo nginx -t` e depois `sudo systemctl restart nginx php8.4-fpm mysql mailpit cron`. Antes de alterar SSH: `sudo sshd -t`; manter uma conexão por chave confirmada. Logs: `journalctl -u <servico>` e `storage/logs/`; scheduler em `storage/logs/scheduler.log`.
- Snapshot antes dos alunos: desligar de forma limpa com `sudo systemctl poweroff`; confirmar `VMState="poweroff"` no host; executar `VBoxManage snapshot server take <nome-unico>` e `VBoxManage startvm server --type headless`. Nenhum snapshot criado nesta execução. Restaurar reverte dados/configuração e exige confirmação. Backup da configuração anterior à Bridge preservado fora do Git em `%USERPROFILE%\.ssh\shopplus-setup-20261026\server-before-bridge-*.vbox`.

## Validação e limitações

- Suíte completa em cópias isoladas no Windows e na própria VM: **349 testes, 2.523 assertions, zero falhas, 4 condicionais ignorados**, PHPUnit 11.5.56; PHP 8.4.24 local e 8.4.27 na VM. Ambas sem `.env` privado/cache de configuração, com SQLite `:memory:`, guardas originais e drivers MySQL desativados (`PDO_DRIVERS=sqlite`). Dev dependencies ficaram somente na cópia privada de testes, fora do webroot. A primeira tentativa Linux foi interrompida pelo desligamento; a repetição após reboot concluiu com sucesso.
- Navegador Edge real: **10 checks desktop/mobile + 11 fluxos**, todos aprovados por NAT e novamente pela Bridge. Cobertura: home, CSS/JS sem Vite, catálogo/produto, pesquisa/filtro, cadastro, verificação e reset pelo Mailpit, login/cookies, favoritos, carrinho persistido, checkout, pagamento exclusivamente fictício e histórico; visitante bloqueado, cliente sem admin e pedido de outro cliente negado. Dois checks adicionais confirmaram Figtree carregada em desktop/mobile e nenhuma falha de recurso.
- TLS pelo Windows aprovado com o certificado específico (`ssl_verify_result=0`); HTTP 301 correto em ambos os endereços. O contexto automatizado do navegador aceitou o autoassinado apenas durante os testes; confiança permanente não instalada.
- 13 checks runtime aprovados, incluindo conexão/banco corretos, ausência de debug, cookies, chaves no formato esperado, SMTP local, imagens/assets, estoque e ausência de Faker em produção. Persistência, serviços e SSH confirmados após reinício; túnel de leitura validado sem escrita.
- Expiração coberta pela suíte isolada e execução real do comando pelo cron. Não foi forçado nem aguardado um pedido vencer 30 minutos em produção. Um acesso imediatamente após boot retornou 500 enquanto MySQL ainda estava `activating`; após o serviço ficar ativo, os checks HTTP/navegador passaram. Acesso de outro computador da turma, reserva formal do IP e instalação de confiança nos dispositivos autorizados permanecem sem comprovação.
- Nenhuma screenshot criada. Todos os JSONs de navegador estão exclusivamente em `.artifacts/visual-reviews/`, gerados por `visualReviewOutput`, com execuções separadas preservadas. `git check-ignore` e `git status --short --untracked-files=all` conferidos ao encerrar.

Referências: [Composer](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md), [Mailpit](https://github.com/axllent/mailpit/releases), [PPA/fingerprint](https://launchpad.net/~ondrej/+archive/ubuntu/php), [correções Nginx](https://ubuntu.com/security/notices/USN-8955-1), [releases PHP](https://www.php.net/archive/2026.php).