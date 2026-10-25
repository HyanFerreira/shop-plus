# ShopPlus

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
- entrega simulada com rastreio, máquina de estados e eventos imutáveis;
- acompanhamento privado do cliente e operação administrativa dos pedidos.

O fluxo funcional e o refinamento visual até a Etapa 8 estão implementados, incluindo autenticação, expiração de pedidos, reembolso, entrega, gestão de administradores, auditoria e dados fictícios de demonstração.

A referência visual e os relatórios estão em [docs/design](docs/design/README.md). A apresentação de SDS está prevista para **26/10/2026**; a VM precisa passar pelo inventário e ensaio próprios antes de ser liberada.

## Instalação nova na VM do IFSP

Siga o **[guia de instalação limpa](docs/installation/ifsp-vm.md)**. Ele separa a preparação no Windows das ações que dependem da VM: PHP corrigido e extensões, Composer, schema MySQL novo, contas de migração/runtime/leitura, chaves privadas, seed, servidor com webroot `public/`, permissões, scheduler e Workbench.

O clone inclui **`public/build` compilado**, SVGs, imagens, fontes Blade/CSS/JS, testes e lockfiles. Instale as dependências Composer no destino; não copie `vendor` ou PHP Windows para Linux. Node/Vite não é necessário para servir o build incluído. Para reconstruir, use `npm ci` e `npm run build` (`npm.cmd` no Windows).

`.env.example` e `.env.vm.example` contêm apenas modelos sem segredos. Configure `.env` privadamente e gere chaves próprias para a base nova. Não reutilize chaves sintéticas de `.env.testing`, credenciais locais ou dados existentes. Não execute migrations/seeders no banco local atual para seguir este README.

O modo de demonstração usa `MAIL_MAILER=array`: cadastro e compra não exigem SMTP na configuração atual; mensagens de recuperação não são entregues nesse modo. Não alterar as etapas de segurança para a apresentação.

## Qualidade e segurança

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
php vendor/phpunit/phpunit/phpunit
php tests/Safety/verify-isolation.php
php tests/Safety/verify-bootstrap.php
php tests/Safety/verify-dotenv-cache.php
php vendor/bin/pint --test
composer validate --strict
composer audit
npm.cmd audit
npm.cmd run build
```

Execute validações num checkout de testes com dependências dev e sem cache de configuração, nunca sobre o MySQL de desenvolvimento. O [relatório final](docs/release/2026-10-25-sds.md) registra build, baseline de **262 aprovados, 7 ignorados, zero falhas**, avisos NPM e divergências preexistentes do Pint. Não apresenta esses avisos como resolvidos.

Detalhes dos controles estão em [proteção de dados pessoais](docs/security/personal-data.md), [segurança do catálogo](docs/security/catalog.md), [integridade de estoque](docs/security/inventory.md), [segurança do checkout](docs/security/checkout.md), [segurança do pagamento simulado](docs/security/payment.md), [segurança da entrega](docs/security/shipping.md) e no [checklist de execução segura](docs/security/deployment.md). A arquitetura aprovada está em [docs/superpowers/specs/2026-09-28-ecommerce-system-design.md](docs/superpowers/specs/2026-09-28-ecommerce-system-design.md).
