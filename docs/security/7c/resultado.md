# ShopPlus — Etapa 7C: ensaio e preparação SDS

Data:24/10/2026, America/Sao_Paulo. Apresentação:26/10/2026. **Ensaio local HTTP/MySQL aprovado; a VM do IF continua sem inspeção.** Não declarar a VM pronta para exposição à turma a partir destes resultados.

## 1. Estado real da VM e bloqueadores

O usuário informou que a VM está somente no PC do IF. Não havia endereço/alias SSH ou sessão dela disponível; não se tentou adivinhar hosts nem acessar máquinas de colegas. **SO, PHP HTTP, servidor, banco, permissões, TLS e portas da VM permanecem não verificados.** O PHP8.4.27 confirmado aqui é o Windows local, não o PHP da faculdade.

O [procedimento do IF](procedimento-if.md) contém os comandos de inventário, inicialização em schema novo, contas separadas e checklist final. Bloqueadores restantes: executar esse preflight na VM, confirmar PHP/SAPI corrigido e configuração privada, preparar o acesso de leitura ao Workbench e conferir o ensaio nesse ambiente real. Os quatro avisos NPM/backport documentados na Etapa7B não foram reabertos ou ocultados nesta etapa.

## 2. Laboratório MySQL realmente isolado

Foi possível iniciar o Docker Desktop **pela CLI**, sem controle de tela. Provisionado MySQL **8.4.13**, imagem oficial `mysql:8.4`, digest/ID:

`sha256:76f0358884cb92d9a086d24e5671c38048d3fef40f2588703d73eb55392d6b69`

- Container exclusivo `shopplus-sds7c-59ac986b4562`, rede Docker **internal**, sem portas publicadas, sem bind mounts do host, datadir próprio em **tmpfs**.
- MySQL não tem acesso ao datadir ou às credenciais do banco original. O cliente de teste usa um relay em **127.0.0.1:33077**, que encaminha bytes por `docker exec` exclusivamente a esse container; não é exposição do banco à rede da turma.
- Schema novo `shopplus_sds7c_59ac986b4562`, credenciais sintéticas próprias, chaves de aplicação/índice cego próprias e UUID do servidor conferido antes de migrations/actions.
- Cópia privada da aplicação, sem `.env` original. Todas as conexões MySQL da aplicação do laboratório passam pelo connector que rejeita host/porta/schema/credenciais diferentes e confere `@@server_uuid`.
- Paths de cache, storage e views presos ao clone. Prova com cache sintético hostil não executou o marcador. Porta3306, `shopplus_db` e host alternativo foram rejeitados **antes de abrir PDO**.
- A suíte principal permanece exclusivamente SQLite. Hashes de `tests/Support/TestEnvironment.php`, `tests/TestCase.php`, `tests/bootstrap.php`, `phpunit.xml` e `artisan` são idênticos aos do início da Etapa7C.

[Provisionamento](evidence/lab-provision.json), [isolamento Docker](evidence/docker-isolation.json), [guards do laboratório](evidence/lab-guards.json), [guards principais inalterados](evidence/main-guards-unchanged.json). A primeira tentativa de publicação de porta em rede interna não ficou acessível no Docker Desktop; aquele container descartável foi removido antes de qualquer migration e substituído pelo canal local descrito acima.

Não houve migrate:fresh/db:wipe, teste no MySQL de desenvolvimento, parada deliberada do serviço MySQL original ou recuperação por binlogs. Nenhuma dependência PHP/JS nova foi instalada. A imagem MySQL foi necessária ao teste solicitado.

## 3. Concorrência real e dados persistidos

Foram usadas as **actions reais** do projeto em **dois processos PHP separados**, cada um com uma conexão MySQL distinta. O coordenador segurava um registro relevante; só o liberava depois de observar **ambas as transações simultaneamente em LOCK WAIT** em `information_schema.innodb_trx`. Os IDs das conexões, tempos e resultados estão registrados.

| Caso | Comprovação persistida |
|---|---|
| Dois compradores disputando a última unidade | Uma compra aceita, outra rejeitada;1 pedido/item vencedor; `on_hand=1`, `reserved=1` |
| Mesmo checkout simultâneo | Ambos recebem o mesmo pedido;1 pedido e1 reserva; estoque3/reserva1 |
| Mesmo pagamento simultâneo | Ambos recebem o mesmo pagamento;1 pagamento persistido; estoque2/reserva0, baixa única |
| Cancelamento/liberação simultâneo e repetido | Ambos concluem sem duplicação;1 movimento de liberação; estoque3/reserva0 |
| Falha tardia ao criar shipment | Pagamento/baixa/histórico revertidos;0 pagamentos para o pedido; estoque3/reserva1 e status pendente preservados |
| Invariantes gerais | Zero linhas com saldo negativo, reserva negativa ou reserva maior que estoque |

[Evidência completa](evidence/mysql-concurrency.json). A falha de rollback é injetada somente pelo executor do laboratório, sem mudar o comportamento comercial da aplicação. Esses casos passaram no MySQL8.4.13 local; isso não equivale a teste de carga, todos os possíveis interleavings ou homologação da configuração InnoDB ainda desconhecida da VM.

## 4. Seeder: falha reproduzida e correção mínima

O seeder criava os registros de demonstração, mas uma repetição usava `InventoryItem::updateOrCreate` para **zerar reservas e reescrever o saldo**, apesar dos pedidos/movimentos já existentes. O teste anterior comparava contagens e não capturava a inconsistência.

Reprodução RED: no primeiro produto, a segunda execução mudava `on_hand17 →13` e `reserved1 →0`; outros saldos também perdiam o abastecimento registrado. Foi alterada **uma chamada** em `database/seeders/DatabaseSeeder.php` para `firstOrCreate`, inicializando só estoque ainda inexistente. Não houve mudança nas regras comerciais.

`DemoSeederTest` agora compara saldos/reservas e quantidade de movimentos antes/depois da repetição. GREEN no SQLite protegido; também aprovado ao executar o seeder duas vezes no schema MySQL novo do laboratório. Em base vazia: **3 usuários,16 produtos,1 fornecedor,2 pedidos (pendente/pago),1 pagamento,1 entrega,6 movimentos**.

O seeder não é ferramenta para recuperar histórico perdido. Para a apresentação, usar schema novo e senha demo própria em configuração privada. Nenhuma senha demo real foi incluída neste relatório.

## 5. Ensaio HTTP real sem SMTP externo

Endpoint do clone: **http://127.0.0.1:8001**. Executadas requisições HTTP com cookie jar, sessões e **tokens CSRF reais**, sem o bypass do PHPUnit e sem automação do desktop. Sequência aprovada:

Home → cadastro novo → logout/login → CPF/telefone/endereço fictícios → catálogo/detalhe →2 unidades no carrinho → checkout economy → pagamento fictício aprovado → pedido pago → informações de entrega.

A autenticação não foi desativada. A verificação de e-mail **já estava desabilitada antes desta etapa**, conforme configuração Fortify e modelo existentes; nenhuma mudança foi feita nela. O laboratório usa mailer array, sem entrega externa. Assim ficou comprovado que o cliente novo compra sem SMTP. Recuperação de senha por e-mail não foi ensaiada sem um transporte real.

O clone exclui `public/hot`: usa os assets compilados de `public/build`, com quatro referências a assets respondendo200, sem Vite5173. Isso foi corrigido no executor após revisão independente; o `public/hot` original do ambiente de desenvolvimento foi preservado. Não houve alteração de layout ou inspeção visual do navegador.

Também passaram pelo backend HTTP: cliente em admin403, pedido alheio404, ID de endereço alheio rejeitado, texto>1.000 caracteres rejeitado, XSS armazenado escapado, busca SQL maliciosa tratada como literal, pagamento repetido idempotente e quantidade maior que estoque rejeitada. A tentativa de excluir endereço alheio não o removeu.

[Resultado HTTP](evidence/http-rehearsal.json). `/up`200; `.env`, `.git/config`, composer404 e log403 no clone: [paths privados](evidence/http-private-paths.json). Essas verificações são do clone local, não da VM.

## 6. Registros conferidos e Workbench

Pedido do ensaio: **id7 / ORD-6GMSLXHTU5DOLJSF**, cliente sintético id9. Consulta persistida confirmou:

- Senha em **Argon2id**; CPF cifrado com índice HMAC; telefone e endereço cifrados.
-2 unidades, subtotal **849.800 centavos**, frete **1.690**, total **851.490**; soma dos itens+frete e valor do pagamento iguais no servidor.
- **Um pagamento autorizado**, mesmo após repetição HTTP; nenhuma coluna CVV/PAN completo e nenhum PAN completo no registro.
- Shipment `awaiting_processing` e evento inicial persistidos. Estoque do produto1: físico15, reservado1; a reserva restante pertence ao pedido demo pendente anterior, não é duplicação da compra paga.
- Conta **`sds_reader`** conseguiu ler registros e possui somente SELECT/SHOW VIEW neste schema; sem privilégios de escrita/administração.

[Inspeção persistida](evidence/persisted-inspection.json). Dados pessoais de nome/e-mail/data de nascimento mantêm o tratamento do schema existente; não se afirma que toda coluna pessoal é cifrada.

**A interface gráfica do MySQL Workbench não foi aberta/controlada.** A conexão e as consultas equivalentes foram confirmadas pelo protocolo MySQL usando a conta leitora. Para a conferência manual local:

1. Workbench → conexão TCP padrão → host127.0.0.1, porta33077, usuário`sds_reader`, schema`shopplus_sds7c_59ac986b4562`.
2. Consultar a senha sintética **localmente** no campo `reader_password` do arquivo privado `C:\Users\Hyan Ferreira\.shopplus-sds7c-59ac986b4562\descriptor.json`. Não anexar o arquivo a relatório/chat ou copiar suas chaves para a VM.
3. Abrir [workbench.sql](workbench.sql), confirmar schema/conta e executar as consultas de leitura com `@pedido_demo=7`. Na VM, usar o ID criado no próprio ensaio e a conta leitora daquela VM.

## 7. Testes finais e pacote para transferência

Suíte principal: **269 testes,262 aprovados,7 ignorados,zero falhas,1.484 assertions**, PHP8.4.27. [Saída](evidence/phpunit.txt), [JUnit](evidence/phpunit.xml). Os sete skips são os mesmos das etapas anteriores. Gates de isolamento principais: [conexões](evidence/main-isolation.json), [bootstrap](evidence/main-bootstrap.json), [Dotenv](evidence/main-dotenv.json): PASS.

O build aprovado na Etapa7B foi preservado; nenhum fonte CSS/JS mudou nesta etapa. O ensaio requisitou os assets compilados pelo HTTP e confirmou200, sem depender do servidor dev. PHP/Python dos executores passaram na validação de sintaxe.

Há um script `scripts/security/mysql-lab/package.py` para empacotar o estado **atual não commitado**, por allowlist, com assets compilados e diretórios runtime vazios. Ele exclui credenciais, `.env`, `.env.testing`, `.git`, vendor/node_modules, logs, dumps, storage privado e `public/hot`. O arquivo ZIP e seu SHA-256 estão descritos em `evidence/package.json`; instalar dependências Composer de produção no destino usando o lockfile. Não copiar a instalação PHP Windows para Linux.

## 8. Reproduzir ou encerrar o laboratório

São executores separados da suíte principal, não uma exceção nos seus guards. Exigem Docker e PHP do host com extensões MySQL e dependências Composer já instaladas. O provisionamento sempre cria identidade/schema novos e falha se a porta local33077 estiver ocupada.

```powershell
docker pull mysql:8.4
python scripts/security/mysql-lab/provision.py > "$env:TEMP\shopplus-lab.json"
$lab = Get-Content "$env:TEMP\shopplus-lab.json" -Raw | ConvertFrom-Json
php scripts/security/mysql-lab/guard-check.php $lab.descriptor_file
php scripts/security/mysql-lab/run.php init $lab.descriptor_file
php scripts/security/mysql-lab/run.php concurrency $lab.descriptor_file
python scripts/security/mysql-lab/http-rehearsal.py $lab.descriptor_file (Get-Command php.exe).Source
php scripts/security/mysql-lab/run.php inspect $lab.descriptor_file
```

Na instalação Herd atual, `php` pode ser wrapper `.bat`; para o executor Python usar o caminho real do `php.exe` do inventário ou `herd which-php`. Não executar `init` em schema já inicializado: o executor exige vazio e não limpa bases existentes.

O laboratório atual foi mantido **somente em loopback** para inspeção manual; suas credenciais são exclusivas e sintéticas. Após conferir, encerrar apenas os recursos identificados dele:

```powershell
python scripts/security/mysql-lab/cleanup.py 'C:\Users\Hyan Ferreira\.shopplus-sds7c-59ac986b4562\descriptor.json'
```

O cleanup valida owner/ID/labels antes de remover container/rede e parar os processos do laboratório. Não toca no MySQL original nem apaga recursivamente arquivos; a pasta privada fica preservada. **Tmpfs perde o banco ao remover/parar o container**; coletar as evidências de leitura antes. Não tratar esse banco como armazenamento permanente da apresentação.

## 9. Pendências até26/10

1. Executar o [procedimento de inicialização](procedimento-if.md) na VM real, com PHP corrigido e configuração validada; ainda não disponível aqui.
2. Abrir o Workbench e conferir visualmente a conta leitora/consultas na instância da apresentação.
3. Ensaiar uma compra nova no endereço final da VM e registrar seu ID/total/estoque/entrega, scheduler e isolamento de rede.
4. Garantir transporte das dependências confiáveis caso a faculdade não tenha internet; preservar chaves e não transportar configurações pessoais do Windows.
5. Só depois disso liberar o site à turma, mantendo banco e Vite privados. O laboratório local aprovado reduz as pendências técnicas, mas **não substitui o aceite do ambiente real**.

Nenhum commit foi criado. Backup das alterações locais no início da etapa: `%TEMP%\shopplus-before-7c-20261024-182452`. Alterações funcionais limitadas à correção reproduzida do seeder; os fluxos comerciais e visuais aprovados foram preservados.
