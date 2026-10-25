# ShopPlus — Etapa 7B: bloqueadores de segurança

> Atualização posterior: a [Etapa7C](../7c/resultado.md) executou concorrência MySQL real em laboratório isolado e ensaio HTTP com inspeção de registros/conta leitora. A VM do IF e a conferência gráfica no Workbench continuam pendentes. Este documento conserva o estado encontrado na Etapa7B.

24/10/2026, America/Sao_Paulo. Correções da Etapa 7 preservadas; nenhum redesign, commit, recovery por binlog ou teste em banco persistente.

**O bloqueador crítico do PHP local foi resolvido:** CLI e servidor usam PHP **8.4.27**, com upload progress desativado. A liberação da **VM do IF** continua pendente de inspeção. Concorrência MySQL não foi aprovada nesta máquina.

## 1. PHP antes e depois

| Superfície | Antes | Depois / evidência |
|---|---|---|
| CLI do projeto | 8.4.22 | **8.4.27**, [inventário](evidence/php-cli.json) |
| Herd FastCGI | 8.4.22, `php-cgi.exe` | **8.4.27**, SAPI `cgi-fcgi`, [consulta ao worker real](evidence/fastcgi-runtime.json) |
| HTTP do ShopPlus em loopback | Servidor anterior observado com PHP8.4.22 | Reiniciado em `127.0.0.1:8000`; `/up` retorna200, `X-Powered-By: PHP/8.4.27`, nosniff; [headers](evidence/http-headers.txt) |
| VM do IF | Não acessada | Não verificada |

O Herd foi iniciado, mas a versão da CLI instalada não oferece `php:update`, mesmo com Desktop aberto. A retomada foi feita **pelo terminal**, sem nova automação de telas. Foi usado o mecanismo equivalente de distribuição oficial ZIP do PHP para Windows, mantendo a mesma versão minor, arquitetura, compilador e NTS compatíveis com FastCGI.

Pacote: `php-8.4.27-nts-Win32-vs17-x64.zip`, obtido de `downloads.php.net/~windows/releases/`. O SHA-256 publicado e o calculado coincidiram:

`93bd8a815a52156606fb906d0573437908c61f96e2cc52c03a51e36a41bf5236`

[Origem e verificação](evidence/php-download.json). Fontes: [downloads oficiais Windows](https://www.php.net/downloads.php?os=windows&version=8.4), [manifesto oficial](https://downloads.php.net/~windows/releases/releases.json). Não foi instalado PHP de fonte arbitrária ou pacote major novo.

Antes da troca, foi feito backup do checkout sujo e da instalação completa em `%TEMP%\shopplus-before-7b-20261024-180329`. A instalação anterior também permanece em:

`C:\Users\Hyan Ferreira\.config\herd\bin\php84-before-sds-20261024`

O candidato foi extraído separadamente e testado com o INI preservado. Extensões Herd/Redis/MongoDB também carregaram sem avisos. Os workers antigos foram parados; o diretório ativo `php84` recebeu o pacote oficial completo com INI anterior; PHP Herd e `artisan serve` foram reiniciados. O terminal continua usando o mesmo wrapper `php.bat`, agora apontando ao executável atualizado no mesmo caminho.

INI efetivo CLI e FastCGI: `C:\Users\Hyan Ferreira\.config\herd\bin\php84\php.ini`; nenhum INI adicional escaneado. Verificados OpenSSL, mbstring, fileinfo, PDO MySQL/SQLite, GD e Argon2id, entre as extensões preservadas. `composer check-platform-reqs`: aprovado, [saída](evidence/platform.txt).

### Situação das CVEs

| Vulnerabilidade | Estado local |
|---|---|
| CVE-2026-109618 | Mínimo corrigido atendido pelo PHP8.4.27 no CLI e worker HTTP |
| CVE-2026-109619 | Mínimo corrigido atendido pelo PHP8.4.27 no CLI e worker HTTP |
| CVE-2026-115560 | **Mitigação**, não alegação de patch definitivo: `session.upload_progress.enabled=0` confirmado no CLI e FastCGI |

Referência: [avisos oficiais PHP de 21 e 23/10/2026](https://www.php.net/archive/2026.php). O serializer efetivo é `php`; a Etapa7 não encontrou troca de serializer no código da aplicação. A recomendação de manter upload progress desativado foi preservada.

A configuração FastCGI foi consultada diretamente no socket loopback do worker existente, executando uma sonda temporária **fora de public/**, sem bootstrap Laravel, acesso ao banco ou `phpinfo()`. O arquivo temporário foi removido em `finally`. Não ficou endpoint diagnóstico público. A requisição HTTP real `/up` confirma separadamente o ShopPlus rodando na versão corrigida; não foi inferida atualização HTTP apenas a partir do CLI.

Instalações alternativas php82/php83/php85 e o rollback antigo não foram atualizados nem selecionados para servir o ShopPlus. Continuam abaixo dos mínimos conforme inventário da Etapa7: atualizar antes de qualquer uso futuro. Não são evidência de que outros projetos da máquina estejam corrigidos.

**Reversão:** parar o PHP pelo Herd e encerrar o `artisan serve` deste projeto; preservar o diretório novo com outro nome e restaurar o diretório `php84-before-sds-20261024` como `php84`; reiniciar e verificar. Isso reintroduziria o PHP8.4.22 e só serve para diagnóstico isolado, nunca para liberar a aplicação à turma. Não executar rollback automaticamente nem sobrescrever backups. O backup externo conserva também o INI e checkout anteriores.

## 2. VM e MySQL

O usuário informou que a VM só está no PC do IF. Nenhuma conexão, SO, versão, webroot, permissão, TLS, usuário Workbench ou exposição de rede da VM foi simulada.

O contexto Docker `desktop-linux` existe, mas `docker info` falhou: o daemon/pipe `dockerDesktopLinuxEngine` não está disponível. Não foi provisionado nem identificado outro MySQL descartável autorizado. O MySQL de desenvolvimento não foi usado como substituto.

**Concorrência real MySQL: não executada, não aprovada.** A suíte preserva exclusivamente SQLite `:memory:`. Disputa simultânea pela última unidade, reservas concorrentes, pagamento simultâneo/idempotência e estado final InnoDB continuam no [checklist operacional do IF](checklist-if.md). Testes sequenciais e rollback em SQLite não substituem esses resultados.

## 3. Logs: origem, prevenção e rotação

As duas ocorrências antigas são frames do mesmo `RuntimeException` em **23/10/2026 20:57:49**, nos caminhos `DatabaseSeeder.php:130` e `:48`. A mensagem corresponde à falta da chave de índice cego na execução histórica do seeder. Os valores encontrados correspondem ao literal fictício do `DatabaseSeeder` e das fixtures de testes. Não foi necessário consultar usuários do MySQL para estabelecer essa correspondência.

Nenhum CPF foi copiado ao relatório. A conclusão é sobre **essas duas ocorrências**, não sobre todo dado existente no log. [Metadados sem valores](evidence/log-investigation.json).

Causa do vazamento: `zend.exception_ignore_args` estava Off, permitindo que argumentos de funções aparecessem em traces. Foi configurado **On** no INI ativo e reiniciados os serviços. A prova sintética no FastCGI confirma que o argumento marcador não aparece no trace nem no array de frames. Isso evita a causa específica observada. Não remove dados já escritos, nem garante sanitização de mensagens/contextos que um código venha a registrar explicitamente.

A revisão focada de `app/` e `routes/` não encontrou logger direto de CPF/telefone/formulário; `SecurityAudit` continua persistindo metadados cifrados. Os testes usam canal de log null e serviços isolados. O `.env` de desenvolvimento e suas chaves não foram alterados. SMTP privado, APP_DEBUG=false e logs restritos continuam necessários na VM; mailer `log` pode guardar tokens de recuperação e não deve ser usado em logs compartilhados.

O log original foi **preservado byte a byte** durante esta etapa, SHA-256:

`4FF0DDD53A1418C47CE89DBE67E205D51501B6024A7DAD00B9EBD6CC3583C16C`

Rotação recomendada antes de levar a instância ao IF, ainda não executada:

1. Parar somente os processos HTTP/jobs deste projeto para impedir gravação durante a rotação.
2. Criar pasta privada fora do repositório e webroot, por exemplo `%USERPROFILE%\.shopplus-private-logs`, com ACL limitada ao usuário atual e SYSTEM. Não adicionar o log ao Git, relatório ou pasta pública.
3. Mover `storage/logs/laravel.log` para essa pasta com nome datado, conferir SHA-256 e conservar só a cópia necessária à investigação. Manter as evidências antigas de outras etapas intactas.
4. Reiniciar a aplicação para criar um log novo. Na VM, usar `LOG_STACK=daily`, `LOG_DAILY_DAYS=7`, nível warning e permissões de leitura restritas. Não apagar a cópia histórica sem revisão da retenção.
5. Reproduzir uma falha controlada com marcadores sintéticos e confirmar ausência dos valores; nunca inserir CPF real para fazer essa demonstração.

Fonte da diretiva: [manual PHP](https://www.php.net/manual/en/ini.core.php#ini.zend.exception-ignore-args). Nenhum dado histórico foi apagado ou publicado.

## 4. Os quatro avisos NPM

Os quatro avisos representam uma cadeia do mesmo advisory de parser CSS, não quatro falhas independentes no PHP/Laravel. `npm audit --omit=dev` retorna **zero**, [saída](evidence/npm-production-audit.json). O build entrega CSS/JS estáticos; não há parsing de seletores enviados por clientes no request path do ShopPlus.

| Entrada no audit | Uso e situação final |
|---|---|
| `postcss-selector-parser` | Parser de seletores no build. Antes havia6.0.10 no Typography e6.1.5 nas outras cadeias; agora todas usam **6.1.5** |
| `@tailwindcss/typography` | Plugin de build que fixava parser6.0.10. Override específico para **6.1.5**, mesma major, aplicado em `package.json` e lockfile |
| `postcss-nested` | Transformação CSS de build; usa6.1.5 deduplicado, sem upgrade major do plugin |
| `tailwindcss` | Build Tailwind3.4.19 preservado, dependência de parser6.1.5 deduplicada; sem migração para Tailwind4 |

O [aviso do próprio mantenedor](https://github.com/postcss/postcss-selector-parser/security/advisories/GHSA-rj75-hqrm-r3gf) lista **6.1.5 e7.1.6 como versões corrigidas**; o pacote6.1.5 instalado contém a mudança para `Set` nas passagens envolvidas. A versão6.0.10 foi substituída por esse backport compatível, sem forçar7.x.

**Divergência registrada:** o feed retornado por `npm audit` nesta sessão ainda classifica `<7.1.6` como vulnerável, abrangendo o backport6.1.5. Por isso a saída bruta continua com **4 moderados, zero altos/críticos**, [audit final](evidence/npm-audit.json). Os avisos não foram silenciados, excluídos ou apresentados como audit limpo. A exceção se apoia no aviso primário, versão/código instalado e ausência de parsing de CSS não confiável na VM; revalidar o feed antes de futuras atualizações.

Validação de regressão: builds com a árvore anterior e com o override geraram **CSS idêntico**, SHA-256 `5F9BBC8D26970B37AA78556CF2CCE86BB561DAFE4E3F189D6B0038A79FE2DB7C`; [comparação](evidence/css-comparison.json). O JS gerado manteve `app-DMsN-rLE.js`. Nenhum CSS de origem foi editado. `npm audit fix --force` não foi usado. Não expor Vite na rede e construir apenas fontes confiáveis continua sendo requisito operacional.

## 5. Validação final no runtime atualizado

| Verificação | Resultado |
|---|---|
| Suíte completa | **269 testes:262 aprovados,7 ignorados,0 falhas;1.482 assertions**, PHP8.4.27 |
| Cenário acadêmico | `AcademicFlowTest` aprovado: cadastro/login, perfil/endereço, catálogo, carrinho, checkout, pagamento, pedido e entrega |
| Persistência e PII | Assertions de hash de senha, cifra/índice cego, valor calculado pelo servidor, ausência de CVV e isolamento entre clientes aprovadas |
| Workbench | Consultas e conta de leitura preparadas; inspeção real na VM **pendente** |
| Gates de isolamento | Três PASS; sem preparação/acesso PDO persistente nos probes |
| Build | Vite6.4.5 aprovado,58 módulos; [saída](evidence/build.txt) |
| Composer audit | Zero avisos/abandonadas; [saída](evidence/composer-audit.json) |
| Requisitos de plataforma | Aprovados, incluindo PHP8.4.27; [saída](evidence/platform.txt) |

[Saída completa da suíte](evidence/phpunit.txt), [JUnit](evidence/phpunit.xml), [isolamento](evidence/isolation.json), [bootstrap](evidence/bootstrap-isolation.json), [Dotenv/cache](evidence/dotenv-isolation.json). O cenário funcional usa SQLite descartável, não o banco de demonstração. Os sete skips permanecem os mesmos da Etapa7, sem exclusão de novas regressões.

## 6. O que ainda impede liberação na rede da turma

1. **VM do IF não inspecionada:** confirmar PHP/SAPI corrigido, INIs, webroot, debug, TLS/cookies, firewall, permissões e contas antes de expor.
2. **Concorrência MySQL sem prova:** homologar em instância descartável separada, sem relaxar as proteções da suíte ou usar o banco original.
3. Aplicar configuração apropriada à demonstração. O `.env` local continua de desenvolvimento; não levá-lo à rede como configuração pronta.
4. Preparar acesso Workbench de leitura e executar a demonstração na instância real. Nenhuma conta privilegiada de aplicação deve ser compartilhada com a turma.
5. Tratar o log histórico privadamente e confirmar retenção/SMTP. A causa dos argumentos em traces foi corrigida localmente, mas o arquivo antigo permanece deliberadamente preservado.

O PHP local deixou de ser bloqueador conhecido das duas CVEs citadas. Isso **não libera automaticamente uma VM que não foi examinada**, nem equivale a segurança absoluta. O checklist seguinte é a etapa operacional restante.
