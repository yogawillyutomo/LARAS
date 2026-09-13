# LARAS Production Deployment Foundation

Status: **S5.6 production-like UAT foundation**  
Frontend canonical origin: `https://laras.bakaranproject.com`  
API canonical origin: `https://api.laras.bakaranproject.com`

## Purpose

This tranche prepares the Laravel API for production-like UAT without declaring S5.6 production-ready. The API deployment used for S5.6 runtime/QR UAT must be built from the same exact S5.6 code tree as the frontend contract under test.

For the current S5.6 UAT, the approved source is the exact S5.6 branch HEAD recorded by PR #90 plus this deployment-foundation tranche. After S5.6 closes and is merged, `main` becomes the normal production release source.

## Locked topology

```text
Browser / phone
    |
    +--> https://laras.bakaranproject.com
    |        Vercel React/Vite frontend
    |
    +--> https://api.laras.bakaranproject.com
             Nginx + PHP-FPM + Laravel API
                      |
                      +--> PostgreSQL
                      +--> persistent private Laravel storage
```

The frontend hostname is permanent because it is encoded in physical QR labels. The API hostname may move between servers later as long as the public API contract remains compatible.

## Initial operating model

The first UAT deployment intentionally uses a small single-VPS operating model:

- Nginx terminates TLS for `api.laras.bakaranproject.com`;
- PHP-FPM runs Laravel;
- PostgreSQL may run on the same VPS initially but MUST listen only on loopback/private interfaces;
- Laravel database sessions and database cache are retained;
- `QUEUE_CONNECTION=sync` is used while no queue worker exists, so newly introduced queued work cannot silently accumulate unprocessed;
- no Redis dependency is required for the first deployment;
- no scheduler process is required while there are no scheduled application tasks;
- Activity Report private attachments remain on Laravel local storage, but that storage MUST be persistent and included in backup;
- Cloudflare is DNS-only for the API during initial UAT unless a later, tested policy changes this.

## Production environment contract

Copy `apps/api/.env.production.example` to a server-only shared `.env` and replace every secret/placeholder. Never commit the real file.

Required invariants:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL=https://api.laras.bakaranproject.com`;
- generated, persistent `APP_KEY`;
- PostgreSQL credentials unique to LARAS;
- `SANCTUM_STATEFUL_DOMAINS=laras.bakaranproject.com`;
- `CORS_ALLOWED_ORIGINS=https://laras.bakaranproject.com`;
- `CORS_ALLOWED_ORIGINS` must never contain `*` while credentialed CORS is enabled;
- `SESSION_DOMAIN=laras.bakaranproject.com`;
- `SESSION_SECURE_COOKIE=true`;
- `SESSION_HTTP_ONLY=true`;
- `SESSION_SAME_SITE=lax`;
- `SESSION_COOKIE=laras_session`;
- `QUEUE_CONNECTION=sync` until a supervised queue worker is intentionally deployed;
- persistent `storage/` shared across releases;
- `APP_KEY`, DB password and all secrets are never rotated as part of ordinary deploys.

Because frontend and API are different origins, CORS is explicit and credentialed. Wildcard origins are prohibited for stateful authentication. With the current single-origin Fruitcake CORS optimization, a rejected request can still receive `Access-Control-Allow-Origin: https://laras.bakaranproject.com`; this is safe because a browser only grants CORS access when that value matches the request Origin. The API must never reflect an unapproved origin.

## Server filesystem layout

Recommended release layout:

```text
/var/www/laras-api/
  current -> releases/<release-sha>/
  releases/
    <release-sha>/
      apps/api/
  shared/
    .env
    storage/
  backups/
```

`current` is an atomic symlink. The Laravel API document root is:

```text
/var/www/laras-api/current/apps/api/public
```

The release's `apps/api/.env` should symlink to `/var/www/laras-api/shared/.env` and its `apps/api/storage` should point to `/var/www/laras-api/shared/storage`.

### First-server persistent directories

Before the first release, create the persistent Laravel storage skeleton. Ownership must be assigned to the chosen deploy user and PHP-FPM/web group for the actual server; do not blindly copy an unknown username from a runbook.

```bash
sudo install -d -m 750 /var/www/laras-api/{releases,shared,backups}
sudo install -d -m 2775 \
  /var/www/laras-api/shared/storage/app/private \
  /var/www/laras-api/shared/storage/app/public \
  /var/www/laras-api/shared/storage/framework/cache/data \
  /var/www/laras-api/shared/storage/framework/sessions \
  /var/www/laras-api/shared/storage/framework/views \
  /var/www/laras-api/shared/storage/logs
```

After choosing the server's deploy user and PHP-FPM group, set owner/group deliberately and verify the PHP-FPM user can write `shared/storage` and each release's `apps/api/bootstrap/cache`. Never use `chmod 777`.

## Release sequence

1. Verify the requested Git commit SHA and deployment environment.
2. Create and checksum a PostgreSQL backup before schema changes.
3. Materialize the exact release SHA into a new immutable release directory.
4. Link the shared `.env` and persistent `storage`.
5. Ensure the release-local `apps/api/bootstrap/cache` exists and is writable by the deploy/web group.
6. Run from `apps/api`:

   ```bash
   composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   composer check-platform-reqs --no-dev
   php artisan config:clear
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. Re-check `storage` and `bootstrap/cache` ownership/permissions.
8. Atomically move `current` to the new release.
9. Reload PHP-FPM only if required for opcode/process refresh; reload Nginx only after `nginx -t` succeeds.
10. Run the smoke tests below.
11. Retain at least the previous known-good application release for rollback.

Do not run seeders automatically in production. Reference/catalog seed behavior must be explicitly reviewed before any production seeding action. UAT-only seeders that refuse production must not be bypassed.

## Required smoke tests

### Health

```bash
curl --fail --show-error --silent \
  https://api.laras.bakaranproject.com/up
```

### Unauthenticated API contract

```bash
curl -i \
  -H 'Accept: application/json' \
  https://api.laras.bakaranproject.com/api/v1/me
```

Expected: HTTP `401` with JSON containing `code: UNAUTHENTICATED`, not HTML.

### Credentialed CORS contract

Allowed origin:

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  -H 'Accept: application/json' \
  https://api.laras.bakaranproject.com/api/v1/me
```

Expected headers include:

- `Access-Control-Allow-Origin: https://laras.bakaranproject.com`
- `Access-Control-Allow-Credentials: true`

Rejected-origin probe:

```bash
curl -i \
  -H 'Origin: https://evil.example' \
  -H 'Accept: application/json' \
  https://api.laras.bakaranproject.com/api/v1/me
```

The response MUST NOT contain `Access-Control-Allow-Origin: https://evil.example`. With the single allowed-origin optimization it may contain the fixed canonical LARAS origin; that still blocks `evil.example` in browsers.

### CSRF endpoint

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  https://api.laras.bakaranproject.com/sanctum/csrf-cookie
```

Expected: successful response and secure cookies scoped for the LARAS web/API relationship.

## Frontend handoff

Only after the API smoke tests pass, set the Vercel Production variables:

```text
VITE_PUBLIC_SCAN_ORIGIN=https://laras.bakaranproject.com
VITE_API_ORIGIN=https://api.laras.bakaranproject.com
```

Then redeploy the frontend because Vite environment values are compiled at build time.

## Database safety

- PostgreSQL is the primary database.
- Never expose port `5432` publicly.
- The application role should not be PostgreSQL superuser and should not have `CREATEDB`/`CREATEROLE` unless a separately justified operational requirement exists.
- Take a tested, checksummed backup before migration-bearing releases.
- Application rollback does not imply database rollback.
- Migrations intended for normal releases should be backward-compatible with the previous application release.
- A destructive migration requires an explicit expand/contract plan and separate approval.

See `BACKUP_RESTORE.md` and `ROLLBACK.md`.

## Network baseline

For the first UAT VPS, only the intended public services should be reachable from the Internet. Normally this means HTTPS/HTTP and tightly controlled SSH. PostgreSQL `5432` must not be Internet-exposed. Confirm the host firewall and provider firewall/security-group rules before loading real data.

## S5.6 gate relationship

This infrastructure work does **not** close PR #90. S5.6 still requires deployed browser/privacy/auth UAT plus PDF/physical printer/real-phone QR evidence. The deployment is the environment in which those gates can finally be proven.

S6.2 is not part of the first production-like API deployment and must not be deployed merely because its automated tests are green.
