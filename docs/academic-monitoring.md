# Monitoramento e geração acadêmica

Implementação local; implantação desta alteração na VM ainda pendente. Nenhum `.env`, banco persistente, dado anterior, commit ou push foi alterado nesta etapa.

## Acesso e navegação

Após implantar, entre com qualquer conta ativa e abra `/monitoramento`. As abas Usuários, Produtos e Transações são de leitura. `/usuarios` continua disponível pelo menu **Ver usuários**, com acesso ao monitoramento. Os dois divisores e logout POST/CSRF foram preservados.

Os três cards contam todos os usuários, usuários automáticos e cadastros dos últimos cinco minutos. As listas e detalhes mostram exclusivamente registros marcados como sintéticos. Contas existentes recebem `false` por padrão; seeders não são reexecutados ou reclassificados.

Clique no cliente para consultar seus pedidos, no número público para abrir o resumo sanitizado e no produto para consultar suas compras. Um cliente pode ter vários pedidos. O resumo mostra o estado real do pedido, pagamentos simulados e movimentos de estoque relacionados ao pedido e aos seus pagamentos. Nenhum link concede acesso às rotas privadas de outros clientes. Filtros: identificador do cliente fictício, estado do pedido e período em dias de Brasília. Paginação: 20 registros; horários `America/Sao_Paulo`.

## Operação

Um ciclo manual, inclusive com o agendamento pausado:

```sh
php artisan academic:cycle --once
```

Há no máximo um ciclo por janela UTC de cinco minutos, compartilhada entre execução manual e Scheduler. Repetir o comando na mesma janela retorna o ciclo já concluído, sem novos registros. Não existe recuperação automática de janelas perdidas.

Cada ciclo adiciona um cliente com nome e sobrenome Faker `pt_BR`, e-mail único de teste e senha aleatória forte armazenada somente como hash; e um produto com categoria, ilustração existente do repositório, SKU único, preço e dez unidades iniciais. Senhas não são exibidas. Seleciona aleatoriamente um cliente sintético ativo e um produto sintético disponível, permitindo novas compras pela mesma conta em ciclos posteriores. Contas humanas e produtos manuais ficam fora da seleção. Carrinhos preenchidos separadamente não são comprados pela automação.

As Actions existentes criam endereço fictício, carrinho, pedido, reserva e pagamento; não se atribuem estados artificiais diretamente. O resultado sorteado é registrado em `academic_cycles.outcome`, junto dos IDs do usuário criado, comprador, produto criado e pedido efetivamente comprado. O produto comprado pode ser de um ciclo anterior. Se uma condição de negócio impedir a compra, o ciclo conserva o novo usuário e produto, registra `purchase_status=skipped` e desfaz a tentativa de compra. Erros inesperados revertem a transação inteira, permitindo nova tentativa. A ilustração usa caminho determinístico para não multiplicar arquivos em uma repetição.

Para habilitar no ambiente acadêmico, definir no `.env` **da VM** e recarregar a configuração:

```dotenv
ACADEMIC_AUTOMATION_ENABLED=true
ACADEMIC_APPROVED_PERCENT=70
ACADEMIC_DECLINED_PERCENT=20
ACADEMIC_PENDING_PERCENT=10
```

```sh
php artisan config:cache
php artisan schedule:list
```

Para pausar, definir `ACADEMIC_AUTOMATION_ENABLED=false` e executar `php artisan config:cache` novamente. Isso preserva dados e não interrompe a expiração dos pedidos.

Os pesos devem ser inteiros entre zero e cem e somar cem. São probabilidades por sorteio, sem garantia de proporção exata em uma amostra pequena:

| Resultado persistido | Probabilidade inicial | Fluxo real |
| --- | --- | --- |
| `approved` | 70% | Pagamento autorizado, pedido pago, baixa física e entrega simulada |
| `declined` | 20% | Cartão de recusa, pagamento falho, pedido cancelado e reserva liberada |
| `pending` | 10% | Pedido pendente, sem pagamento, estoque reservado; expiração normal após o prazo |

O sorteio original permanece registrado mesmo se um pedido pendente expirar depois. A interface consulta os estados atuais e não sorteia resultados. Idempotência combina chave única da janela, bloqueio transacional e `withoutOverlapping()`.

O cron existente continua executando `schedule:run` a cada minuto. Dentro dele há **um** `academic:cycle` com `everyFiveMinutes()` e o `orders:expire` original com `everyMinute()`. Não adicionar outro cron nem outro `schedule:work` para a mesma aplicação.

## Pagamento fictício

Somente Visa (13, 16 ou 19 dígitos) e Mastercard (16 dígitos; prefixos 51–55 ou 2221–2720), com Luhn válido, CVV inventado de três dígitos e validade não expirada, até quinze anos à frente. Aceitam-se dígitos ASCII e espaços; letras, outros separadores, sequências repetidas e datas inválidas são recusados.

| Número fictício | Resultado |
| --- | --- |
| `4111 1111 1111 1111` | Aprovação simulada |
| `5555 5555 5555 4444` | Aprovação simulada |
| `5437 1426 1584 3937` | Aprovação simulada |
| `4000 0000 0000 0002` | Recusa simulada e liberação da reserva |
| `1111 1111 1111 1111` | Validação rejeitada |

Não utilizar cartões reais. Não existe integração bancária nem cobrança; Luhn não comprova existência ou crédito. PAN completo e CVV não são persistidos. Os campos sensíveis Livewire são limpos também nas falhas de validação, propriedade e limite de tentativas. `ProcessPayment` conserva a implementação existente.

## Arquivos principais

- Geração: `app/Actions/Academic/`, `app/Domain/Academic/`, `app/Console/Commands/GenerateAcademicCycle.php`, `app/Models/AcademicCycle.php`, `config/academic-automation.php`, `routes/console.php`.
- Metadados: migration `2026_10_26_235000_add_academic_automation_tracking.php`, casts em `User`, `Product` e `Order`; exemplos de ambiente. Marcadores protegidos de atribuição em massa.
- Monitoramento: `AcademicMonitoringController`, `UserDirectoryController`, `routes/web.php`, views `monitoring/` e `users/`, partial de navegação, ícone e CSS de conta/header.
- Pagamento: `FictitiousCardValidator`, `PaymentForm` e sua view; testes de domínio, interface e compra completa.
- Dependências: Faker movido para execução em `composer.json` e `composer.lock`, mantendo **todas** as versões travadas. Manifest e CSS compilado atualizados.
- Testes novos: `AcademicCycleTest`, `AcademicRandomizedCycleTest`, `AcademicMonitoringTest`, `UsersDirectoryTest`, `TransactionOutcomeSelectorTest`; testes de header e pagamento ampliados.

## Validação e limites

Suíte integrada: **435 testes, 3.545 asserções, sete ignorados, sem falhas**, em cópia externa com SQLite `:memory:`, guardas do projeto e driver PDO MySQL indisponível. Casos cobrem relações reais, múltiplos pedidos do mesmo cliente, fronteiras de probabilidades, três transições, expiração, rollback tardio, repetição de ciclo, isolamento de dados humanos, filtros, paginação, navegação e ausência de PAN/CVV persistidos. Os sete testes ignorados são cenários já existentes que exigem condições indisponíveis nessa execução.

Build Vite, validação estrita do Composer e instalação de produção simulada (`--dry-run --no-dev --no-scripts`) passaram. Revisão estática independente sem problemas críticos ou importantes. Validação visual usa HTML renderizado com dados SQLite em memória e assets reais, em 1440×1000 e 390×844. Evidências temporárias ficam exclusivamente em `.artifacts/visual-reviews/`, ignoradas pelo Git; essa revisão não equivale a um teste HTTP ponta a ponta contra a VM. Disputa de locks/concorrência MySQL real não foi executada.

## Implantação pendente na VM

1. Fazer backup do banco e preparar janela de manutenção; copiar somente os fontes alterados, migration, lockfile e build. Preservar `.env`, chaves, uploads e dados. Não copiar arquivos de teste ou evidências.
2. Instalar pelo lockfile com `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`, em PHP 8.4 compatível com o lock atual. Faker precisa estar instalado em produção.
3. Executar `php artisan migrate --force` com a identidade de migração autorizada; a conta de execução da aplicação possui somente permissões DML. Essa migration é aditiva; não executar `migrate:fresh`, rollback, seeders ou comandos de limpeza do banco.
4. Conferir link `public/storage` e escrita em `storage/app/public` pelo usuário do Scheduler. Atualizar caches de configuração/views durante a manutenção e reabrir o site.
5. Entrar como cliente comum, abrir `/monitoramento`, executar explicitamente um ciclo manual e conferir cliente → pedido → produto → pagamento → estoque. Habilitar a flag acadêmica somente quando desejado; conferir o cron existente, sem duplicá-lo.

Nenhuma migração ou execução do gerador foi realizada contra o MySQL do Windows ou da VM durante os testes.
