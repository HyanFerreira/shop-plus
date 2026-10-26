# Shared authentication Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans inline. No commits, as requested.

**Goal:** Reuse the approved login panel and presentation on registration without changing login rendering.

**Architecture:** Simplify the existing auth-benefits-panel to the approved panel, without props. Keep auth-shell and share its approved presentation via auth-page--branded. Extend existing error and password components and validation module for registration, preserving login behavior and all backend rules.

**Tech Stack:** Blade, Laravel/Fortify/Jetstream, Alpine, existing Vite CSS/JS.

**Spec:** User attachment Pasted text.txt (2026-10-26); login source is visual authority.

## Global Constraints

No .env, database, migrations, backend rules, libraries or commercial-page changes. Test only under project guards with SQLite :memory:. Keep login pixel-identical, including 20px brand margin. One panel implementation and images/login.png. No automatic commits.

## Review Focus

- Untouched fields remain neutral; submit checks all fields.
- Password confirmation reacts when password changes, including server confirmed errors.
- Unicode lengths match Laravel; max lengths enforced; passwords never repopulated.
- Native validation remains available without JS; toggles and errors accessible.
- Narrow/short viewports allow natural scroll and the illustration stays inside the panel.

### Task 1: Shared panel and registration form

- [x] Capture login before at 320, 375, 768, 1024, 1280 and 1440px; run isolated baseline suite.
- [x] Add failing RegistrationPresentationTest for identical aside HTML/asset, four required icon fields, server errors, limits and confirmation errors.
- [x] Simplify auth-benefits-panel, update auth-shell shared presentation, register form and shared CSS/error/password components. Preserve login HTML layout and CSS specificity.
- [x] Run isolated registration/login/authentication tests; expected: no new failures.

### Task 2: Client validation and visual verification

- [x] Add browser checks that fail on current registration: untouched, required, name/email limits, password policy, confirmation dependency, correction, server error and toggles.
- [x] Extend existing login-validation module to registration through shared field/error attributes; keep login messages and behavior.
- [x] Capture both pages at all specified widths and short viewport. Compare login PNG pixels to before. Check 32px registration heading, 16px labels/text, 40px controls and max 416px form.
- [x] Test registration success/duplicates/invalid policy in isolated PHPUnit; run full suite and compare baseline; build and diff check.
- [x] Review diff, document files, evidence and limits in review README. No commits.

## Execution record

Executed in place, without commits. Tests and HTTP preview used a disposable filesystem copy in /tmp/shopplus-shared-auth/app because the original checkout contains a config cache rejected by the test guard. Original .env/cache remained untouched. Baseline: 275 tests / 1543 assertions / 7 skipped; final: 281 / 1607 / same 7 skipped. Seven login PNG comparisons: zero changed pixels. Browser checks and 14 final viewport captures passed. Independent read-only review: no critical/important findings; minor Unicode maxlength behavior documented in review README.
