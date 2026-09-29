# Procurement and Inventory Implementation Plan

**Goal:** Add secure fictitious suppliers, store purchase orders, partial receiving, and an immutable/idempotent stock ledger.

**Architecture:** Administrative Livewire pages call transaction-scoped actions. Inventory quantities are projections updated under row locks while `stock_movements` remains the immutable audit source. Every stock mutation requires a unique idempotency key.

**Spec:** `docs/superpowers/specs/2026-09-28-ecommerce-system-design.md`

## Constraints

- Admin-only writes, with authorization repeated in Livewire and actions.
- Supplier document/contact/address data is synthetic and encrypted; searchable documents use a blind index.
- Costs and totals are integer cents.
- Purchase-order state transitions are explicit and irreversible where specified.
- Receiving locks order/item/inventory rows, cannot exceed ordered quantity, and is idempotent.
- Stock movement records are never updated or deleted through application code.
- Manual adjustments require an administrator, justification, and idempotency key.

### Task 1: Persistence and State Types

- Add supplier, supplier-product, purchase-order/item, inventory-item, and stock-movement enums/models/migrations/factories.
- Test relationships, encryption, integer money, unique public numbers/idempotency keys, casts, and derived availability.
- Commit `feat: add procurement and inventory persistence`.

### Task 2: Supplier Administration

- Add normalized synthetic company document handling, encrypted supplier fields, admin route/component/actions, CRUD and product-cost association.
- Test authorization, uniqueness, escaping, soft deletion, and integer-cent costs.
- Commit `feat: manage fictitious suppliers`.

### Task 3: Purchase Orders

- Add draft creation/editing, items with frozen costs, totals computed server-side, placing and cancellation transitions.
- Test invalid transitions, unavailable products/suppliers, tampered totals, and immutable non-draft content.
- Commit `feat: manage supplier purchase orders`.

### Task 4: Receiving and Stock Ledger

- Add idempotent partial receiving under pessimistic locks, inventory projection updates, immutable movements, and automatic order states.
- Test partial/full/duplicate/over receipt and concurrent-safe invariants.
- Commit `feat: receive purchases into inventory`.

### Task 5: Manual Adjustments and Verification

- Add justified idempotent admin adjustments and inventory view.
- Document stock invariants and run full MySQL/tests/audits/build verification.
- Commit implementation and documentation independently.
