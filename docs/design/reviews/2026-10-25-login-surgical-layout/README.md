# Login — ajuste cirúrgico de geometria

Alteração exclusiva de `resources/css/auth.css`, sob seletores do login desktop, além do build gerado. Sem mudanças em componentes globais, Blade, autenticação, validações, backend, banco ou outras telas. Sem commits.

[Comparação real antes/depois](comparison.html), mesma viewport CSS e DPR1: [antes1440×800](before/login-1440x800.png), [depois1440×800](login-1440x800.png). Mobile: [antes](before/login-375x812.png), [depois](login-375x812.png).

## Ajustes

- Padding externo simétrico de16px vertical/32px horizontal no desktop; flex centraliza o conjunto inteiro, sem offsets ou margens artificiais.
- Painéis visuais55%/45%. Preservada a extensão pintada de40px sob os cantos do formulário, sem faixas de outra cor.
- Padding superior esquerdo40→16px; padding vertical direito48→32px. Espaços institucionais compactados com a escala existente.
- Conteúdo do formulário limitado a26rem (**416px**), incluindo heading, feedback e cadastro, alinhados no mesmo eixo.
- Título esquerdo mantém32/40px e as duas linhas exatas: “Sua próxima compra” / “começa aqui.”. Somente “aqui.” recebe `--ds-brand`, exatamente como “volta!”. Nenhuma quebra nova foi introduzida no markup ou no mobile.
- Arte com altura automática e largura máxima480px, um passo de32px abaixo da anterior. Margens laterais negativas removidas. PNG e plataforma permanecem inteiros e após os benefícios, sem sobreposição.
- A mesma curva decorativa existente acompanha a altura do painel para manter sua posição inferior ao ampliar a coluna. Nenhum elemento, gradiente, cor, borda ou sombra novo foi acrescentado.

## Medidas reais

| Viewport CSS | Card antes | Card depois | Largura do input | Centro vertical do card |
| --- | --- | --- | --- | --- |
| 1440×800 | 718px | **642px** | **416px** | **400px** |
| 1366×681 | 718px, página com782px e rolagem | **642px**, sem rolagem | **416px** | **340.5px** |
| 1280×720 | — | **642px** | **416px** | **360px** |
| 1024×768 | 715.91px | **633.44px** | **367.11px** | **384px** |
| 375×812 | 538px | **538px** | **309px** | **406px** |

Sem altura fixa no card. Em320×640 e1440×600, o conteúdo cresce naturalmente com rolagem vertical, sem cortes; as demais condições testadas cabem na viewport. Nenhuma condição apresentou overflow horizontal.

Verificados: centralização horizontal/vertical quando o conteúdo cabe; proporção real do grid55/45; título de duas linhas com32px; igualdade da cor dos destaques; três benefícios16/14px; imagem carregada, inteiramente contida e sem sobrepor a lista; campos40px; checkbox16×16px e link natural24px. No mobile, o painel institucional continua oculto e o formulário mantém os estilos anteriores.

## Validação

- Build aprovado, Vite6.4.5,59 módulos — [saída](build.txt).
- Testes pertinentes de autenticação/cadastro com SQLite em memória protegido: **5 aprovados,1 ignorado,zero falhas;9 assertions** — [saída](auth-tests.txt), [JUnit](auth-tests.xml).
- Chrome real, oito combinações de largura/altura:320/375/768/1024/1280/1366/1440px, incluindo uma viewport de desktop baixa. Inspeção visual das capturas desktop/mobile concluída.
- `git diff --check` aprovado.
- [Medições antes](before/layout.json), [depois](layout.json), [executor antes](capture-before.mjs) e [executor depois](verify-after.mjs).

Preview externo temporário com as proteções existentes e SQLite `:memory:`, sem rotas de preview na aplicação e sem acesso a banco persistente. Não foram usados dispositivos físicos, outros navegadores ou leitor de tela nesta revisão exclusivamente geométrica.
