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

Authentication should use a server-owned `.pgpass` with restrictive permissions (`chmod 600`). The LARAS application role should not be a PostgreSQL superuser and should not need `CREATEDB` or `CREATEROLE`.

Create a backup directory readable only by the deployment/backup operator:

```bash
install -d -m 700 /var/www/laras-api/backups
```

## Pre-deploy database backup

Use PostgreSQL custom format so restore can be inspected and selectively controlled:

```bash
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DUMP="/var/www/laras-api/backups/laras-${STAMP}.dump"

pg_dump \
  --format=custom \
  --no-owner \
  --no-acl \
  --file="$DUMP"

pg_restore --list "$DUMP" >/dev/null
sha256sum "$DUMP" > "${DUMP}.sha256"
sha256sum -c "${DUMP}.sha256"
```

A backup command succeeding is not the same as a tested restore. The checksum detects file corruption; it does not replace a restore drill.

## Persistent attachment backup

Activity Report attachments currently use Laravel local/private storage. Back up the persistent shared storage independently of the database:

```bash
STORAGE_ARCHIVE="/var/www/laras-api/backups/laras-storage-${STAMP}.tar.gz"

tar -C /var/www/laras-api/shared \
  -czf "$STORAGE_ARCHIVE" \
  storage

tar -tzf "$STORAGE_ARCHIVE" >/dev/null
sha256sum "$STORAGE_ARCHIVE" > "${STORAGE_ARCHIVE}.sha256"
sha256sum -c "${STORAGE_ARCHIVE}.sha256"
```

Database and storage backups from the same deployment window should be retained together.

## Restore drill

Restore drills should use a separate temporary database first. Never overwrite the live database just to test a backup. Because the LARAS application role should not have `CREATEDB`, create/drop the temporary database through the local PostgreSQL administrator (or the equivalent DBA workflow if PostgreSQL is remote):

```bash
sha256sum -c /var/www/laras-api/backups/<backup-file>.dump.sha256
sudo -u postgres createdb --owner=laras laras_restore_test

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
5. drop the temporary database with `sudo -u postgres dropdb laras_restore_test` after the drill.

## Persistent-storage restore drill

Validate the matching storage archive without touching live storage:

```bash
sha256sum -c /var/www/laras-api/backups/<storage-file>.tar.gz.sha256
RESTORE_DIR="$(mktemp -d)"
tar -xzf /var/www/laras-api/backups/<storage-file>.tar.gz -C "$RESTORE_DIR"
test -d "$RESTORE_DIR/storage/app/private"
rm -rf "$RESTORE_DIR"
```

For a real storage restore, stop application writes first, preserve the current `shared/storage` as an incident snapshot, extract the selected matching archive into `/var/www/laras-api/shared`, then restore the expected owner/group and permissions before reopening the API. Never overwrite live storage merely to test an archive.

## Emergency live restore

A live restore is a destructive operational action and is not part of normal deploy/rollback.

Before a live restore:

1. place the API in maintenance mode or otherwise stop writes;
2. take one final snapshot of the current database and persistent storage even if they are believed broken;
3. record the exact database + storage backup pair chosen and the reason;
4. verify both SHA-256 checksum files;
5. verify attachment-storage consistency for the target time window;
6. restore under an explicit incident/change record;
7. run migrations only if the restored schema requires them and the target application SHA is known;
8. restore storage ownership/permissions deliberately;
9. run the full smoke-test set before reopening writes.

Do not combine an emergency database restore with an unreviewed application upgrade.

## Retention

For UAT, keep at least:

- the backup immediately before every migration-bearing deploy;
- seven daily backups while active UAT is running;
- one known-good backup paired with each release promoted as a stable checkpoint.

Same-host backups protect against bad migrations/operator mistakes but not total VPS/disk loss. Encrypted off-server backup and a tested recovery copy are required before real school data becomes business-critical.
