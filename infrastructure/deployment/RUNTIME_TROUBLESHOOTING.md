# LARAS Runtime Troubleshooting

Operational troubleshooting guidance for the deployed **LARAS Laravel API**.

Canonical frontend origin:

`https://laras.bakaranproject.com`

Canonical API origin:

`https://api-laras.bakaranproject.com`

This document is a recovery/diagnostic runbook. It does **not** replace the normal release procedure in `RELEASE_DEPLOYMENT_RUNBOOK.md`.

---

## 1. Triage principle

Do not guess the failing layer.

Follow the request path in order:

```text
Browser
  ↓
Vercel frontend
  ↓
DNS / TLS
  ↓
Nginx
  ↓
PHP-FPM
  ↓
Laravel bootstrap
  ↓
Database / session / cache / storage
```

A change in the user-facing error is useful evidence.

For example:

- frontend says **"Server mengembalikan respons yang tidak dikenali"**:
  - often means the browser reached something that returned HTML/non-JSON where JSON was expected;
  - common examples are an empty/wrong `VITE_API_ORIGIN` or a proxy/fallback page.

- frontend says **"Layanan autentikasi sedang tidak dapat dijangkau"**:
  - the browser-side API request failed at the network layer or received a server-side failure that the auth layer classifies as unavailable.

Always verify the API directly before changing frontend code.

---

## 2. First API probes

Run from a client outside the VPS:

```bash
curl -i https://api-laras.bakaranproject.com/up
```

Then:

```bash
curl -i \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Expected healthy unauthenticated behavior:

```text
/up
→ HTTP 200

/api/v1/me
→ HTTP 401
→ Content-Type: application/json
→ JSON code: UNAUTHENTICATED
```

Interpretation:

| Observation | Likely layer |
| --- | --- |
| DNS resolution error | DNS |
| certificate/hostname error | TLS |
| Nginx default/404 page | Nginx vhost/routing |
| Laravel/Symfony 500 page | request reached Laravel; investigate PHP/Laravel runtime |
| JSON 401 from `/api/v1/me` | API path and unauthenticated contract are healthy |

---

## 3. Symptom: HTTP 500 with `tempnam()` / Blade compiler

A production request may reach Laravel but fail with an exception similar to:

```text
tempnam(): file created in the system's temporary directory
```

with a stack involving:

```text
Illuminate\Filesystem\Filesystem
Illuminate\View\Compilers\BladeCompiler
```

### Important interpretation

This symptom strongly suggests that Laravel/PHP is unable to create a temporary or compiled-view file at the expected application path.

Common causes include:

- `storage/framework/views` missing;
- `storage/framework/views` not writable by PHP-FPM;
- another required `storage/framework/*` directory missing/not writable;
- `bootstrap/cache` missing/not writable;
- active release/storage symlinks pointing somewhere unexpected;
- owner/group drift after deploy, copy, restore, or manual file operations.

Do **not** declare the root cause confirmed until the active path and write permissions are checked.

---

## 4. Security first: public stack traces

If an external browser can see a detailed Symfony/Laravel exception page, internal paths and stack details may be exposed.

Verify the active production environment immediately.

From the **active Laravel application directory**:

```bash
grep -E '^(APP_ENV|APP_DEBUG|APP_URL)=' .env
```

Production target:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api-laras.bakaranproject.com
```

After correcting environment values:

```bash
php artisan optimize:clear
php artisan config:cache
```

Then reload PHP-FPM if required:

```bash
sudo systemctl reload php8.3-fpm
```

Never leave `APP_DEBUG=true` enabled on the public production API.

---

## 5. Identify the active runtime path

Do not blindly assume the documented immutable-release layout is already active.

The target layout is:

```text
/var/www/laras-api/current/apps/api
```

An older runtime may still exist at a different location, for example:

```text
/var/www/laras/apps/api
```

Diagnose the real active path first.

Useful checks:

```bash
sudo nginx -T 2>/dev/null | grep -nE 'server_name|root .*/apps/api/public'
```

If the immutable-release layout is active:

```bash
readlink -f /var/www/laras-api/current
```

From the active API directory:

```bash
pwd
php artisan about
```

Repair the **active runtime** first. Migrating an older deployment layout to the target immutable-release structure is a separate operational task and should not be mixed into emergency recovery unless required.

---

## 6. Identify PHP-FPM user/group

The web process must be able to write the Laravel runtime directories.

Typical PHP-FPM pool:

```bash
grep -E '^(user|group)\s*=' /etc/php/8.3/fpm/pool.d/www.conf
```

A common Ubuntu/Debian result is:

```text
user = www-data
group = www-data
```

Do not assume this value on every server.

---

## 7. Inspect Laravel writable directories

From the active Laravel API directory:

```bash
ls -ld storage
ls -ld storage/framework
ls -ld storage/framework/cache
ls -ld storage/framework/cache/data
ls -ld storage/framework/sessions
ls -ld storage/framework/views
ls -ld storage/logs
ls -ld bootstrap/cache
```

Check symlinks as well:

```bash
readlink -f storage || true
readlink -f .env || true
```

For the target immutable-release layout, expected shared storage is:

```text
/var/www/laras-api/shared/storage
```

---

## 8. Prove write access

If PHP-FPM uses `www-data`, test that identity directly.

From the active Laravel API directory:

```bash
sudo -u www-data test -w storage/framework/views \
  && echo "views writable" \
  || echo "views NOT writable"

sudo -u www-data test -w storage/framework/cache/data \
  && echo "cache writable" \
  || echo "cache NOT writable"

sudo -u www-data test -w storage/framework/sessions \
  && echo "sessions writable" \
  || echo "sessions NOT writable"

sudo -u www-data test -w storage/logs \
  && echo "logs writable" \
  || echo "logs NOT writable"

sudo -u www-data test -w bootstrap/cache \
  && echo "bootstrap/cache writable" \
  || echo "bootstrap/cache NOT writable"
```

Optionally prove an actual create/delete operation in the affected directory:

```bash
sudo -u www-data sh -c 'touch storage/framework/views/.laras-write-test && rm storage/framework/views/.laras-write-test'
```

Use the real PHP-FPM account when it differs from `www-data`.

---

## 9. Safe directory repair

Only after confirming the active application path and PHP-FPM group.

From the active Laravel API directory:

```bash
sudo mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
```

Example for a deploy user plus the `www-data` web group:

```bash
sudo chown -R "$USER":www-data storage bootstrap/cache
```

Set group-writable directories with setgid so newly created files inherit the web group:

```bash
sudo find storage -type d -exec chmod 2775 {} \;
sudo find storage -type f -exec chmod 664 {} \;

sudo find bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find bootstrap/cache -type f -exec chmod 664 {} \;
```

Then re-run the write-access checks from the previous section.

### Prohibited shortcut

Do **not** use:

```bash
chmod -R 777 storage bootstrap/cache
```

World-writable application directories are not an acceptable production fix.

---

## 10. Rebuild Laravel runtime caches

After fixing paths, permissions, or production environment values:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

All commands must complete without error.

If `view:cache` reproduces the `tempnam()` failure, the filesystem problem is still present.

Reload PHP-FPM if needed:

```bash
sudo systemctl reload php8.3-fpm
```

---

## 11. Re-run smoke tests

### Health

```bash
curl -i https://api-laras.bakaranproject.com/up
```

Expected:

```text
HTTP 200
```

### Unauthenticated API

```bash
curl -i \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Expected:

```text
HTTP 401
Content-Type: application/json
```

and the API error contract should include:

```text
code: UNAUTHENTICATED
```

### Allowed CORS

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Expected headers include:

```text
Access-Control-Allow-Origin: https://laras.bakaranproject.com
Access-Control-Allow-Credentials: true
```

### Sanctum CSRF

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  https://api-laras.bakaranproject.com/sanctum/csrf-cookie
```

Only after these API tests are healthy should frontend/browser UAT be repeated.

---

## 12. Frontend verification after API recovery

Production Vercel configuration must contain:

```env
VITE_API_ORIGIN=https://api-laras.bakaranproject.com
VITE_PUBLIC_SCAN_ORIGIN=https://laras.bakaranproject.com
```

Because Vite embeds `VITE_*` values at build time, changing these variables requires a production redeploy.

Then verify:

```text
https://laras.bakaranproject.com/dashboard
```

Signed-out behavior should resolve to the normal authentication flow, not:

- an invalid-response error;
- an authentication-service-unavailable error;
- a public Symfony/Laravel exception page.

---

## 13. Logs to inspect

Laravel:

```bash
tail -n 200 /var/www/laras-api/shared/storage/logs/laravel.log
```

If the server still uses a legacy runtime layout, use the actual active `storage/logs/laravel.log` path instead.

Nginx error log:

```bash
sudo tail -n 200 /var/log/nginx/laras-api.error.log
```

PHP-FPM:

```bash
sudo journalctl -u php8.3-fpm -n 200 --no-pager
```

Nginx:

```bash
sudo journalctl -u nginx -n 200 --no-pager
```

---

## 14. Evidence to record

For a runtime incident/recovery, record at minimum:

- timestamp/timezone;
- affected public URL;
- exact application/release SHA if known;
- active filesystem path;
- Nginx document root;
- PHP-FPM user/group;
- `APP_ENV`, `APP_DEBUG`, and `APP_URL` values with secrets excluded;
- permissions/ownership of `storage` and `bootstrap/cache`;
- relevant exception/error text;
- repair commands actually executed;
- `php artisan view:cache` result;
- post-repair `/up` result;
- post-repair unauthenticated `/api/v1/me` result;
- CORS/CSRF smoke-test results;
- browser UAT result.

Do not record secret values such as `APP_KEY`, database passwords, API tokens, or session secrets.

---

## 15. Recovery acceptance

The incident is not considered resolved merely because the exception page disappears.

Minimum recovery acceptance:

- public API no longer exposes debug stack traces;
- `APP_ENV=production`;
- `APP_DEBUG=false`;
- Laravel writable directories are proven writable by the PHP-FPM identity;
- `php artisan config:cache`, `route:cache`, and `view:cache` pass;
- `/up` returns HTTP 200;
- signed-out `/api/v1/me` returns the expected JSON 401 contract;
- credentialed CORS policy remains correct;
- CSRF endpoint works;
- frontend authentication flow works after production redeploy;
- no `chmod 777` or equivalent insecure workaround was introduced.

---

## 16. Relationship to deployment architecture

Emergency recovery and deployment modernization are separate concerns.

If production is currently running from a legacy path such as:

```text
/var/www/laras/apps/api
```

restore service safely on that active runtime first.

The long-term target remains the immutable exact-SHA release layout:

```text
/var/www/laras-api/
├── current -> releases/<release-sha>/
├── releases/
├── shared/
│   ├── .env
│   └── storage/
└── backups/
```

Move to that target through the normal release procedure in `RELEASE_DEPLOYMENT_RUNBOOK.md`, not through an improvised incident fix.
