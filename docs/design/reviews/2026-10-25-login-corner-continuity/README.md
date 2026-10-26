# Continuidade do fundo nos cantos do formulário

O painel institucional agora avança40px por trás do formulário, cobrindo o raio dos cantos esquerdos. Fundo, iluminação e curva são pintados pelo mesmo painel, evitando expor o navy sólido do container. O padding direito compensa a extensão, preservando a largura e o posicionamento do conteúdo institucional e da ilustração. Ajuste exclusivo do login desktop em `resources/css/auth.css`.

Build aprovado. Navegador real em320/375/768/1024/1280/1440px: sem overflow; campos continuam40px; sobreposição desktop medida em40px. Sem mudanças funcionais ou commits.

- [Captura desktop](login-1440.png)
- [Detalhe da junção e dos cantos](panel-join.png)
- [Captura mobile](login-375.png)
- [Medições](layout.json) e [executor](verify.mjs)
