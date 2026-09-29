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
- catálogo público pesquisável com categorias, produtos, preços em centavos e imagens validadas;
- administração do catálogo com autorização no servidor e exclusão lógica.
- fornecedores fictícios protegidos, ordens de compra e recebimentos parciais;
- estoque transacional com idempotência e livro-razão imutável.
- carrinho autenticado, frete interno e checkout idempotente com reserva transacional;
- pedidos com itens congelados e snapshot criptografado do endereço.
- pagamento interno com cartões fictícios, idempotência e descarte de PAN/CVV;
- aprovação, recusa e cancelamento integrados ao livro-razão de estoque.

Processamento administrativo do pedido e entrega simulada serão adicionados nos próximos incrementos.

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

Detalhes dos controles estão em [proteção de dados pessoais](docs/security/personal-data.md), [segurança do catálogo](docs/security/catalog.md), [integridade de estoque](docs/security/inventory.md), [segurança do checkout](docs/security/checkout.md) e [segurança do pagamento simulado](docs/security/payment.md). A arquitetura aprovada está em [docs/superpowers/specs/2026-09-28-ecommerce-system-design.md](docs/superpowers/specs/2026-09-28-ecommerce-system-design.md).
