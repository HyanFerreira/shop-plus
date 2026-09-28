# Customer Personal Data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task in the current checkout.

**Goal:** Add secure, synthetic-only customer CPF, phone, and address management with encrypted storage, blind indexes, ownership enforcement, and a minimal Livewire interface.

**Architecture:** Keep personal data inside the Laravel monolith under customer-owned Eloquent models. Normalize sensitive identifiers before producing keyed HMAC blind indexes, store display values with Laravel authenticated encryption, and expose mutations only through an authenticated Livewire component that always scopes records to the current user. Apply `no-store` to the personal-data page.

**Tech Stack:** Laravel 12, PHP 8.4, Eloquent/MySQL, Livewire 3, Jetstream, PHPUnit

**Spec:** `docs/superpowers/specs/2026-09-28-ecommerce-system-design.md`

## Global Constraints

- Use only declared synthetic identities and test values.
- Never persist plain CPF, phone, postal code, recipient, or address content.
- Never log personal-data form payloads.
- Keep the blind-index key separate from `APP_KEY`; document it in `.env.example` and inject a deterministic test-only value through `phpunit.xml`.
- A customer can read and mutate only records related to their own authenticated user ID.
- Public registration remains unchanged; completing personal data is an authenticated action.
- Exactly one phone and one address may be primary per user; the first record becomes primary automatically.

---

### Task 1: CPF Domain Value and Blind Index Service

**Files:**
- Create: `app/Domain/PersonalData/Cpf.php`
- Create: `app/Domain/PersonalData/BlindIndex.php`
- Create: `app/Rules/ValidCpf.php`
- Create: `config/personal-data.php`
- Modify: `.env.example`
- Modify: `phpunit.xml`
- Test: `tests/Unit/Domain/PersonalData/CpfTest.php`
- Test: `tests/Unit/Domain/PersonalData/BlindIndexTest.php`

**Interfaces:**
- Produces: immutable `Cpf::from(string)`, `digits()`, `formatted()`, `masked()`, `isValid(string)`; `BlindIndex::for(string): string`; reusable `ValidCpf` validation rule.

- [ ] Write failing tests for valid formatted/unformatted CPFs, invalid check digits, repeated sequences, canonical formatting, masking, deterministic keyed HMAC output, and separation between different keys.
- [ ] Run the focused unit tests and verify RED.
- [ ] Implement CPF normalization/check digits without external APIs. Implement HMAC-SHA-256 using `config('personal-data.blind_index_key')` and fail closed when the key is absent.
- [ ] Add `PII_BLIND_INDEX_KEY=` to `.env.example` and a synthetic test key to `phpunit.xml`.
- [ ] Run focused and complete tests, then commit `feat: add personal data security primitives`.

### Task 2: Encrypted Personal Data Models

**Files:**
- Create: `app/Enums/PhoneType.php`
- Create: `app/Models/CustomerProfile.php`
- Create: `app/Models/Phone.php`
- Create: `app/Models/Address.php`
- Create: `database/migrations/2026_09_28_231000_create_customer_profiles_table.php`
- Create: `database/migrations/2026_09_28_231100_create_phones_table.php`
- Create: `database/migrations/2026_09_28_231200_create_addresses_table.php`
- Create: `database/factories/CustomerProfileFactory.php`
- Create: `database/factories/PhoneFactory.php`
- Create: `database/factories/AddressFactory.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/PersonalData/EncryptedPersonalDataTest.php`

**Interfaces:**
- Produces: `User::customerProfile()`, `phones()`, `addresses()`; encrypted model attributes; hidden ciphertext/hash fields; factory states with synthetic values only.

- [ ] Write failing tests proving plaintext does not appear in raw database rows, model access decrypts values, CPF/phone hashes are canonical, uniqueness is enforced, casts are typed, relationships work, and sensitive fields are absent from array/JSON serialization.
- [ ] Run focused tests and verify RED.
- [ ] Implement reversible migrations with foreign-key cascades, 64-character hashes, unique profile per user, unique normalized phone per user, indexed primary flags, and text columns for encrypted payloads.
- [ ] Add guarded/fillable boundaries, encrypted casts, date/boolean/enum casts, relationships, and explicitly fictitious factories.
- [ ] Run focused and complete tests, then commit `feat: store encrypted customer personal data`.

### Task 3: Customer Profile Service and Livewire Page

**Files:**
- Create: `app/Actions/PersonalData/SaveCustomerProfile.php`
- Create: `app/Livewire/PersonalData/Manager.php`
- Create: `resources/views/livewire/personal-data/manager.blade.php`
- Create: `resources/views/personal-data/show.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/navigation-menu.blade.php`
- Test: `tests/Feature/PersonalData/CustomerProfileManagementTest.php`

**Interfaces:**
- Produces: named route `personal-data.show` at `/meus-dados`; transaction-safe profile upsert; Livewire form fields `cpf` and `birthDate`.

- [ ] Write failing tests for guest redirect, authenticated rendering, valid create/update, formatted redisplay, invalid CPF rejection, duplicate CPF rejection, and mass-assignment attempts that cannot target another user.
- [ ] Run focused tests and verify RED.
- [ ] Implement an action that derives `user_id`, encrypted CPF, and blind index server-side. Build the authenticated Livewire page and navigation link using escaped Blade output.
- [ ] Run focused and complete tests, then commit `feat: manage encrypted customer profiles`.

### Task 4: Customer Phone Management

**Files:**
- Create: `app/Actions/PersonalData/SavePhone.php`
- Create: `app/Actions/PersonalData/DeletePhone.php`
- Modify: `app/Livewire/PersonalData/Manager.php`
- Modify: `resources/views/livewire/personal-data/manager.blade.php`
- Test: `tests/Feature/PersonalData/PhoneManagementTest.php`

**Interfaces:**
- Produces: phone normalization for Brazilian 10/11-digit numbers, create/update/delete actions scoped to the authenticated owner, and primary-phone invariant.

- [ ] Write failing Livewire tests for create, normalization, invalid values, duplicate normalized numbers, edit/delete ownership protection, first-record primary behavior, primary switching, and automatic promotion after deleting the primary.
- [ ] Run focused tests and verify RED.
- [ ] Implement transactional actions that accept the authenticated `User`, query related records through `$user->phones()`, compute encrypted value/hash server-side, and never trust a submitted owner ID.
- [ ] Render masked phone lists and explicit create/edit/delete controls.
- [ ] Run focused and complete tests, then commit `feat: manage encrypted customer phones`.

### Task 5: Customer Address Management and Private Caching Policy

**Files:**
- Create: `app/Actions/PersonalData/SaveAddress.php`
- Create: `app/Actions/PersonalData/DeleteAddress.php`
- Create: `app/Http/Middleware/PreventSensitiveResponseCaching.php`
- Modify: `app/Livewire/PersonalData/Manager.php`
- Modify: `resources/views/livewire/personal-data/manager.blade.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PersonalData/AddressManagementTest.php`
- Test: `tests/Feature/PersonalData/PersonalDataSecurityTest.php`

**Interfaces:**
- Produces: owner-scoped address CRUD, primary-address invariant, middleware alias `no-store`, and `Cache-Control: no-store, private` on the personal-data page.

- [ ] Write failing tests for complete address create/update/delete, required Brazilian UF/postal-code validation, encrypted raw storage, ownership isolation/IDOR, first/selected primary behavior, promotion after deletion, escaped persistent input, and the exact no-store response policy.
- [ ] Run focused tests and verify RED.
- [ ] Implement transactional owner-scoped actions and encrypted address attributes. Register and apply the response-caching middleware only to sensitive pages.
- [ ] Render a minimal accessible Tailwind form and masked summary; use normal escaped Blade bindings only.
- [ ] Run focused and complete tests, then commit `feat: manage encrypted customer addresses`.

### Task 6: Increment Verification and Documentation

**Files:**
- Create: `docs/security/personal-data.md`
- Modify: `README.md`

- [ ] Document the synthetic-data rule, key generation, key rotation limitation, encrypted fields, blind-index threat model, masking rules, ownership controls, and local setup without including a real key or personal data.
- [ ] Generate a local untracked blind-index key in `.env` if absent, run migrations, and exercise a real MySQL model round-trip without printing plaintext values.
- [ ] Run `php artisan test`, `php artisan migrate:status`, `composer validate --strict`, `composer audit`, `npm.cmd audit`, and `npm.cmd run build`.
- [ ] Confirm `git grep` finds no real-looking personal data, secrets, or debug dumps in tracked files.
- [ ] Commit `docs: document personal data protections`.

## Completion Evidence

- All focused and complete test suites pass.
- MySQL stores ciphertext rather than plaintext for sensitive values.
- Direct record IDs cannot cross customer ownership boundaries.
- Sensitive pages are not browser/proxy cacheable.
- No real PII or secret blind-index key is committed.
