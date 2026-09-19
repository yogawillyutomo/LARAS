# LARAS API Rollback Runbook

Application rollback is designed around immutable release directories and the `/var/www/laras-api/current` symlink.

## Principle

A normal rollback changes only the application release symlink. It does **not** automatically reverse database migrations or delete new data.

For that reason, normal release migrations must remain backward-compatible with at least the immediately previous application release. Destructive schema changes require a separate expand/contract migration plan.

## Before every release

Record:

- release Git SHA;
- previous known-good Git SHA;
- database backup filename;
- migration list/status before and after deploy;
- operator and timestamp;
- smoke-test result.

Keep the previous release directory until the new release is accepted.

## Fast application rollback

Assume:

```text
/var/www/laras-api/current -> /var/www/laras-api/releases/<new-sha>
/var/www/laras-api/releases/<old-sha>
```

Verify the old release still has valid links to the shared `.env` and persistent `storage`, then atomically switch:

```bash
ln -sfn "/var/www/laras-api/releases/<old-sha>" /var/www/laras-api/current.next
mv -Tf /var/www/laras-api/current.next /var/www/laras-api/current
```

Then, from the restored release's `apps/api` directory:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Reload PHP-FPM only when necessary for opcode/process refresh. Do not restart PostgreSQL as part of application rollback.

## Validate after rollback

Run:

1. `GET https://api-laras.bakaranproject.com/up`;
2. unauthenticated `/api/v1/me` and confirm JSON `401 UNAUTHENTICATED`;
3. credentialed CORS check from `https://laras.bakaranproject.com`;
4. login/session smoke test in the browser;
5. one representative read flow (Laboratory/Device/Asset);
6. inspect Laravel and Nginx logs for new 5xx errors.

## When fast rollback is NOT safe

Stop and treat the event as an incident if:

- the new release ran a migration that the old application cannot understand;
- data was irreversibly transformed or deleted;
- the `APP_KEY` or other persistent secrets changed;
- the persistent storage layout was changed incompatibly;
- rollback would reintroduce a known security vulnerability;
- the failure is actually database/storage corruption rather than application code.

In those cases, use the backup/restore runbook and an explicit recovery plan rather than blindly switching the symlink.

## Frontend/API compatibility

The Vercel frontend and Laravel API must use compatible contracts. During S5.6 runtime UAT, both sides must represent the same S5.6 code tree. Do not roll the API back to pre-S5.6 `main` while leaving the S5.6 frontend active if the frontend depends on S5.6 API routes.
