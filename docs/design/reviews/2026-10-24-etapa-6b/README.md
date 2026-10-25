# Etapa 6B — Dashboard e Área da Conta

Implementação restrita à apresentação da conta, na main existente, preservando alterações locais. Sem commits, dependências adicionais, mudanças em .env ou banco persistente.

## Revalidação da entrega em 24/10/2026

A retomada encontrou a implementação, o plano e as capturas desta etapa já presentes como alterações locais na `main`. Esses arquivos foram preservados; não houve nova implementação nesta retomada.

- `npm.cmd run build`: aprovado, Vite 6.4.3, 58 módulos transformados.
- `php vendor/phpunit/phpunit/phpunit`: executado novamente com o bootstrap protegido e SQLite `:memory:`; 227 testes, 1234 assertions, 218 aprovados, 7 ignorados e as mesmas 2 falhas documentadas abaixo. Nenhuma regressão adicional identificada pela suíte.
- `git diff --check`: aprovado, com aviso de normalização CRLF/LF em `profile/show.blade.php`.
- Referência oficial e capturas existentes de dashboard, dados pessoais mobile e perfil desktop inspecionadas visualmente. `validation.json` contém 12 medições por largura (320, 375, 768, 1024, 1280 e 1440px), totalizando 72 registros sem overflow. As capturas e medições foram reutilizadas da implementação existente; o navegador não foi executado novamente nesta retomada.

Esta revalidação acrescenta apenas este registro ao relatório; não altera regras, infraestrutura, configuração de testes ou dados persistentes.

## Resultado e arquivos

- Novos `app/Livewire/Account/Overview.php` e `resources/views/livewire/account/overview.blade.php`: resumo real do próprio cliente, pedidos recentes, estados vazios e atalhos existentes. Consultas somente de leitura, sem indicadores administrativos para clientes.
- Novo `resources/views/components/account-shell.blade.php`; integração em `dashboard.blade.php`, `personal-data/show.blade.php` e `profile/show.blade.php`.
- `components/customer-navigation.blade.php`: sidebar compartilhada com pedidos, disclosure mobile, página ativa, ícones, logout e acesso administrativo condicionado. `components/icon.blade.php` acrescenta user e chevron-down.
- `layouts/app.blade.php`: perfil usa o storefront sem menu Jetstream duplicado; área de tokens preservada.
- `livewire/personal-data/manager.blade.php`: cards, campos responsivos, listas, badges de principal, carregamento e associação acessível de erros. Classe PHP Manager inalterada.
- Templates `profile/update-profile-information-form`, `update-password-form`, `two-factor-authentication-form`, `logout-other-browser-sessions-form` e `delete-user-form`: apresentação, organização e textos em português, conservando as condições e ações Jetstream.
- Novo `resources/css/account.css`, importado por `resources/css/app.css`; documentação em `docs/design/design-system.md` e plano em `docs/superpowers/plans/2026-10-24-account-refinement.md`.
- Novo `tests/Feature/Account/AccountPresentationTest.php`: posse das informações, destinos e administração, estado vazio, máscaras/erros e contratos Jetstream.

Reutilizados tokens --ds-*, Figtree, x-button/input/label/input-error/checkbox, x-badge, ds-panel, x-icon, x-order-status e x-order-item-image. O wrapper é simples; não foi introduzida biblioteca ou substituto do Jetstream.

## Fluxos preservados

Cadastro/edição/exclusão e principal de telefones/endereços permanecem nos métodos existentes. Perfil, senha, confirmação de senha, 2FA, sessões e exclusão da conta conservam bindings e condições originais. Autorização do administrador continua existente; clientes recebem 403 na área administrativa. Logout mantém POST/CSRF. Nenhuma alteração em pedidos, pagamentos, estoque, entregas, migrations, rotas ou mecanismos de autenticação.

Foram conferidos hashes de 96 arquivos protegidos: nenhum alterado, incluindo actions/models, Manager, rotas, configurações de segurança, isolamento dos testes e arquivos das telas anteriores selecionados antes da implementação. Alterações locais anteriores não foram descartadas.

## Validação

| Verificação | Resultado |
| --- | --- |
| Build Vite (`npm.cmd run build`) | Aprovado |
| Suíte direcionada: conta, dados pessoais e recursos Jetstream | 41 testes, 228 assertions, aprovados |
| Suíte completa, exclusivamente SQLite :memory: protegido | 227 testes, 1234 assertions; 218 aprovados, 7 ignorados, 2 falhas anteriores |
| Baseline anterior | 222 testes, 1164 assertions; 212 aprovados, 7 ignorados, 3 falhas |
| Chrome headless: 3 páginas × 4 cenários × 6 larguras | 72 combinações aprovadas |
| Navegação, salvar identificação, editar endereço/erro e modal de sessões | Aprovados no navegador |
| Pint nos dois arquivos PHP novos | Aprovado |

As duas falhas restantes são `ExampleTest::test_the_application_returns_a_successful_response` e `SecurityHeadersTest::test_successful_web_responses_include_baseline_security_headers`: tabela categories ausente nesses contextos sem preparação do banco. Não foram corrigidas nesta etapa. A falha anterior `AdminAccessTest::test_administrator_navigation_shows_the_administration_link` passou como consequência direta do destino administrativo autorizado agora presente na navegação, sem modificar teste ou permissão para obter esse resultado.

Os testes existentes de dados pessoais e Jetstream exercitam os fluxos funcionais. Os cinco testes adicionados verificam também que pedidos de outro usuário não aparecem e que as contagens pertencem ao cliente autenticado.

### Condições das capturas

Chrome headless real, larguras CSS 320, 375, 768, 1024, 1280 e 1440px, DPR 1 e escala 1. Fixtures exclusivamente fictícias em SQLite :memory:, recriadas a cada requisição por um harness temporário externo ao projeto, protegido antes das migrations em memória. Não foi necessário popular o banco local.

Os cenários são conta com dados/pedidos, conta vazia, nomes/rótulos longos e administrador. As 72 combinações verificam ausência de overflow, header/footer/h1 únicos, controles visíveis com altura mínima de 44px e dentro da viewport, acesso administrativo condicionado e navegação recolhida abaixo de 1024px. Espaço abre o menu e Tab recebe foco visível. Também foi conferida a listagem de pedidos da 6A com a navegação compartilhada.

O preview autentica um usuário sintético e reinicia seus dados a cada requisição: comprova renderização e interações pontuais, não persistência entre requisições ou login real. Persistência, permissões e ações são cobertas pelos testes PHP protegidos. Nenhum mecanismo de segurança da aplicação foi alterado para a captura.

[Medições completas](validation.json).

| Tela | Desktop | Mobile |
| --- | --- | --- |
| Dashboard com pedidos | [1440px](dashboard-full-1440.png) | [375px](dashboard-full-375.png) |
| Dashboard vazio | [1440px](dashboard-empty-1440.png) | [375px](dashboard-empty-375.png) |
| Dados pessoais | [1440px](personal-full-1440.png) | [375px](personal-full-375.png) |
| Dados sem cadastros | [1440px](personal-empty-1440.png) | [375px](personal-empty-375.png) |
| Perfil e segurança | [1440px](profile-full-1440.png) | [375px](profile-full-375.png) |

[Menu mobile aberto](account-navigation-open-375.png), [edição de endereço](personal-edit-address-375.png), [erro de validação](personal-validation-375.png), [confirmação de sessões](profile-session-modal-375.png) e [pedidos com navegação compartilhada](orders-shared-navigation-1440.png).

## Comparação visual e limites

Foram inspecionadas capturas desktop/mobile e comparadas à referência oficial e às composições anteriores: eixo externo de 1120px, sidebar, navy nos títulos, azul nas ações, Figtree, fundo claro, cards brancos, bordas/sombras discretas e tipografia legível. A conta deixa de usar a composição padrão separada do Jetstream. No mobile, menu compacto antecede o conteúdo; formulários e pedidos quebram em linhas.

A referência é direção estética, sem inferir zoom da imagem original. O conteúdo e o número de cards refletem somente os dados reais do ShopPlus. Não foram fabricados avatar fotográfico, benefícios, notificações ou indicadores. Formulários de segurança são mais extensos para preservar todos os recursos e explicações existentes.

Não foram validados dispositivos físicos, Safari/Firefox, leitores de tela ou conformidade WCAG integral. QR/recuperação 2FA, envio de e-mail e encerramento real de sessões não foram executados manualmente no navegador; ações existentes são cobertas pelos testes disponíveis e mantidas sem alteração. O preview em memória não apresenta uma lista persistente de dispositivos.

Pendências para trabalho posterior: resolver separadamente as duas falhas antigas de preparação dos testes, ampliar validação com tecnologias assistivas/outros navegadores e revisar traduções residuais internas dos componentes Jetstream (por exemplo, confirmação genérica por senha). Autenticação e demais telas não foram refatoradas nesta etapa.
