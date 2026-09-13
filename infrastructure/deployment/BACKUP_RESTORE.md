# LARAS PostgreSQL Backup and Restore Runbook

This runbook covers the initial single-VPS/UAT operating model. Database credentials must be supplied through PostgreSQL environment variables or `.pgpass`; never place passwords in shell history, scripts, or Git.

## Backup prerequisites

Set or export:

```bash
export PGHOST=127.0.0.1
export PGPORT=5432
export PGDATABASE=laras
export PGUSER=laras
```

Authentication should use a server-owned `.pgpass` with restrictive permissions (`chmod 600`).

Create a backup directory readable only by the deployment/backup operator:

```bash
install -d -m 700 /var/www/laras-api/backups
```

## Pre-deploy database backup

Use PostgreSQL custom format so restore can be inspected and selectively controlled:

```bash
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
pg_dump \
  --format=custom \
  --no-owner \
  --no-acl \
  --file="/var/www/laras-api/backups/laras-${STAMP}.dump"
```

Then verify the archive can be listed:

```bash
pg_restore --list "/var/www/laras-api/backups/laras-${STAMP}.dump" >/dev/null
```

A backup command succeeding is not the same as a tested restore.

## Persistent attachment backup

Activity Report attachments currently use Laravel local/private storage. Back up the persistent shared storage independently of the database:

```bash
tar -C /var/www/laras-api/shared \
  -czf "/var/www/laras-api/backups/laras-storage-${STAMP}.tar.gz" \
  storage
```

Database and storage backups from the same deployment window should be retained together.

## Restore drill

Restore drills should use a separate temporary database first. Never overwrite the live database just to test a backup.

Example:

```bash
createdb laras_restore_test
pg_restore \
  --clean \
  --if-exists \
  --no-owner \
  --no-acl \
  --dbname=laras_restore_test \
  /var/www/laras-api/backups/<backup-file>.dump
```

After restore:

1. confirm migrations/tables exist;
2. confirm representative Schools, memberships, Laboratories, Devices and Assets are readable;
3. verify the restored row counts are plausible;
4. run API smoke tests against an isolated application instance if available;
5. drop the temporary database after the drill.

## Emergency live restore

A live restore is a destructive operational action and is not part of normal deploy/rollback.

Before a live restore:

1. place the API in maintenance mode or otherwise stop writes;
2. take one final snapshot of the current database even if it is believed broken;
3. record the exact backup chosen and the reason;
4. verify attachment-storage consistency for the target time window;
5. restore under an explicit incident/change record;
6. run migrations only if the restored schema requires them and the target application SHA is known;
7. run the full smoke-test set before reopening writes.

Do not combine an emergency database restore with an unreviewed application upgrade.

## Retention

For UAT, keep at least:

- the backup immediately before every migration-bearing deploy;
- seven daily backups while active UAT is running;
- one known-good backup paired with each release promoted as a stable checkpoint.

Longer production retention and off-server encrypted backup are required before real school data becomes business-critical.
