# LARAS Production-like UAT Checklist

Use this checklist for the first API deployment that supports S5.6 runtime/QR UAT.

## Before server changes

- [ ] Record exact API Git SHA.
- [ ] Confirm the SHA belongs to the S5.6 code tree under test.
- [ ] Confirm PR #90 remains Draft and unmerged.
- [ ] Confirm `laras.bakaranproject.com` is the canonical frontend/QR origin.
- [ ] Decide the VPS public IPv4 address for `api.laras.bakaranproject.com`.
- [ ] Ensure SSH key access exists; disable password/root SSH where practical.
- [ ] Ensure system clock/NTP is healthy.

## DNS and TLS

- [ ] Create Cloudflare `A` record `api.laras` -> VPS IPv4.
- [ ] Keep Cloudflare proxy DNS-only for initial UAT.
- [ ] Verify public DNS resolves to the expected VPS.
- [ ] Issue a valid TLS certificate for `api.laras.bakaranproject.com`.
- [ ] Verify HTTP redirects to HTTPS.
- [ ] Verify the certificate hostname and chain in a normal browser/curl client.

## Runtime packages

- [ ] Nginx installed.
- [ ] PHP 8.3 CLI + PHP-FPM installed.
- [ ] Required PHP extensions for Laravel/PostgreSQL installed (`pdo_pgsql`, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, curl as required by platform/packages).
- [ ] Composer available to the deployment operator.
- [ ] PostgreSQL client tools (`psql`, `pg_dump`, `pg_restore`) installed.
- [ ] PostgreSQL server reachable only through loopback/private network.

## Filesystem

- [ ] `/var/www/laras-api/releases` exists.
- [ ] `/var/www/laras-api/shared/.env` exists and is not world-readable.
- [ ] `/var/www/laras-api/shared/storage` exists and is writable by the web/deploy group.
- [ ] `/var/www/laras-api/backups` exists with restrictive permissions.
- [ ] `current` points only to an immutable release directory.
- [ ] Nginx document root points to `current/apps/api/public`.

## Environment

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] Persistent `APP_KEY` generated and backed up securely.
- [ ] `APP_URL=https://api.laras.bakaranproject.com`.
- [ ] Unique LARAS DB user/password configured.
- [ ] `SANCTUM_STATEFUL_DOMAINS=laras.bakaranproject.com`.
- [ ] `CORS_ALLOWED_ORIGINS=https://laras.bakaranproject.com`.
- [ ] `SESSION_DOMAIN=laras.bakaranproject.com`.
- [ ] `SESSION_COOKIE=laras_session`.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] `SESSION_HTTP_ONLY=true`.
- [ ] `SESSION_SAME_SITE=lax`.
- [ ] Activity Report attachment disk points to persistent private storage.

## Database and release

- [ ] Pre-migration PostgreSQL backup created.
- [ ] Backup archive passes `pg_restore --list`.
- [ ] `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` succeeds.
- [ ] `php artisan migrate --force` succeeds.
- [ ] No production seeder is run implicitly.
- [ ] `php artisan config:cache` succeeds.
- [ ] `php artisan route:cache` succeeds.
- [ ] `php artisan view:cache` succeeds.
- [ ] `nginx -t` succeeds before reload.

## API smoke tests

- [ ] `/up` returns success.
- [ ] `/api/v1/me` without login returns JSON 401 `UNAUTHENTICATED`.
- [ ] Allowed origin receives `Access-Control-Allow-Origin: https://laras.bakaranproject.com`.
- [ ] Allowed origin receives `Access-Control-Allow-Credentials: true`.
- [ ] An arbitrary origin is not reflected.
- [ ] `/sanctum/csrf-cookie` works over HTTPS.
- [ ] Secure cookies have the expected domain/same-site behavior.

## Vercel handoff

- [ ] Production `VITE_PUBLIC_SCAN_ORIGIN=https://laras.bakaranproject.com`.
- [ ] Production `VITE_API_ORIGIN=https://api.laras.bakaranproject.com`.
- [ ] Frontend redeployed after env change.
- [ ] `/dashboard` no longer shows an invalid-response auth error.
- [ ] Signed-out browser reaches the login flow normally.
- [ ] Successful login survives navigation/refresh.

## S5.6 runtime UAT

- [ ] Public `/q/{publicId}` direct navigation works.
- [ ] Anonymous QR page exposes safe-minimal fields only.
- [ ] Unknown/revoked/malformed QR remains safe 404.
- [ ] Login return-to-QR works.
- [ ] Same-School `assets.view` handoff works.
- [ ] Cross-School/permission denial remains fail-closed.
- [ ] Label generation uses the canonical LARAS HTTPS URL.
- [ ] PDF inspection, physical print and real-phone scan gates are completed separately.

## Rollback readiness

- [ ] Previous release directory still exists.
- [ ] Previous known-good SHA is recorded.
- [ ] Backup filename is recorded.
- [ ] Operator knows the symlink rollback steps.
- [ ] No destructive migration was introduced without an explicit recovery plan.
