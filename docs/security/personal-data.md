# Proteção de dados pessoais

Este projeto acadêmico aceita **somente dados fictícios**. Os formatos imitam dados brasileiros para demonstrar controles de segurança, mas nenhuma informação de pessoa real deve ser cadastrada, importada ou incluída em fixtures, capturas de tela e logs.

## Dados protegidos

O Laravel cifra de forma autenticada, usando `APP_KEY`, os seguintes campos antes de persistir no MySQL:

- CPF;
- número de telefone;
- destinatário, CEP, logradouro, número, complemento, bairro, cidade e UF do endereço.

Os modelos escondem ciphertexts e índices cegos de `toArray()` e JSON. Data de nascimento, rótulo, tipo e indicadores de registro principal permanecem estruturados porque são necessários para regras do domínio. A página lista telefone e CEP mascarados e só carrega valores completos quando o usuário escolhe editar seu próprio registro.

## Índices cegos

CPF e telefone normalizados recebem HMAC-SHA-256 com `PII_BLIND_INDEX_KEY`. Isso permite unicidade e comparação sem gravar o valor pesquisável em texto puro.

A chave deve:

- ser aleatória, exclusiva deste sistema e diferente de `APP_KEY`;
- existir apenas no gerenciador de segredos ou `.env` local não versionado;
- ter pelo menos 32 bytes de entropia;
- nunca ser impressa em logs, erros, seeders ou documentação.

Exemplo para gerar uma chave local, copiando a saída diretamente para o `.env`:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

O índice cego protege contra um vazamento isolado do banco, mas não contra comprometimento simultâneo da aplicação e de suas chaves. Também permite correlação de valores iguais dentro do escopo do índice. Para CPF, a coluna é globalmente única; para telefone, a unicidade é por cliente.

## Rotação de chaves

`APP_PREVIOUS_KEYS` permite ao Laravel ler ciphertexts criados com chaves de aplicação anteriores durante uma rotação planejada. Os dados devem ser recifrados com a chave atual antes de remover as chaves antigas.

A rotação on-line de `PII_BLIND_INDEX_KEY` ainda não foi implementada. Como os HMACs não são reversíveis, a operação exige manter acesso temporário aos valores descriptografados, recalcular todos os índices em uma manutenção controlada e validar unicidade antes de ativar a nova chave. Nunca substitua essa chave diretamente em produção sem a migração correspondente.

## Autorização e exposição

- O proprietário é sempre derivado do usuário autenticado; IDs de usuário não fazem parte do estado Livewire.
- Edição e exclusão consultam registros pela relação do proprietário, impedindo IDOR.
- Alterações de telefone/endereço principal usam transação e bloqueio pessimista.
- A página `/meus-dados` exige sessão autenticada/verificada e aplica `Cache-Control` com `no-store` e `private`.
- Blade mantém escape automático para conteúdo persistido; testes cobrem tentativa de XSS.
- Erros de CPF duplicado são genéricos e não identificam a conta proprietária.

## Logs e suporte

Não registre payloads dos formulários, valores descriptografados, índices cegos, chaves, cookies ou IDs de sessão. Diagnósticos devem usar apenas IDs internos de evento/recurso e resultados genéricos. Dumps de banco, traces e capturas usados na atividade precisam ser revisados antes de compartilhamento.

## Verificação automatizada

Os testes em `tests/Feature/PersonalData` verificam ciphertext no banco, ocultação em serialização, unicidade, mascaramento, XSS, regras de registro principal, cache privado e isolamento entre clientes. Os testes em `tests/Unit/Domain/PersonalData` cobrem CPF e HMAC.
