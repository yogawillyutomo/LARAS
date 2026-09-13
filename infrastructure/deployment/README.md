# Deployment

LARAS production deployment guidance now lives in this directory.

Start here:

- `PRODUCTION_FOUNDATION.md` — topology, release layout, environment contract, deploy sequence, smoke tests, and S5.6 governance;
- `BACKUP_RESTORE.md` — PostgreSQL and persistent attachment backup/restore procedure;
- `ROLLBACK.md` — immutable-release and symlink rollback procedure;
- `PRODUCTION_UAT_CHECKLIST.md` — evidence checklist for the first server deployment;
- `../../apps/api/.env.production.example` — production environment template with no secrets;
- `../nginx/laras-api-http-bootstrap.conf.example` — HTTP-only first-certificate bootstrap;
- `../nginx/laras-api.conf.example` — normal Nginx/PHP-FPM HTTPS API vhost.

## Current scope

This is a **production-like UAT foundation**, not a declaration that S5.6 is production-ready.

The first API runtime should deploy the exact S5.6 code tree plus this approved deployment-foundation tranche so the Vercel frontend and Laravel API expose compatible contracts. PR #90 still requires browser/privacy/auth/PDF/physical-printer/real-phone QR evidence before merge.

Redis, queue workers, scheduler services, Docker topology, centralized observability, and off-server backup automation are deliberately deferred until there is a real workload or an explicit operational requirement. Until a queue worker is intentionally deployed, production uses the synchronous queue driver so work cannot silently accumulate unprocessed.
