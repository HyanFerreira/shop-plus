# Recuperação de senha — 26/10/2026

Implementação restrita à apresentação de `/forgot-password`: card único com cabeçalho institucional, marca centralizada, curva branca, PNG original, formulário e retorno ao login. Login e cadastro preservados.

## Implementação

- `resources/views/auth/forgot-password.blade.php`: composição, formulário, erros acessíveis, status real do servidor e link para a rota de login.
- `resources/views/components/auth-shell.blade.php`: opção `recovery`, sem modificar o HTML renderizado nas demais apresentações.
- `resources/css/auth.css`: regras sob `.auth-page--recovery`, consumindo os tokens existentes. A curva usa `::after`, largura de 120%, altura de 24px e bordas superiores elípticas; não sobrepõe o logo.
- `resources/views/layouts/guest.blade.php`: inclui a estratégia existente de Figtree local também na rota `password.request`.
- `tests/Feature/RecoveryPresentationTest.php` e `AuthFontLoadingTest.php`: cobertura da apresentação, mensagens, segurança e escopo das fontes.

Reutilizados: `auth-shell`, `shop-logo`, `auth-form`, `auth-submit`, `input`, `label`, `auth-field-error`, `alert`, `icon`, `login-icon`, estilos dos controles/bloco inferior e `auth-validation.js`. O JavaScript compartilhado não foi alterado.

Asset: **`public/images/forgout-password.png`**, grafia original preservada. PNG RGBA de 1536 × 1024 com transparência e brilho/sombra incorporados; nenhuma alteração na imagem. Exibido em 192 × 128px no desktop e 160 × 106,66px no mobile, com dimensões intrínsecas, proporção reservada, `contain`, carregamento eager e prioridade alta, sem preload duplicado.

SHA-256: `f06b1f8f8d7c43c59794b38f7545ecc8cbc6bfa95ea25b3bcb921f76e04bae9b`.

## Capturas e medidas

- [Desktop 1440 × 900](recovery/recovery-1440x900.png)
- [Mobile 375 × 812](recovery/recovery-375x812.png)
- [Mobile 320 × 640](recovery/recovery-320x640.png)
- [Viewport baixa](recovery/recovery-1440x600.png)
- [Reflow equivalente a zoom de 200%](recovery/recovery-zoom-200.png)
- [Erro com foco por teclado](recovery/recovery-client-error-1440.png)
- [Erro mobile](recovery/recovery-client-error-375.png)
- [Erro de campo do servidor](recovery/recovery-server-field-1440.png)
- [Alerta geral](recovery/recovery-server-error-1440.png)
- [Status](recovery/recovery-server-status-1440.png)
- [Estado de envio](recovery/recovery-pending-1440.png)

| Viewport | Card (largura × altura) |
| --- | --- |
| 320 × 640 | 296 × 588,66px |
| 375 × 812 | 343 × 564,66px |
| 768 × 900 | 464 × 586px |
| 1024 × 768 | 464 × 586px |
| 1280 × 720 | 464 × 586px |
| 1440 × 900 | 464 × 586px |
| 1440 × 600 | 464 × 586px |
| 720 × 450, DPR 2 | 464 × 564,66px |

O último cenário emula o espaço CSS disponível ao ampliar 1440 × 900 para 200%; não é uma alteração nativa do zoom do Chrome. Sem overflow horizontal, imagem sem cortes e rolagem natural quando necessária. Input/botão: 40px; textos/label/link: 16px; título: 32px; erros: 14px; link inferior: altura natural de 24px.

Nenhuma imagem de referência de composição acompanhava o anexo de texto disponível nesta sessão. A comparação seguiu a composição escrita e a identidade atual de login/cadastro; não há comparação visual literal com uma referência ausente.

## Verificação

- `npm run build`: passou; Vite 6.4.5, CSS `app-BYuPSK5V.css`; JavaScript preservado `app-BwVb2cst.js`.
- Suíte completa: **304 testes, 1830 assertions, 7 ignorados, nenhuma falha**. Executada em cópia temporária protegida, com configuração de testes e SQLite `:memory:`; nenhum teste contra banco persistente.
- Grupo focado de apresentação, recuperação, fontes e imagem: 14 testes, 164 assertions.
- Pint nos testes alterados e `git diff --check`: passaram.
- Chrome real via CDP: seis larguras, viewport baixa, reflow de 200%, validação de campo intocado/apagado/inválido/corrigido, envio vazio, foco por teclado e fallback nativo sem JavaScript passaram. [Medidas completas](recovery/layout.json), [execução](browser.txt).
- O estado de envio foi inspecionado após o handler real do formulário, cancelando apenas a navegação na instrumentação do navegador. O endpoint foi testado separadamente com notificações falsas, sem simular entregas externas.
- Onze cenários de carregamento: seis larguras com cache frio, reload, hard reload, duas conexões lentas e navegação a partir do login. Sem mudanças de geometria após o primeiro paint, CLS zero, fontes locais Figtree e nenhuma requisição a provedores externos de fontes. [Dados e frames](after-build/summary.json), [execução](loading.txt). Amostras muito rápidas podem não ter glifos disponíveis na primeira consulta de fontes; as capturas lentas registraram Figtree, sem fallback observado.
- Login/cadastro: estilos computados e medidas idênticos em seis larguras. Quatro capturas desktop/mobile com **zero pixels alterados**. [Comparação](auth-comparison.json), diretórios `before/` e `after/`.
- Hashes do PNG e de `auth-validation.js` preservados. Revisão independente sem problemas identificados.

## Limites funcionais preservados

Rotas, backend, tokens, CSRF, sessões, rate limiting, validações autoritativas e `.env` não foram alterados por esta implementação. As mensagens reais mantêm a neutralidade entre e-mails cadastrados e desconhecidos. Os testes usam notificações falsas e não comprovam entrega SMTP externa.

O limite real de tentativas continua respondendo HTTP 429 pela página de erro existente; falhas de transporte continuam sob o tratamento atual do servidor. A tela exibe erros gerais/status quando fornecidos ao formulário pelo fluxo existente; não converte essas respostas em AJAX nem muda o backend para produzir alertas artificiais. Capturas de alertas gerais foram alimentadas por rotas sintéticas somente no preview temporário, não incluídas no produto.

Sem commits, push, tags, releases ou publicação. Alterações locais anteriores preservadas.
