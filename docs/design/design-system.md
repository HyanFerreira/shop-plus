# Design system

Este sistema traduz a referência visual `ecommerce-ui-reference.png` para componentes consistentes e reutilizáveis. Ele usa superfícies claras, contornos discretos, cantos arredondados e ações inequívocas.

## Fundamentos

- **Canvas:** `slate-50`; superfícies e cartões: branco; contorno: `slate-200`.
- **Texto:** `slate-900` para conteúdo e `slate-500` para informação auxiliar.
- **Ação principal:** `slate-900`; azul fica reservado a links, foco e informação.
- **Raio:** 8 px em controles, 12 px em cartões e 16 px em painéis grandes.
- **Sombra:** sutil e exclusiva de cartões/painéis; controles usam borda e sombra curta.

## Componentes

### Botões

Use sempre `x-button`; não combine classes de cor de botão diretamente nas telas.

```blade
<x-button type="submit">Salvar</x-button>
<x-button variant="secondary">Voltar</x-button>
<x-button variant="danger">Excluir</x-button>
<x-button variant="success">Confirmar recebimento</x-button>
```

As quatro variantes são `primary` (escuro), `secondary` (claro), `danger` (vermelho) e `success` (verde). Tamanhos: `sm`, `md` (padrão) e `lg`.

`x-secondary-button` e `x-danger-button` continuam como aliases compatíveis. Para a ação verde, use `x-success-button`.

### Formulários

Use `x-label`, `x-input` e `x-input-error for="campo"`. Para texto auxiliar, aplique `ds-help`.

### Estrutura e estados

- `ds-container`, `ds-page`, `ds-panel` e `ds-card` organizam o layout;
- `x-badge` usa `neutral`, `info`, `success`, `danger` ou `warning`;
- `x-alert` usa os mesmos estados para mensagens persistentes.

Os componentes já incluem contraste, foco e estado desabilitado. Um novo controle deve reutilizar esse contrato antes de criar uma nova variante.
