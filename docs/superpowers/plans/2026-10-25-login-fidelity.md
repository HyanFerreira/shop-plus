# Correção da fidelidade visual do login

Especificação detalhada fornecida pelo usuário e referência visual com autorização expressa para variantes locais de geometria e acabamento. Execução contínua no checkout atual, preservando o trabalho anterior e o asset fornecido `public/images/login.png`. Sem commits.

Direção: card 48%/52%, cantos amplos e transição arredondada, sombra difusa; fundo azulado com curvas decorativas; painel navy com curvas sobrepostas e iluminação; marca maior com destaque Plus e selo informativo; títulos destacados e benefícios mais presentes; imagem local fornecida; campos com ícones e toggle visual; ação com ícone e gradiente dos tokens. Mobile conserva formulário prioritário. Alternativa de recriar o asset em SVG descartada diante da indicação expressa do PNG pelo usuário.

- [x] Comparar implementação anterior/referência e inspecionar tokens, componentes e imagem; capturar antes em 375/1440/1672px.
- [x] Implementar exclusivamente variantes visuais opt-in do login, sem modificar tokens, backend, rotas, segurança ou outras telas.
- [x] Build, captura real e comparação em 1672×843 CSS (área de página da referência); segunda passagem visual realizada.
- [x] Seis larguras 320–1440px, estados, teclado, zoom/reflow e uso sem JS; suíte SQLite em memória com baseline262/7/0.
- [x] Relatório, antes/depois desktop, captura mobile e revisão do diff.

Verificação visual e suíte existente são apropriadas para esta alteração reversível de apresentação; não adicionar testes que apenas reproduzam markup/CSS.
