# Sistema de Comércio

Aplicação monolítica acadêmica de comércio construída com Laravel 12, PHP 8.4, MySQL, Jetstream, Livewire e Tailwind CSS.

> Use somente dados fictícios. Nunca cadastre nomes, CPFs, telefones, endereços ou cartões de pessoas reais.

## Estado atual

- autenticação Jetstream com recuperação de senha, 2FA, passkeys e sessões;
- papéis de cliente/administrador e bloqueio de contas desativadas;
- área administrativa protegida no servidor;
- Argon2id e cabeçalhos HTTP de segurança;
- perfil do cliente com CPF sintético validado, telefones e endereços;
- dados pessoais criptografados, índices cegos e isolamento por proprietário.

Catálogo, fornecedores, estoque, carrinho, pedidos, pagamento simulado e entrega serão adicionados nos próximos incrementos.

## Instalação local

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
```

Configure no `.env` sua conexão MySQL sem versionar credenciais. Gere também uma chave exclusiva para `PII_BLIND_INDEX_KEY`:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Depois execute:

```powershell
php artisan migrate
npm.cmd run build
php artisan serve
```

## Qualidade e segurança

```powershell
php artisan test
composer validate --strict
composer audit
npm.cmd audit
npm.cmd run build
```

Detalhes dos controles de dados pessoais estão em [docs/security/personal-data.md](docs/security/personal-data.md). A arquitetura aprovada está em [docs/superpowers/specs/2026-09-28-ecommerce-system-design.md](docs/superpowers/specs/2026-09-28-ecommerce-system-design.md).
