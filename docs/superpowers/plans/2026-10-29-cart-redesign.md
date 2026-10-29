# Carrinho: composição e controle compartilhado

**Objetivo:** implementar a referência fornecida em 29/10/2026 exclusivamente em `/carrinho`, usando Figtree, tokens, container e componentes existentes. Sem commits ou push.

**Arquitetura:** lista em painel único à esquerda e resumo à direita; estoque, imagem, marca/nome, SKU, quantidade e preço reais. Lixeira icon-only global. O controle atual de produto é extraído para `x-quantity-control` com slots que preservam IDs, comandos e markup interno; seus estilos únicos passam para `quantity-control.css`. Cart utiliza esses mesmos estilos.

**Interação:** `CartManager::updateItem` e `removeItem`, preços e validações permanecem. O usuário confirmou manter a digitação existente no controle de produto. O botão Atualizar sai; edição salva ao sair do campo ou pressionar Enter, e +/- salvam uma única vez. Adaptador Alpine serializa mutações e desabilita controles durante a operação. `clearCart` reutiliza `UpdateCartItem` com quantidade zero em transação, apenas para itens próprios. Nenhuma alteração em Models, migrations ou Actions.

- [x] Cobrir limpeza, isolamento, estoque/subtotais e contrato de atualização sem botão; testar serialização e recuperação de falhas do adaptador JS.
- [x] Extrair controle compartilhado sem alterar a aparência/comportamento do produto; refazer a composição do carrinho e adicionar feedback/ações reais.
- [x] Build, testes relevantes e suíte isolada. Comparar produto antes/depois e carrinho vazio/um/múltiplos/limites/nomes longos em desktop/tablet/mobile. Revisão independente.
- [x] Guardar evidências somente em `.artifacts/visual-reviews/` com `visualReviewOutput`; confirmar `git check-ignore`, diff e status final.

**Foco:** digitação não duplica chamadas com +/-; ações concorrentes não sobrescrevem outro item; zero remove conforme contrato existente no servidor; estoque insuficiente conserva valor salvo; limpar não afeta outro usuário; produto mantém dimensões, pintura, foco e comandos originais; controles de 40px e ausência de overflow no carrinho.
