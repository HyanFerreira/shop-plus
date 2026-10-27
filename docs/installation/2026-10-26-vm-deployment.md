# ShopPlus — implantação na VM, 26/10/2026

**Estado: implantação parcial.** SO atualizado e serviços básicos ativos; código transferido e conferido. O lockfile exige PHP 8.4.1+, e o PHP 8.3 instalado não atende. A instalação de PHP 8.4 e a configuração da aplicação aguardam nova autenticação sudo. **O ShopPlus ainda não está disponível, nenhum banco da aplicação foi criado e nenhuma migration foi executada.**

## Fonte e alterações

- Workspace: `C:\Users\cg3034704\Documents\GitHub\sistema-comercio`; branch `main`; HEAD `36f442ccaef4a07cb81bc0fb0202da8f94697f73`. Comparação com `origin/main`: `0 0`; os dois endereços GitHub consultados retornaram esse commit.
- O `origin` cadastrado usa `HyanFerreira/git-remote-add-origin-https---github.com-HyanFerreira-shop-plus.git`; o endereço solicitado usa `HyanFerreira/shop-plus`. Nenhum remote foi alterado.
- A fonte mais recente inclui mudanças locais não commitadas em 10 CSS, duas views, manifest e assets. Foram preservadas. Com autorização específica do usuário, a implantação habilitou `Features::emailVerification()` em `config/fortify.php` e `MustVerifyEmail` em `app/Models/User.php`. O teste acadêmico foi ajustado para verificar o e-mail por link assinado antes da compra e confirmar o bloqueio anterior à verificação.
- Sem commits, push, alteração de dependências ou acesso ao banco original Windows.
- Snapshot: `%LOCALAPPDATA%\ShopPlusDeploy\2026-10-26T23-22-21-602Z`; pacote remoto `/home/server/shopplus-deploy/shopplus-deploy.tar`. São 363 arquivos selecionados, excluindo `.env`, `.git`, bancos, backups, logs, `vendor`, `node_modules`, `public/hot`, cache de configuração e evidências/documentação de review.
- SHA-256 do pacote atualizado: `b53055fc1d64fc4d35570bb8494fea999b836022158c20d37a6f667773969b10`; do manifesto: `9a37bd3226b5451b6414b34587e6c8d8a2cf04529b8f7837a14e437c4ffd531b`. Hash do pacote conferido na VM e integridade individual dos 363 arquivos extraídos validada.

## VM, rede e SSH

- VirtualBox `server`, UUID `72326722-60b9-4dce-9212-ae797c92f39f`, 4096 MB, 3 CPUs; Pop!_OS 24.04 LTS x86_64; timezone `America/Sao_Paulo`. Havia 25 GB livres antes das atualizações, e 22 GB durante a execução.
- Adaptador 1 preservado em NAT: MAC `08:00:27:00:55:7C`, interface `enp0s3`, `10.0.2.15/24`, default via `10.0.2.2`.
- Encaminhamentos vinculados exclusivamente ao loopback Windows: `127.0.0.1:2222 → VM:22` (preservado), `127.0.0.1:8080 → VM:80` e `127.0.0.1:8443 → VM:443` (adicionados). SSH retestado após a mudança.
- Host: `10.81.211.27/24`, Realtek PCIe GbE Family Controller, gateway `10.81.211.254`, DNS `10.81.211.200` e `10.81.211.254`. Rede Windows não alterada.
- IP pretendido `10.81.211.127`: usuário autorizou conforme orientação do professor de somar 100. Não foi apresentada reserva do TI. Consulta ICMP direcionada não respondeu e ARP ficou `Unreachable`, sem MAC válido; isso **não comprova disponibilidade**.
- Adaptador 2 ainda desativado. MAC preparado, ainda não aplicado: `08:00:27:ED:4A:86`. O script da aplicação prepara perfil NetworkManager vinculado a esse MAC, IP `/24`, sem gateway/DNS, `ipv4.never-default yes` e detecção de endereço duplicado. Esse perfil também ainda não foi aplicado. Bridge, reboot e acesso pela rede institucional permanecem pendentes.
- Alias `shopplus-vm` em `%USERPROFILE%\.ssh\config`: `server`, loopback, porta 2222, identidade exclusiva Ed25519. Somente a chave pública foi instalada na VM; ACL privada restrita a `IFSP\cg3034704`. Fingerprint público `SHA256:F4TIwUX4+Pt6b83desXrW97yR519j9CVkr4KgW8Iq4c`.
- Conexões independentes passaram com `BatchMode=yes` e autenticação por senha/interativa desabilitada no cliente. A chave foi criada **sem passphrase** e está carregada no agente Git desta sessão. Pode receber passphrase com `ssh-keygen -p -f "$env:USERPROFILE\.ssh\shopplus_vm_ed25519"`, exclusivamente em terminal privado.
- `sshd -t` passou no script base e `PermitRootLogin no` foi aplicado. Desativação de senha SSH no servidor ainda pendente. Nenhum `NOPASSWD` foi criado; nenhuma senha Linux foi gravada em scripts ou logs de execução.

## Serviços e compatibilidade

| Item | Resultado observado |
| --- | --- |
| Atualizações do SO | `apt-get update` e `upgrade --no-remove` concluídos; reinício ainda não realizado |
| Nginx | `1.24.0-2ubuntu7.19`, ativo; ainda com site padrão |
| MySQL | `8.0.46-0ubuntu0.24.04.4`, ativo; 3306 e 33060 em `127.0.0.1` |
| PHP atual | `8.3.6-0ubuntu0.24.04.13`, CLI/FPM instalados; FPM ativo, **incompatível com o lockfile** |
| Composer | `2.10.3`; instalador oficial verificado por SHA-384 |
| Mailpit | `1.32.0`; binário oficial validado pelo digest SHA-256 do asset GitHub; serviço dedicado, ativo |
| Mailpit bindings | SMTP `127.0.0.1:1025`; web `127.0.0.1:8025`; sem relay externo configurado |
| Cron/SSH | Ativos; cron Laravel ainda não instalado |

O script base falhou **depois de instalar e iniciar os serviços**, na consulta `mailpit --version` (linha 82). A forma correta, `mailpit version`, foi executada e validada. A confirmação interativa de `composer --version` como root também foi identificada; a retomada consulta Composer como `server`, sem interação. Não repetir o script base inteiro.

Embora `composer.json` declare PHP `^8.2`, o lockfile contém nove pacotes Symfony 8.1 exigindo `>=8.4.1`. A tentativa de `composer install --no-dev --no-scripts` como `server` foi corretamente recusada antes de instalar o vendor. Não foi usado `composer update` nem `--ignore-platform-reqs`.

A preparação de PHP 8.4 usa o PPA de Ondřej Surý porque os repositórios atuais não oferecem PHP 8.4. A API oficial Launchpad, o fingerprint `B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6`, uma assinatura válida do InRelease por essa chave e o SHA-256 do índice de pacotes foram conferidos. O índice assinado oferece CLI/FPM `8.4.27-1+ubuntu24.04.1+deb.sury.org+1`. **O PPA e esses pacotes ainda não foram instalados**; o script preparado exige PHP >=8.4.27, usa `Signed-By` e não altera o lockfile.

## Testes concluídos

- Node Windows `24.11.1`, npm `11.6.2`: `npm ci` e build Vite `6.4.5` passaram em cópia isolada. Templates Blade de Laravel e Jetstream conferidos com o lockfile e incluídos na compilação; arquivos originais preservados.
- Laravel travado em `12.69.2`, Livewire `3.8.10`. `composer audit --locked --no-dev` local: nenhum advisory ou pacote abandonado.
- Suíte final em cópia separada, sem `.env` privado nem config cache: **349 testes, 2.523 assertions, zero falhas, 4 ignorados**, PHP 8.4.24 / PHPUnit 11.5.56. Os três testes de verificação de e-mail passaram. O teste acadêmico cobre cadastro, bloqueio do cliente não verificado, link assinado, compra, pagamento fictício e entrega.
- Variáveis explícitas exigiram SQLite `:memory:`; guardas originais foram preservados; um INI exclusivo desativou mysqli/pdo_mysql e confirmou `PDO_DRIVERS=sqlite`. Nenhuma suíte conectou a MySQL. Saída textual fora do Git em `isolated-tests-result.txt`. Esses testes locais não atestam a VM.
- DNS remoto e HTTPS ao Composer funcionaram. SSH por chave, bindings locais e integridade da transferência foram conferidos.
- Uma mensagem fictícia foi enviada por SMTP local e encontrada pela API do Mailpit: recebimento confirmado. Testes Laravel de recuperação/verificação via Mailpit ainda pendentes.
- Nenhuma screenshot criada. Roteiro de navegador preparado para desktop 1440×1000 e mobile 390×844, ainda não executado. Usará `visualReviewOutput` e `.artifacts/visual-reviews/`, destino confirmado por `git check-ignore`.

## Retomada e operações pendentes

Quando houver autenticação sudo interativa, executar no PowerShell:

```powershell
& "$env:USERPROFILE\.ssh\shopplus-setup-20261026\resume-deployment.cmd"
```

A retomada instala PHP 8.4, confere serviços e executa o Composer como `server`. Os scripts preparados passaram em `bash -n`. Depois cria schema **novo e vazio** `shopplus_sds`, contas limitadas de aplicação/migração/leitura e segredos exclusivos; comprova a conexão efetiva antes de `migrate --force`; executa os seeders de dados e imagens sem dev dependencies; configura permissões, Nginx/PHP-FPM 8.4, certificado SAN para NAT e `.127`, Mailpit, scheduler `orders:expire` como `www-data` e firewall. O script aguarda uma confirmação técnica de HTTPS testado pelo Windows antes de redirecionar HTTP e desativar senha SSH.

**Nenhuma dessas operações da aplicação foi executada ainda.** `.env`, chaves Laravel/PII, contas MySQL, dados fictícios, TLS, scheduler e regras finais de firewall continuam pendentes. O seeder atual não usa Faker. A avaliação preliminar não encontrou jobs próprios implementando `ShouldQueue`; a necessidade de worker será confirmada após instalar o vendor.

- URL futura por NAT: `https://127.0.0.1:8443`; atualmente indisponível. `http://127.0.0.1:8080` pode servir apenas a página padrão do Nginx, não o ShopPlus.
- Mailpit por túnel, sem exposição à turma: `ssh -L 8025:127.0.0.1:8025 shopplus-vm`, depois `http://127.0.0.1:8025`. Mensagens de recuperação contêm tokens sensíveis.
- Workbench, após criar a conta de leitura: **Standard TCP/IP over SSH**; SSH `127.0.0.1:2222`, usuário `server`, chave da VM; MySQL `127.0.0.1:3306`, conta dedicada. Não abrir 3306 na rede.
- TLS autoassinado futuro cifra a conexão, mas exige confiança explícita nos dispositivos autorizados. Fingerprint ainda não gerado. Nginx preparado para remover HSTS enquanto autoassinado, preservando CSP/CSRF e demais políticas.
- Reiniciar serviços atuais: `sudo systemctl restart nginx php8.3-fpm mysql mailpit cron`; após a migração de PHP, usar `php8.4-fpm`. Validar `sudo nginx -t` e `sudo sshd -t` antes de aplicar configurações.
- Snapshot antes dos alunos: desligar a VM de forma limpa, confirmar `poweroff` e usar `VBoxManage snapshot server take <nome-unico>`. Nenhum snapshot criado; restaurar requer confirmação por reverter dados e configuração.
- Pendem HTTPS/HTTP real da aplicação, navegador, fluxo completo Mailpit, autorização entre clientes/admin, persistência e expiração na VM, inspeção privilegiada final de firewall, Bridge e nova conexão após reinício.

Referências: [Composer oficial](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md), [Mailpit oficial](https://github.com/axllent/mailpit/releases), [PPA e fingerprint no Launchpad](https://launchpad.net/~ondrej/+archive/ubuntu/php), [correções PHP Ubuntu](https://ubuntu.com/security/notices/USN-8948-1), [correções Nginx Ubuntu](https://ubuntu.com/security/notices/USN-8955-1), [releases PHP](https://www.php.net/archive/2026.php).