# Ecommerce Security and Roles Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish reusable customer/admin authorization, disabled-account enforcement, Argon2id password hashing, and baseline HTTP security headers for the ecommerce monolith.

**Architecture:** Extend the existing Jetstream user model with typed role and status enums, enforce administrative boundaries through middleware and a dedicated route, and keep password authentication in Fortify. Apply security headers through global web middleware and configure Laravel hashing explicitly, leaving domain-specific ecommerce behavior for later increments.

**Tech Stack:** Laravel 12, PHP 8.4 enums, Jetstream/Fortify, Eloquent, PHPUnit, MySQL

**Spec:** `docs/superpowers/specs/2026-09-28-ecommerce-system-design.md`

## Global Constraints

- Keep a single Laravel monolith and MySQL database.
- Preserve Jetstream login, password recovery, 2FA, passkeys, session management, and Livewire behavior.
- Default every newly registered user to active customer; never accept role or status from public registration input.
- Require server-side authorization for every admin resource.
- Use Argon2id for newly hashed passwords.
- Do not log credentials, session identifiers, personal data, or payment data.
- Do not introduce catalogue, supplier, stock, order, payment, or shipping behavior in this increment.

## Review Focus

- A registration payload that injects `role=admin` or `status=active` must still create an active customer; Task 1 tests mass-assignment resistance at the public boundary.
- A disabled account with a correct password must not authenticate, while the response remains the same generic credential failure; Task 2 tests both behavior and message shape.
- An authenticated customer must receive `403` from admin routes even when calling the URL directly; Task 3 tests IDOR/privilege escalation protection.
- Existing Jetstream authentication and password recovery routes must remain functional after the Fortify customization; Tasks 2 and 5 run their focused and complete suites.
- Security headers must be attached to normal, error, and redirect web responses without breaking Livewire; Task 4 exercises successful and redirect responses, and Task 5 runs the full suite.

---

### Task 1: Typed User Roles and Statuses

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/UserStatus.php`
- Create: `database/migrations/2026_09_28_230000_add_role_and_status_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/UserRoleAndStatusTest.php`

**Interfaces:**
- Consumes: existing Jetstream `App\Models\User`
- Produces: `UserRole: string` (`Customer`, `Admin`), `UserStatus: string` (`Active`, `Disabled`), typed `User::$role`, typed `User::$status`, `User::isAdmin(): bool`, `User::isActive(): bool`, factory states `admin()` and `disabled()`

- [ ] **Step 1: Write failing role/status tests**

Create tests named:

- `new_users_default_to_active_customer`
- `role_and_status_are_cast_to_enums`
- `admin_and_disabled_factory_states_are_explicit`
- `public_registration_ignores_injected_role_and_status`

Assert literal enum values `customer`, `admin`, `active`, and `disabled`; assert `isAdmin()` and `isActive()` behavior; POST to `/register` with injected privileged fields and assert the stored user remains an active customer.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/UserRoleAndStatusTest.php`

Expected: FAIL because enums, columns, casts, helpers, and factory states do not exist.

- [ ] **Step 3: Implement enums, migration, model casts/helpers, and factory states**

Create backed enums with exact cases and values:

```php
enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
}

enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
```

Add indexed string columns with database defaults `customer` and `active`. Do not add either field to `$fillable`. Add enum casts and the two boolean helper methods. Factory states must use enum values.

- [ ] **Step 4: Run focused and complete tests**

Run:

```powershell
php artisan test tests/Feature/UserRoleAndStatusTest.php
php artisan test
```

Expected: focused tests and the complete suite pass.

- [ ] **Step 5: Commit**

```powershell
git add app/Enums app/Models/User.php database/factories/UserFactory.php database/migrations tests/Feature/UserRoleAndStatusTest.php
git commit -m "feat: add typed user roles and statuses"
```

### Task 2: Disabled Account Authentication Enforcement

**Files:**
- Create: `app/Actions/Fortify/AuthenticateUser.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Test: `tests/Feature/DisabledAccountAuthenticationTest.php`

**Interfaces:**
- Consumes: `UserStatus::Active`, Fortify username configuration, Laravel `Hash`
- Produces: `AuthenticateUser::__invoke(Request $request): ?User` registered through `Fortify::authenticateUsing(...)`

- [ ] **Step 1: Write failing authentication tests**

Create tests named:

- `active_user_can_authenticate_with_correct_password`
- `disabled_user_cannot_authenticate_with_correct_password`
- `disabled_account_uses_the_generic_failed_authentication_message`

The disabled case must POST to `/login`, assert the user is a guest, assert a validation error on Fortify's username field, and assert the message equals the invalid-password case rather than revealing account status.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/DisabledAccountAuthenticationTest.php`

Expected: the disabled-user test fails because Jetstream currently authenticates that account.

- [ ] **Step 3: Implement the Fortify authentication action**

Normalize the submitted email consistently with Fortify, retrieve the user by email, call `Hash::check` for the submitted password, require `UserStatus::Active`, and return the user or `null`. Register the invokable action in `FortifyServiceProvider::boot()`.

- [ ] **Step 4: Run focused authentication suites**

Run:

```powershell
php artisan test tests/Feature/DisabledAccountAuthenticationTest.php
php artisan test tests/Feature/AuthenticationTest.php tests/Feature/PasswordResetTest.php tests/Feature/TwoFactorAuthenticationSettingsTest.php
```

Expected: all focused authentication and recovery tests pass.

- [ ] **Step 5: Commit**

```powershell
git add app/Actions/Fortify/AuthenticateUser.php app/Providers/FortifyServiceProvider.php tests/Feature/DisabledAccountAuthenticationTest.php
git commit -m "feat: block disabled account authentication"
```

### Task 3: Protected Administration Boundary

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Create: `resources/views/admin/dashboard.blade.php`
- Create: `routes/admin.php`
- Modify: `bootstrap/app.php`
- Modify: `resources/views/navigation-menu.blade.php`
- Test: `tests/Feature/AdminAccessTest.php`

**Interfaces:**
- Consumes: `User::isAdmin(): bool`, Laravel authentication middleware
- Produces: middleware alias `admin`, named route `admin.dashboard`, authenticated admin dashboard at `/admin`

- [ ] **Step 1: Write failing admin-boundary tests**

Create tests named:

- `guest_is_redirected_from_admin_dashboard`
- `customer_is_forbidden_from_admin_dashboard`
- `admin_can_view_admin_dashboard`
- `customer_navigation_does_not_render_admin_link`
- `admin_navigation_renders_admin_link`

Assert redirect to `login`, HTTP `403`, HTTP `200` with heading `Administração`, and role-dependent navigation visibility.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/AdminAccessTest.php`

Expected: FAIL because route, middleware, controller, view, and navigation link do not exist.

- [ ] **Step 3: Implement the admin boundary**

`EnsureUserIsAdmin::handle(Request $request, Closure $next): Response` must abort with `403` unless the authenticated user is an admin. Register alias `admin`, load `routes/admin.php` from `bootstrap/app.php`, group routes under `auth:sanctum`, Jetstream auth-session, `verified`, and `admin`, and render a minimal Jetstream-layout dashboard. Render the navigation link only when `auth()->user()->isAdmin()`.

- [ ] **Step 4: Run focused and complete tests**

Run:

```powershell
php artisan test tests/Feature/AdminAccessTest.php
php artisan test
```

Expected: focused tests and the complete suite pass.

- [ ] **Step 5: Commit**

```powershell
git add app/Http bootstrap/app.php resources/views/admin resources/views/navigation-menu.blade.php routes/admin.php tests/Feature/AdminAccessTest.php
git commit -m "feat: protect administration routes"
```

### Task 4: HTTP Security Headers

**Files:**
- Create: `app/Http/Middleware/AddSecurityHeaders.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/SecurityHeadersTest.php`

**Interfaces:**
- Consumes: Laravel web middleware stack and Symfony response headers
- Produces: `AddSecurityHeaders::handle(Request $request, Closure $next): Response` applied to web responses

- [ ] **Step 1: Write failing header tests**

Create tests named:

- `successful_web_responses_include_baseline_security_headers`
- `authentication_redirects_include_baseline_security_headers`

Assert exact headers:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/SecurityHeadersTest.php`

Expected: FAIL because the headers are absent.

- [ ] **Step 3: Implement and append the middleware to the web group**

The middleware must call the next handler first, then set the four exact headers on the response. Register it with `$middleware->web(append: [...])`. Do not add a CSP in this increment because Livewire/Vite nonce handling is deferred to the final hardening increment.

- [ ] **Step 4: Run focused and complete tests**

Run:

```powershell
php artisan test tests/Feature/SecurityHeadersTest.php
php artisan test
```

Expected: focused tests and complete suite pass without changing response content.

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Middleware/AddSecurityHeaders.php bootstrap/app.php tests/Feature/SecurityHeadersTest.php
git commit -m "feat: add baseline HTTP security headers"
```

### Task 5: Argon2id Password Hashing and Increment Verification

**Files:**
- Create: `config/hashing.php`
- Modify: `.env.example`
- Test: `tests/Feature/ArgonPasswordHashingTest.php`

**Interfaces:**
- Consumes: Laravel Hash manager and Jetstream registration/password-update actions
- Produces: default hashing driver `argon`, documented `HASH_DRIVER=argon`, Argon2id hashes for newly registered and updated passwords

- [ ] **Step 1: Write failing password-hashing tests**

Create tests named:

- `newly_registered_password_uses_argon2id`
- `updated_password_uses_argon2id`

Exercise the public registration and authenticated password-update endpoints. Assert stored hashes begin with `$argon2id$` and `Hash::check` succeeds; never print or snapshot the password/hash.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/ArgonPasswordHashingTest.php`

Expected: FAIL because current hashes begin with bcrypt `$2y$`.

- [ ] **Step 3: Configure Argon2id explicitly**

Create Laravel hashing configuration with default `env('HASH_DRIVER', 'argon')`, retain Laravel's bcrypt options for verification compatibility, configure Argon memory `65536`, threads `1`, time `4`, and rehash-on-login. Add `HASH_DRIVER=argon` to `.env.example`; do not modify or commit `.env`.

- [ ] **Step 4: Run all verification commands**

Run:

```powershell
php artisan config:clear
php artisan test tests/Feature/ArgonPasswordHashingTest.php
php artisan test
php artisan migrate:status
composer validate --strict
composer audit
npm.cmd audit
npm.cmd run build
```

Expected: both Argon2id tests and the complete suite pass; migrations are `Ran`; manifest is valid; audits report no blocking vulnerability; frontend build succeeds.

- [ ] **Step 5: Commit**

```powershell
git add config/hashing.php .env.example tests/Feature/ArgonPasswordHashingTest.php
git commit -m "feat: use Argon2id password hashing"
```
