# Ilustrações dos produtos fictícios ShopPlus

As 16 ilustrações SVG desta pasta foram desenhadas originalmente em código para o ShopPlus nesta implementação (2026-10-23), sem fotografias, marcas, fontes, bibliotecas ou imagens de terceiros. Não são fotos dos produtos nem reproduções de marcas reais.

Licença dos desenhos: CC0 1.0 Universal — dedicação ao domínio público, sem restrições adicionais impostas por este projeto. Texto legal: https://creativecommons.org/publicdomain/zero/1.0/legalcode

Cada arquivo representa um tipo de produto específico. O mapeamento SKU/arquivo/texto alternativo está em config/demo-images.php. A Home prioriza imagens cadastradas disponíveis, depois a ilustração do SKU demo, e usa fallback explícito para produtos desconhecidos sem imagem. Nenhum nome/preço/avaliação comercial é inferido do desenho.

Para associar os assets aos registros ProductImage, executar somente o seeder DemoProductImagesSeeder no ambiente desejado. Ele é idempotente, copia apenas estes arquivos ao disco public, adiciona associações com sort_order 100 e preserva fotos existentes e todos os atributos comerciais. O DatabaseSeeder geral não foi alterado e não deve ser executado como atualização visual de um banco existente.
