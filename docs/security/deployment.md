# Checklist de execução segura

## Produção

- Use `APP_ENV=production`, `APP_DEBUG=false` e uma `APP_KEY` exclusiva.
- Gere uma `PII_BLIND_INDEX_KEY` aleatória e independente; perder essa chave impede novas buscas por índices, e trocá-la exige migração planejada.
- Use HTTPS e configure `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` e `SESSION_ENCRYPT=true`.
- Use credenciais MySQL exclusivas com o menor privilégio necessário. Nunca versione `.env`, dumps ou backups.
- Configure e teste e-mail para recuperação de senha; o ambiente local usa log por padrão.
- Execute filas com processo supervisionado, retenha logs pelo mínimo necessário e restrinja o acesso operacional.
- Faça backup cifrado e teste restauração. Dados cifrados exigem backup seguro da `APP_KEY`.

## Demonstração acadêmica

Defina `DEMO_PASSWORD` somente no ambiente local e execute `php artisan db:seed`. Se a variável estiver vazia, o comando gera e mostra uma senha efêmera. Todos os e-mails usam o domínio reservado `example.test`; documentos, nomes, endereços e telefones são marcados como fictícios.

Os cartões aceitos não pertencem a pessoas reais:

- `4111 1111 1111 1111`: aprovação Visa;
- `5555 5555 5555 4444`: aprovação Mastercard;
- `4000 0000 0000 0002`: recusa Visa.

O CVV pode ter três ou quatro dígitos e nunca é persistido.

## Verificação antes da entrega

```powershell
php artisan migrate --force
php artisan test
composer audit
npm.cmd audit
npm.cmd run build
```
