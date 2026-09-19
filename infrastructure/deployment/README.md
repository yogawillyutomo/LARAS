# LARAS Deployment

Production and production-like UAT deployment guidance for **LARAS — Laboratory Management Platform** lives in this directory.

Start here:

- `RELEASE_DEPLOYMENT_RUNBOOK.md` — operator-oriented, step-by-step release/deploy procedure from VPS preparation through DNS/TLS, exact-SHA release activation, smoke tests, Vercel handoff, normal releases, and rollback;
- `PRODUCTION_FOUNDATION.md` — topology, release layout, environment contract, deploy sequence, smoke tests, and rollout governance;
- `BACKUP_RESTORE.md` — PostgreSQL and persistent attachment backup/restore procedure;
- `ROLLBACK.md` — immutable-release and symlink rollback procedure;
- `PRODUCTION_UAT_CHECKLIST.md` — evidence checklist for the first server deployment;
- `RUNTIME_TROUBLESHOOTING.md` — production/runtime diagnosis and safe recovery for API reachability, Laravel 500s, `tempnam()`/Blade filesystem failures, debug exposure, storage/cache permissions, and post-repair smoke tests;
- `../../apps/api/.env.production.example` — production environment template with no secrets;
- `../nginx/laras-api-http-bootstrap.conf.example` — HTTP-only first-certificate bootstrap;
- `../nginx/laras-api.conf.example` — normal Nginx/PHP-FPM HTTPS API vhost.

## Current scope

The deployment foundation was merged with S5.6. That merge proves the code/config tranche passed its automated gates; it does **not** by itself establish production readiness.

The next server deployment must use a verified post-rebrand `main` and record runtime evidence against the real environment. Production acceptance still requires the locked browser/privacy/auth/PDF/physical-printer/real-phone QR checks.

The durable public product origin is:

`https://laras.bakaranproject.com`

The frontend host, API host, VPS, and other infrastructure may change later, but already-issued physical QR labels must continue to resolve through that canonical public hostname and `/q/<public-uuid>` route.

## Repository identity

The repository has been administratively renamed to **`yogawillyutomo/LARAS`**. Active deployment scripts, developer remotes, and integrations should use:

`https://github.com/yogawillyutomo/LARAS.git`

Do not rely on the legacy `SMARTLAB` repository redirect for new deployment automation.

## Deferred infrastructure

Redis, queue workers, scheduler services, Docker topology, centralized observability, and off-server backup automation remain deliberately deferred until there is a demonstrated workload or an explicit operational requirement. Until a queue worker is intentionally deployed, production uses the synchronous queue driver so work cannot silently accumulate unprocessed.
