# Etapa 7C — laboratório e ensaio final

Usar as skills de planejamento, investigação e regressão com o escopo definido pelo pedido: preparação operacional e comprovação, sem redesign/refatoração comercial. VM do IF inacessível nesta sessão; não inferir estado dela a partir do Windows.

1. Preservar checkout: backup externo feito em `%TEMP%/shopplus-before-7c-20261024-182452`.
2. Fortalecer teste do seeder quanto a estoque/reservas após repetição. Corrigir somente se a regressão confirmar perda de integridade.
3. Docker Desktop iniciado via CLI; provisionar imagem oficial MySQL8.4 em container novo, rede interna própria, armazenamento temporário sem volumes do host, porta127.0.0.1:33077 e credenciais sintéticas exclusivas. Nunca usar3306 ou shopplus_db.
4. Criar executor separado da suíte principal: clone temporário sem .env original, configuração privada, identity check por server_uuid, restrição de todas as conexões ao laboratório. Main TestCase/bootstrap permanecem SQLite-only.
5. Executar actions reais em dois processos/conexões sincronizados; comprovar espera simultânea por locks e estado persistido para última unidade, checkout/pagamento repetidos, cancelamento/liberação e rollback tardio.
6. Subir cópia HTTP isolada em loopback, sem e-mail externo. Ensaiar cadastro/login/dados/compra/pagamento/entrega e consultas equivalentes às do Workbench; distinguir requisições backend de inspeção visual/GUI não executada.
7. Registrar resultados e preparar roteiro de VM, inicialização com banco novo, conta Workbench somente leitura e checklist final. Parar/remover apenas recursos identificados como deste laboratório após coletar evidências; preservar banco original e todas as mudanças locais.

Não há autorização para liberar uma VM ainda não inspecionada. Nenhum teste automatizado da suíte principal apontará ao MySQL, nem haverá recuperação por binlogs.

## Resultados e decisões

- Seeder RED reproduzido: reseed mudava on_hand17→13 e reserved1→0. Correção mínima para firstOrCreate; novo teste preserva saldos e movimentos. GREEN na suíte SQLite e no seeder MySQL repetido.
- Docker MySQL8.4.13 isolado em rede internal/tmpfs, sem portas publicadas ou bind mounts. Como porta publicada não ficou acessível nessa rede no Docker Desktop, o cliente usa relay loopback via docker exec, sem adicionar rede externa ao banco.
- Quatro pares de conexões distintas foram observados simultaneamente em LOCK WAIT; resultados persistidos de última unidade, checkout/pay/cancel replay e rollback PASS. Evidências em docs/security/7c/evidence/mysql-concurrency.json.
- Ensaio HTTP real com sessões/CSRF e sem SMTP PASS; inspeção SQL e conta leitora PASS. GUI Workbench não automatizada, VM inacessível. Esses limites foram mantidos no relatório.
- Revisão independente encontrou public/hot copiado no clone e paths de cache/storage herdáveis. Clone agora exclui hot; todos esses paths ficam no laboratório, com prova de cache hostil não executado. Assets compilados responderam200.
- Main guards inalterados por hash, gates PASS; suíte269testes/262aprovados/7skips/0falhas/1484assertions. Sem commits ou alterações visuais.
- O laboratório loopback foi mantido para inspeção manual do usuário, com cleanup validando identidade/labels. Não é ambiente de produção nem autorização de exposição; tmpfs é descartável.
- Pacote com fontes atuais não commitadas, assets e roteiro gerado por allowlist fora do Git; exclui segredos, dados runtime e public/hot. Instruções operacionais em docs/security/7c/procedimento-if.md.
