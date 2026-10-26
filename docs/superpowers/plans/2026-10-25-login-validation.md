# Validação visual do login

Especificação detalhada fornecida pelo usuário. Escopo: login e apresentação de erros, mantendo geometria inicial e toda autenticação/segurança. Sem commits, dependências ou acesso a banco persistente.

Arquitetura: normalizar apenas na view do login os erros existentes do MessageBag (auth.failed→alerta genérico; auth.throttle→alerta original; validações→campo). Reutilizar x-auth-form/POST/CSRF/loading e x-alert. Mensagens locais em componente exclusivo com circle-alert; senha conserva o mesmo markup/controle acessível. Módulo JS escopado ao formulário, sem consultas: estado de alteração real, validação no blur após modificar, correção imediata de erro já exposto e capture no submit para bloquear inválidos antes do loading existente. required/type/autocomplete preservados; novalidate habilitado somente após inicializar JS. Limites existentes: e-mail255/senha128, sem regras de criação de senha.

- [ ] RED: testes reais de apresentação de credenciais/erros de servidor e interação no navegador.
- [ ] Implementar classificação Blade, mensagens/ícones e controle de interação local, sem alterar componentes globais ou backend.
- [ ] GREEN: testes de apresentação/autenticação/rate limit e cenários de interação; estados de foco vermelho/azul e anúncios sem repetição por tecla.
- [ ] Comparar estado inicial antes/depois, validar desktop/mobile, screenshots de e-mail inválido, senha obrigatória e alerta geral; build e suíte isolada.
- [ ] Revisão do diff e relatório com evidências e limites reais de acessibilidade.
