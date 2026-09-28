# Laravel Commerce Bootstrap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bootstrap and verify a Laravel 12 monolith with PHP 8.4, MySQL, Jetstream Livewire, and Tailwind CSS directly in the repository root.

**Architecture:** Start from the official Laravel 12 application skeleton, then layer Jetstream's Livewire scaffolding onto it. Preserve the existing Git metadata and design documents by generating the skeleton in a disposable system-temporary directory and copying its contents into the repository root; the temporary directory is removed after the copy, so the finished application has no additional project subdirectory.

**Tech Stack:** PHP 8.4, Laravel 12, Composer, MySQL, Laravel Jetstream, Livewire, Tailwind CSS, Vite, Node.js, npm

**Spec:** `docs/superpowers/specs/2026-09-28-laravel-bootstrap-design.md`

## Global Constraints

- Install Laravel major version 12 and run it with PHP 8.4.
- Build one monolithic Laravel application directly in the repository root.
- Use MySQL at `127.0.0.1:3306`, database `comercio_db`, username `root`, and only the password explicitly supplied by the user.
- Store database credentials only in the Git-ignored `.env`; never commit them.
- Install Jetstream with its Livewire stack and the Tailwind CSS integration it scaffolds.
- Do not enable Jetstream teams, API support, or dark mode.
- Do not implement commerce models, schema, pages, rules, or other domain functionality.
- Use `npm.cmd` on this Windows host because PowerShell blocks the `npm.ps1` shim.

## Review Focus

- A non-empty repository root containing `.git` and `docs` must retain both while Laravel files land at the root, with no nested application directory left behind; Task 1 verifies this.
- Dependency resolution must select Laravel 12 rather than the current latest major; Task 1 asserts the reported framework major.
- Runtime must be PHP 8.4 even if another PHP installation is available; Task 1 asserts the active CLI version.
- The MySQL connection must use the approved host, port, database, username, and user-supplied password without exposing the password to Git; Task 3 checks resolved configuration and Git ignore status.
- The application must answer an HTTP request after compiled assets and MySQL migrations are available; Task 3 starts a temporary hidden server and asserts a successful response.

---

### Task 1: Scaffold Laravel 12 in the Repository Root

**Files:**
- Create: standard Laravel 12 application skeleton in the repository root (`app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `artisan`, `composer.json`, and related root files)
- Preserve: `.git/`
- Preserve: `docs/superpowers/specs/2026-09-28-laravel-bootstrap-design.md`
- Preserve: `docs/superpowers/plans/2026-09-28-laravel-bootstrap.md`

**Interfaces:**
- Consumes: approved repository root and PHP/Composer executables available on `PATH`
- Produces: Laravel 12 application root with installed Composer dependencies, `.env`, generated `APP_KEY`, and runnable `php artisan`

- [ ] **Step 1: Verify the preconditions and absence of an existing application**

Run:

```powershell
php -r "exit(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4 ? 0 : 1);"
if (Test-Path artisan) { throw 'An application already exists in the repository root.' }
git status --short --branch
```

Expected: PHP check exits `0`, `artisan` is absent, and Git reports only the planning state expected for this plan.

- [ ] **Step 2: Generate the official Laravel 12 skeleton in a disposable directory and copy it into the root**

Run this as one PowerShell session so the temporary path cannot be lost between commands:

```powershell
$repoRoot = (Get-Location).Path
$tempRoot = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
$bootstrapDir = Join-Path $tempRoot ("sistema-comercio-" + [guid]::NewGuid().ToString("N"))
try {
    composer create-project laravel/laravel $bootstrapDir "^12.0" --prefer-dist --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "Composer create-project failed." }

    $collisions = Get-ChildItem -LiteralPath $bootstrapDir -Force | Where-Object {
        Test-Path -LiteralPath (Join-Path $repoRoot $_.Name)
    }
    if ($collisions) { throw "Generated files collide with repository content: $($collisions.Name -join ', ')" }

    Get-ChildItem -LiteralPath $bootstrapDir -Force | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $repoRoot -Recurse
    }
} finally {
    $resolvedBootstrap = [IO.Path]::GetFullPath($bootstrapDir)
    if ($resolvedBootstrap.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase) -and
        $resolvedBootstrap -ne $tempRoot -and
        (Test-Path -LiteralPath $resolvedBootstrap)) {
        Remove-Item -LiteralPath $resolvedBootstrap -Recurse -Force
    }
}
```

Expected: Composer completes successfully; the temporary directory is removed; `artisan`, `composer.json`, `app/`, and the other standard Laravel files exist directly in the repository root; `.git/` and `docs/` remain intact; no nested Laravel project directory remains.

- [ ] **Step 3: Verify framework and runtime versions**

Run:

```powershell
php artisan --version
php -r "echo PHP_VERSION, PHP_EOL;"
composer show laravel/framework --format=json
```

Expected: Artisan reports `Laravel Framework 12.x`; PHP reports `8.4.x`; Composer reports `laravel/framework` with a `v12.x` installed version.

- [ ] **Step 4: Run the pristine Laravel test suite**

Run: `php artisan test`

Expected: all default Laravel tests pass.

- [ ] **Step 5: Commit the Laravel skeleton**

```powershell
git add . ':!.env'
git commit -m "chore: scaffold Laravel 12 application"
```

Expected: the scaffold is committed and `.env` is not tracked.

### Task 2: Install Jetstream Livewire and Frontend Dependencies

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `package.json`
- Modify: `package-lock.json`
- Modify: Jetstream-published application, provider, configuration, migration, route, test, Blade, JavaScript, and CSS files
- Create: Jetstream Livewire/Tailwind scaffolding generated by `php artisan jetstream:install livewire`

**Interfaces:**
- Consumes: runnable Laravel 12 application and generated `APP_KEY` from Task 1
- Produces: Jetstream authentication/profile scaffolding using Livewire and Tailwind, installed Composer packages, installed npm packages, and production assets in `public/build/`

- [ ] **Step 1: Add Jetstream and install the Livewire stack**

Run:

```powershell
composer require laravel/jetstream --no-interaction
php artisan jetstream:install livewire --no-interaction
```

Expected: Composer succeeds; Artisan reports that Jetstream scaffolding was installed; no teams, API, or dark-mode flags are used.

- [ ] **Step 2: Assert the selected backend stack**

Run:

```powershell
composer show laravel/jetstream
composer show livewire/livewire
php artisan route:list --name=login
```

Expected: both packages are installed and the named `login` route is present.

- [ ] **Step 3: Install and build frontend dependencies**

Run:

```powershell
npm.cmd install
npm.cmd run build
```

Expected: npm installation succeeds and Vite completes a production build into `public/build/` without errors.

- [ ] **Step 4: Run the Jetstream-expanded test suite**

Run: `php artisan test`

Expected: all Laravel and Jetstream tests pass.

- [ ] **Step 5: Commit the Jetstream Livewire scaffold**

```powershell
git add . ':!.env'
git commit -m "feat: install Jetstream Livewire stack"
```

Expected: all versioned backend/frontend scaffold files and lockfiles are committed; `.env`, `vendor/`, and `node_modules/` remain untracked or ignored.

### Task 3: Configure MySQL, Migrate, and Verify Startup

**Files:**
- Modify locally only: `.env`
- Verify: `.gitignore`
- Create externally if absent: MySQL database `comercio_db`

**Interfaces:**
- Consumes: approved MySQL endpoint and credentials plus the Jetstream application from Task 2
- Produces: local MySQL-backed runtime configuration, migrated default schema, green test/build checks, and a verified HTTP response

- [ ] **Step 1: Update only the approved database entries in `.env`**

Use a scoped PowerShell replacement that changes `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to the approved values. Use `apply_patch` for the file edit, and do not print the password in command output.

Expected: all six entries occur exactly once in `.env`; unrelated environment settings remain unchanged.

- [ ] **Step 2: Verify resolved non-secret database configuration and credential isolation**

Run:

```powershell
php artisan config:clear
php artisan tinker --execute="dump(config('database.default'), config('database.connections.mysql.host'), config('database.connections.mysql.port'), config('database.connections.mysql.database'), config('database.connections.mysql.username'));"
git check-ignore .env
git ls-files --error-unmatch .env
```

Expected: resolved values are `mysql`, `127.0.0.1`, `3306`, `comercio_db`, and `root`; `git check-ignore` identifies `.env`; `git ls-files --error-unmatch .env` fails because the file is not tracked. Never dump the password.

- [ ] **Step 3: Ensure the approved database exists and run default migrations**

Use Laravel's resolved configuration so the password is not repeated or printed in the command:

```powershell
php artisan tinker --execute="`$db = config('database.connections.mysql'); `$pdo = new PDO('mysql:host=' . `$db['host'] . ';port=' . `$db['port'] . ';charset=utf8mb4', `$db['username'], `$db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); `$pdo->exec('CREATE DATABASE IF NOT EXISTS comercio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');"
```

Then run:

```powershell
php artisan migrate --force
php artisan migrate:status
```

Expected: the database creation/check succeeds; every Laravel and Jetstream default migration is marked `Ran`; no commerce-specific table exists.

- [ ] **Step 4: Run final automated and asset verification**

Run:

```powershell
php artisan test
npm.cmd run build
```

Expected: the complete test suite passes and the production frontend build succeeds.

- [ ] **Step 5: Start the application temporarily and verify HTTP**

Start `php artisan serve --host=127.0.0.1 --port=8000` with `Start-Process -WindowStyle Hidden -PassThru`, redirect output to task-local log files, poll `http://127.0.0.1:8000/` for at most 30 seconds, assert an HTTP `200` response, and stop only the captured process in a `finally` block.

Expected: the root page returns HTTP `200`; the exact captured server process is stopped even if verification fails.

- [ ] **Step 6: Confirm the final repository state**

Run:

```powershell
php artisan about --only=environment
git status --short --branch
git check-ignore .env vendor node_modules
```

Expected: Laravel reports a local environment; the branch has no uncommitted versioned changes; `.env`, `vendor/`, and `node_modules/` are ignored; the application remains directly at the repository root.
