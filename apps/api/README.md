# LARAS API

Laravel 13 REST API for **LARAS — Laboratory Asset & Resource Administration System**.

LARAS is the public product identity. The Composer package name and selected internal/test identifiers may intentionally retain the legacy `smartlab` namespace until a compatibility-safe migration is justified.

## Runtime and database

- PHP 8.3 or newer
- Laravel 13 with Laravel Sanctum
- PostgreSQL is the canonical development and production database
- SQLite `:memory:` is used only by the portable automated test suite

PostgreSQL is required for production and for the concurrency/invariant tests that depend on PostgreSQL locking and database constraints.

## Local setup

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configure the ignored `.env` with dedicated PostgreSQL credentials before running migrations. Never run destructive migration commands against an unknown or shared database.

## Validation

```powershell
composer validate --strict
php artisan about
php artisan route:list
php artisan test
```

Reference RBAC data can be checked safely in the isolated testing environment with:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan migrate:fresh --seed --force
```

PostgreSQL-specific concurrency gates are executed separately by CI and must remain green; portable SQLite tests do not replace those proofs.

## API contract

- `GET /api/v1/health` — public safe health response
- `GET /api/v1/me` — Sanctum-authenticated current user and active School context

The machine-readable source of truth for implemented HTTP contracts is [`packages/contracts/openapi.yaml`](../../packages/contracts/openapi.yaml), with domain-specific contracts alongside it.

## Production boundary

Production deployment guidance is maintained under [`infrastructure/deployment/`](../../infrastructure/deployment/README.md). The durable public product origin is `https://laras.bakaranproject.com`; API hosting may move independently as long as the frontend/API origin contract, CORS, TLS, authentication, and canonical QR route remain valid.
