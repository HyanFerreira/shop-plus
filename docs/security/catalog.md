# Controles de segurança do catálogo

O catálogo separa operações administrativas da leitura pública e adota uma política de exposição fechada por padrão.

## Autorização

- Categorias, produtos e imagens só podem ser alterados por administradores.
- A rota administrativa usa os middlewares de autenticação, sessão Jetstream, verificação de e-mail e papel administrativo.
- O componente Livewire também verifica o papel em cada ciclo de requisição, pois atualizações Livewire usam um endpoint compartilhado.
- Actions de escrita repetem a autorização no servidor e aceitam apenas campos permitidos.

## Visibilidade pública

Um produto aparece publicamente apenas quando:

- o produto está ativo e não foi excluído;
- sua categoria está ativa e não foi excluída.

Detalhes indisponíveis retornam `404`. Filtros de ordenação usam uma lista fechada e nunca transformam entrada do usuário em identificadores SQL.

## Valores monetários

Preços são validados como strings decimais e convertidos sem ponto flutuante. O banco persiste somente centavos inteiros em `price_cents`, evitando erros binários de arredondamento. A formatação em reais ocorre apenas na apresentação.

## Slugs, SKU e exclusão

- Slugs são gerados no servidor.
- SKU é normalizado para maiúsculas e limitado a caracteres seguros.
- Slug e SKU permanecem globalmente reservados mesmo após exclusão lógica.
- Categorias e produtos usam exclusão lógica para preservar referências futuras de estoque e pedidos.

## Imagens

- Somente administradores enviam arquivos.
- O upload exige imagem real JPEG, PNG ou WebP de até 2 MB.
- O nome fornecido pelo cliente não é usado como caminho final; o storage gera o nome dentro do diretório do produto.
- Em falha de persistência, o arquivo recém-gravado é removido.
- Ao excluir a imagem, registro e arquivo são removidos.
- Texto alternativo e demais conteúdos persistidos são renderizados com escape do Blade.

Arquivos de demonstração devem ser sintéticos e não podem conter pessoas, documentos, endereços ou outras informações reais.

## Integração futura

O catálogo ainda não representa disponibilidade. O incremento de estoque será a fonte autoritativa da quantidade vendável; produto ativo sem saldo poderá continuar visível, mas não poderá ser comprado acima do estoque disponível.
