# Proporções dos controles e foco por teclado

Especificação direta do usuário: superfícies/controles retangulares com raio de8px, preservando cards e painéis do login; ícones/textos/gaps proporcionais aos40px; aproximar prefixo/texto dos campos do login e labels; foco apenas na navegação por teclado, com outline1px e offset0.

- [x] Ajustar tokens/consumidores de raio, tipografia dos controles14/20px, ícones20px e label-gap4px; preservar alturas40px, checkbox16px, marca, ilustração e card do login.
- [x] Prefixo do login a12px da esquerda, ícone20px, padding do texto40px; reduzir toggle e gap de botão.
- [x] Foco1px/offset0 e rastreamento mínimo da modalidade Tab/ponteiro, pois browsers aplicam focus-visible a inputs clicados. Sem alterar eventos de autenticação ou validações; fallback CSS acessível sem JS.
- [x] Build, suíte isolada e navegador real: clique versus Tab/Shift+Tab, foco contínuo, seis larguras, medidas, capturas e26combinações de consumidores.
- [x] Documentar contratos, alterações, resultados e limites; sem commits.

Steering posterior incorporado: labels600 e borda neutra suave conforme portal; aparência de2px ao focar. Clique usa reforço interno1px, Tab usa outline1px colado, evitando acumular3px. Contraste neutro mais baixo é escolha visual explícita e está registrado no design system.
