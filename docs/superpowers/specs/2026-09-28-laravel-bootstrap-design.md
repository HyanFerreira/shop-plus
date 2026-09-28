# Laravel Commerce Application Bootstrap Design

## Objective

Create the technical foundation for a monolithic commerce application directly in the repository root. This phase only bootstraps and verifies the framework; it does not implement commerce features.

## Technology Baseline

- Laravel 12
- PHP 8.4
- MySQL
- Laravel Jetstream with the Livewire stack
- Tailwind CSS through Jetstream's frontend setup
- Node.js and npm for frontend dependencies and asset compilation

## Project Layout

Laravel will be installed directly in the current, initially empty directory. No additional application subdirectory will be created. The standard Laravel directory structure and Jetstream scaffolding will be retained without adding domain-specific modules, models, migrations, pages, or business rules.

## Installation Approach

Use Composer's `create-project` command with the Laravel 12 constraint. Because the approved specification and Git metadata now occupy the repository root, generate the skeleton in a unique system-temporary directory, copy its contents into the root without replacing `.git` or `docs`, and then remove that exact temporary directory. The finished Laravel application still lives directly in the current folder, with no additional project subdirectory. This avoids depending on a separately installed Laravel CLI while preserving the versioned planning artifacts.

After Laravel is present:

1. Add Laravel Jetstream as a Composer dependency.
2. Install Jetstream using its Livewire stack.
3. Install frontend dependencies using `npm.cmd`, because PowerShell execution policy blocks the `npm.ps1` shim.
4. Build the frontend assets.

## Environment Configuration

Create `.env` from Laravel's standard environment template and generate the application key. Configure only the database connection values explicitly supplied by the user:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comercio_db
DB_USERNAME=root
DB_PASSWORD=<user-supplied password>
```

The `.env` file remains excluded from Git. The password supplied by the user is written only to that ignored local file, never to versioned documentation. No other database username or password will be substituted.

## Database Initialization

Use Laravel's database connection to run the framework and Jetstream default migrations. The target database must already exist and the supplied account must be able to connect and create tables. No commerce-specific schema will be added.

## Verification

Verification consists of:

- Confirming the application reports Laravel 12 and runs under PHP 8.4.
- Running the default automated test suite.
- Confirming frontend assets compile successfully.
- Confirming all default migrations complete against MySQL.
- Starting Laravel's development server temporarily and making a local HTTP request that receives a successful response, then stopping the server.

If the database server or target database is unavailable, installation may still complete, but the migration requirement is not considered complete until connectivity is restored and migrations succeed.

## Scope Exclusions

This phase will not implement products, inventory, sales, customers, suppliers, payments, reports, authorization roles, or any other commerce functionality. It will not add Docker, deployment automation, optional Jetstream teams, API tokens, or additional packages unless Laravel or Jetstream requires them for the approved stack.

## Success Criteria

The repository root contains a Laravel 12 monolith compatible with PHP 8.4, configured with Jetstream Livewire and Tailwind CSS. Composer and frontend dependencies are installed, default migrations exist in `comercio_db`, tests and asset compilation pass, and the application responds when started locally.
