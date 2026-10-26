# Registration password requirements Implementation Plan

> Execute inline using superpowers:executing-plans and test-driven-development. No commits or publishing.

**Goal:** Add a compact reactive requirements card between confirmation and submit, allowing natural card height; enforce exactly the advertised registration policy on the server.

**Architecture:** Extend the existing Fortify password-rule helper with a registration-specific option, preserving defaults and update/reset behavior. Extend the existing auth-validation module for local criteria and indicator updates, with no password storage or requests. Add only registration-scoped card CSS. Keep the approved shared panel and login intact.

**Spec:** User attachment 39a34ff1-32b8-4a7c-b417-a1d1b9aafb3e/Pasted text.txt.

## Global constraints

No .env, persistent DB, migrations, libraries, commercial screens, login styling or shared-panel edits. SQLite :memory: guarded disposable copy for tests. 40px controls, 16px labels, 14px helper, 32px heading maximum. No numeric requirement.

## Review focus

- Empty/mismatching confirmation never shows success; changing either password recomputes.
- Green checklist must agree with Laravel Unicode letters/cases/symbols semantics.
- Client indicators never replace authoritative server validation or expose values in logs/storage.
- Existing credentials remain usable; policy change limited to account creation.
- Natural height and scroll preserve widths, image aspect and panel proportions.

### Task 1: Enforce registration policy

- [x] Run isolated current baseline, add failing direct POST tests for missing lower/upper/special/length and boundary acceptance without digits.
- [x] Extend passwordRules for registration using cloned Password::default(), letters/mixedCase/symbols; use only in CreateNewUser. Adapt existing successful registration fixture to valid policy.
- [x] Verify direct registration, persisted hash and authentication; existing login/reset/update tests.

### Task 2: Checklist and layout

- [x] Add failing browser checks for all specified neutral, partial, complete, confirmation and submit states.
- [x] Add requirements markup within existing register form; style with DS soft/line/radius/spacing and responsive columns. No fixed card height.
- [x] Share local criteria within existing JS for checklist and password field errors, preserving unchanged login behavior.
- [x] Capture neutral/partial/complete desktop and mobile; compare login pixels; verify control height, form width, natural scroll and final card height.
- [x] Build, full guarded suite, independent read-only review and evidence report.

## Completion evidence

Baseline281/1607/same7 skipped. Final294/1672/same7 skipped, all existing case statuses preserved. New policy direct tests RED with five failures before implementation, GREEN after. Two valid-registration fixtures updated to new policy (RegistrationTest and injected-role test), with assertions preserved. Browser policy/indicators/layout PASS in seven viewports; neutral/partial/complete/error screenshots desktop/mobile. Login7/7 pixel-identical. Natural desktop card740px; no fixed height. Build, Pint and diff check passed. Independent read-only review: no actionable findings. Tests used protected disposable copy; original .env/cache/persistent DB unchanged. No commits/publishing.
