# LARAS — Release & Deployment Runbook

## 1. Tujuan

Dokumen ini menjelaskan cara melakukan release dan deploy **LARAS API** ke VPS secara aman, repeatable, dan mudah di-rollback.

Arsitektur yang digunakan:

```text
Browser / Phone
   |
   +--> https://laras.bakaranproject.com
   |      Vercel — React/Vite frontend
   |
   +--> https://api-laras.bakaranproject.com
          VPS
          Nginx
          PHP-FPM 8.3
          Laravel API
          PostgreSQL
          Persistent Laravel storage
```

Frontend **tetap di Vercel**. Di VPS, aplikasi yang dijalankan hanya Laravel di `apps/api`.

Repository:

```text
https://github.com/yogawillyutomo/LARAS.git
```

---

# 2. Prinsip Release

Setiap deployment harus berasal dari **exact Git commit SHA**.

Jangan deploy dengan cara:

```text
ZIP apps/api -> FTP -> extract -> overwrite
```

Gunakan release directory immutable:

```text
/var/www/laras-api/
├── current -> releases/<release-sha>/
├── releases/
│   └── <release-sha>/
│       └── apps/api/
├── shared/
│   ├── .env
│   └── storage/
└── backups/
```

Nginx hanya diarahkan ke:

```text
/var/www/laras-api/current/apps/api/public
```

---

# 3. Prasyarat

Panduan ini mengasumsikan VPS Ubuntu/Debian dengan:

- SSH
- Git
- Nginx
- PHP 8.3
- PHP-FPM 8.3
- Composer 2
- PostgreSQL
- Certbot
- Firewall aktif

Contoh instalasi:

```bash
sudo apt update
sudo apt install -y \
  git nginx postgresql postgresql-contrib \
  php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl \
  php8.3-opcache \
  composer certbot
```

Verifikasi:

```bash
php -v
php-fpm8.3 -v
composer --version
psql --version
nginx -v
git --version
```

Pastikan PHP-FPM aktif:

```bash
sudo systemctl enable --now php8.3-fpm
sudo systemctl status php8.3-fpm
```

---

# 4. Firewall

Buka hanya SSH, HTTP, dan HTTPS.

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Jangan expose PostgreSQL port `5432` ke Internet.

Cek:

```bash
sudo ss -ltnp | grep 5432
```

Idealnya PostgreSQL hanya listen pada loopback/private interface.

---

# 5. DNS

Di Cloudflare buat record:

```text
Type    : A
Name    : api-laras
Content : <PUBLIC_IP_VPS>
Proxy   : DNS only
TTL     : Auto
```

Hasil akhir:

```text
api-laras.bakaranproject.com -> VPS
```

Verifikasi dari komputer lokal:

```bash
nslookup api-laras.bakaranproject.com
```

atau:

```bash
dig +short api-laras.bakaranproject.com
```

Jangan lanjut ke TLS sebelum DNS sudah mengarah ke IP VPS yang benar.

---

# 6. Buat PostgreSQL Database

Buat role aplikasi tanpa hak superuser:

```bash
sudo -u postgres createuser \
  --pwprompt \
  --no-createdb \
  --no-createrole \
  --no-superuser \
  laras
```

Buat database:

```bash
sudo -u postgres createdb --owner=laras laras
```

Verifikasi:

```bash
sudo -u postgres psql -c '\du laras'
sudo -u postgres psql -c '\l laras'
```

Database production:

```text
DB_DATABASE=laras
DB_USERNAME=laras
```

Password jangan disimpan di Git atau dokumentasi.

---

# 7. Siapkan Struktur Server

```bash
sudo install -d -m 750 /var/www/laras-api/{releases,shared,backups}
```

Persistent Laravel storage:

```bash
sudo install -d -m 2775 \
  /var/www/laras-api/shared/storage/app/private \
  /var/www/laras-api/shared/storage/app/public \
  /var/www/laras-api/shared/storage/framework/cache/data \
  /var/www/laras-api/shared/storage/framework/sessions \
  /var/www/laras-api/shared/storage/framework/views \
  /var/www/laras-api/shared/storage/logs
```

Contoh jika deploy menggunakan user SSH saat ini dan PHP-FPM memakai `www-data`:

```bash
sudo chown -R "$USER":www-data /var/www/laras-api
sudo find /var/www/laras-api/shared/storage -type d -exec chmod 2775 {} \;
sudo find /var/www/laras-api/shared/storage -type f -exec chmod 664 {} \;
```

Jangan gunakan:

```bash
chmod -R 777
```

---

# 8. Tentukan Exact Release SHA

Jangan mengasumsikan `main` lokal adalah versi terbaru.

Ambil SHA remote:

```bash
git ls-remote \
  https://github.com/yogawillyutomo/LARAS.git \
  refs/heads/main
```

Set:

```bash
export RELEASE_SHA=<EXACT_MAIN_SHA>
export RELEASE_DIR=/var/www/laras-api/releases/$RELEASE_SHA
```

Cek:

```bash
echo "$RELEASE_SHA"
echo "$RELEASE_DIR"
```

Release sebaiknya hanya dilakukan ketika CI exact commit tersebut sudah hijau.

---

# 9. Materialize Release

Clone exact repository:

```bash
git clone \
  --no-checkout \
  https://github.com/yogawillyutomo/LARAS.git \
  "$RELEASE_DIR"
```

Checkout exact SHA:

```bash
git -C "$RELEASE_DIR" checkout --detach "$RELEASE_SHA"
```

Verifikasi:

```bash
git -C "$RELEASE_DIR" rev-parse HEAD
```

Output harus sama persis dengan `$RELEASE_SHA`.

---

# 10. Buat Shared Production `.env`

Untuk deployment pertama:

```bash
cp \
  "$RELEASE_DIR/apps/api/.env.production.example" \
  /var/www/laras-api/shared/.env
```

Set permission:

```bash
chmod 640 /var/www/laras-api/shared/.env
chown "$USER":www-data /var/www/laras-api/shared/.env
```

Edit:

```bash
nano /var/www/laras-api/shared/.env
```

Minimum configuration:

```env
APP_NAME="LARAS API"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api-laras.bakaranproject.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laras
DB_USERNAME=laras
DB_PASSWORD=<STRONG_DB_PASSWORD>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=laras.bakaranproject.com
SESSION_COOKIE=laras_session
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=laras.bakaranproject.com
CORS_ALLOWED_ORIGINS=https://laras.bakaranproject.com

FILESYSTEM_DISK=local
ACTIVITY_REPORT_ATTACHMENT_DISK=local
ACTIVITY_REPORT_ATTACHMENT_MAX_KB=10240

QUEUE_CONNECTION=sync
CACHE_STORE=database
```

Jangan pernah gunakan `APP_DEBUG=true` di production.

Jangan pernah gunakan `CORS_ALLOWED_ORIGINS=*` karena autentikasi frontend menggunakan credentialed CORS.

---

# 11. Link `.env` dan Persistent Storage

Hapus storage release-local:

```bash
rm -rf "$RELEASE_DIR/apps/api/storage"
```

Buat symlink:

```bash
ln -s /var/www/laras-api/shared/storage \
  "$RELEASE_DIR/apps/api/storage"

ln -s /var/www/laras-api/shared/.env \
  "$RELEASE_DIR/apps/api/.env"
```

Pastikan bootstrap cache ada:

```bash
mkdir -p "$RELEASE_DIR/apps/api/bootstrap/cache"
```

Set permission:

```bash
chown -R "$USER":www-data \
  "$RELEASE_DIR/apps/api/bootstrap/cache"

chmod -R g+rwX \
  "$RELEASE_DIR/apps/api/bootstrap/cache"
```

---

# 12. Install Production Dependencies

Masuk ke Laravel API:

```bash
cd "$RELEASE_DIR/apps/api"
```

Install:

```bash
composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader
```

Validasi platform:

```bash
composer check-platform-reqs --no-dev
```

Semua requirement harus PASS sebelum lanjut.

---

# 13. Generate APP_KEY — Hanya First Deployment

Jika ini deployment pertama dan `APP_KEY` masih kosong:

```bash
cd "$RELEASE_DIR/apps/api"
php artisan key:generate --show
```

Copy nilai `base64:...` ke `/var/www/laras-api/shared/.env`.

Setelah production mulai digunakan, **JANGAN ROTATE APP_KEY saat release biasa**.

---

# 14. Test Database Connection

```bash
cd "$RELEASE_DIR/apps/api"
php artisan config:clear
php artisan migrate:status
```

Jika gagal, hentikan deployment dan perbaiki koneksi DB terlebih dahulu.

---

# 15. First Migration

Untuk production:

```bash
php artisan migrate --force
```

Jangan otomatis menjalankan seeder production.

---

# 16. Build Laravel Production Cache

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verifikasi tidak ada error.

---

# 17. Aktifkan Release Pertama

Buat symlink sementara:

```bash
ln -s "$RELEASE_DIR" /var/www/laras-api/current.new
```

Aktifkan atomically:

```bash
mv -Tf \
  /var/www/laras-api/current.new \
  /var/www/laras-api/current
```

Cek:

```bash
readlink -f /var/www/laras-api/current
```

Harus menunjuk ke `/var/www/laras-api/releases/<RELEASE_SHA>`.

---

# 18. Bootstrap Nginx HTTP untuk Certbot

Buat ACME root:

```bash
sudo mkdir -p /var/www/laras-acme/.well-known/acme-challenge
sudo chown -R www-data:www-data /var/www/laras-acme
```

Copy bootstrap config dari release:

```bash
sudo cp \
  "$RELEASE_DIR/infrastructure/nginx/laras-api-http-bootstrap.conf.example" \
  /etc/nginx/sites-available/laras-api
```

Enable:

```bash
sudo ln -sfn \
  /etc/nginx/sites-available/laras-api \
  /etc/nginx/sites-enabled/laras-api
```

Optional: disable default site:

```bash
sudo rm -f /etc/nginx/sites-enabled/default
```

Validate:

```bash
sudo nginx -t
```

Jika PASS:

```bash
sudo systemctl reload nginx
```

---

# 19. Issue TLS Certificate

Setelah DNS sudah benar:

```bash
sudo certbot certonly \
  --webroot \
  -w /var/www/laras-acme \
  -d api-laras.bakaranproject.com
```

Verifikasi:

```bash
sudo ls -la \
  /etc/letsencrypt/live/api-laras.bakaranproject.com/
```

---

# 20. Aktifkan Nginx HTTPS Laravel Config

Copy final template:

```bash
sudo cp \
  "$RELEASE_DIR/infrastructure/nginx/laras-api.conf.example" \
  /etc/nginx/sites-available/laras-api
```

Verifikasi PHP-FPM socket:

```bash
ls -la /run/php/
```

Template repository mengharapkan `/run/php/php8.3-fpm.sock`.

Jika server menggunakan socket berbeda, edit config Nginx terlebih dahulu.

Validasi:

```bash
sudo nginx -t
```

Jika PASS:

```bash
sudo systemctl reload nginx
```

Optional refresh PHP-FPM:

```bash
sudo systemctl reload php8.3-fpm
```

---

# 21. Smoke Test API

## Health

```bash
curl --fail --show-error --silent \
  https://api-laras.bakaranproject.com/up
```

## Unauthenticated API

```bash
curl -i \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Expected: HTTP `401` dengan JSON berisi `code: UNAUTHENTICATED`, bukan HTML.

## Allowed CORS

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Expected header:

```text
Access-Control-Allow-Origin: https://laras.bakaranproject.com
Access-Control-Allow-Credentials: true
```

## Rejected Origin

```bash
curl -i \
  -H 'Origin: https://evil.example' \
  -H 'Accept: application/json' \
  https://api-laras.bakaranproject.com/api/v1/me
```

Server **tidak boleh** mengembalikan `Access-Control-Allow-Origin: https://evil.example`.

## Sanctum CSRF

```bash
curl -i \
  -H 'Origin: https://laras.bakaranproject.com' \
  https://api-laras.bakaranproject.com/sanctum/csrf-cookie
```

Expected: successful response, secure cookies, dan cookie scope sesuai `laras.bakaranproject.com`.

---

# 22. Hubungkan Frontend Vercel

Setelah API smoke test PASS, di Vercel Production Environment Variables:

```env
VITE_PUBLIC_SCAN_ORIGIN=https://laras.bakaranproject.com
VITE_API_ORIGIN=https://api-laras.bakaranproject.com
```

Karena Vite compile env saat build, lakukan **Redeploy Production**.

---

# 23. Browser UAT Setelah Vercel Redeploy

Minimal test:

1. buka `https://laras.bakaranproject.com`
2. login
3. `/dashboard`
4. refresh browser pada route langsung
5. logout/login ulang
6. test authenticated API
7. test Asset list
8. test Asset QR route
9. test direct navigation `/q/<public-uuid>`
10. pastikan unauthorized/cross-school access tetap ditolak
11. pastikan browser DevTools tidak menunjukkan CORS/CSRF/session error

Release belum dianggap accepted sebelum browser/auth/QR UAT selesai.

---

# 24. Backup Sebelum Release Berikutnya

Sebelum migration-bearing release:

```bash
export BACKUP_TS=$(date +%Y%m%d-%H%M%S)
```

Database backup:

```bash
sudo -u postgres pg_dump \
  --format=custom \
  --file="/var/www/laras-api/backups/laras-$BACKUP_TS.dump" \
  laras
```

Checksum:

```bash
sha256sum \
  "/var/www/laras-api/backups/laras-$BACKUP_TS.dump" \
  > "/var/www/laras-api/backups/laras-$BACKUP_TS.dump.sha256"
```

Verify:

```bash
sha256sum -c \
  "/var/www/laras-api/backups/laras-$BACKUP_TS.dump.sha256"
```

Backup persistent storage:

```bash
tar -C /var/www/laras-api/shared \
  -czf "/var/www/laras-api/backups/storage-$BACKUP_TS.tar.gz" \
  storage
```

Checksum:

```bash
sha256sum \
  "/var/www/laras-api/backups/storage-$BACKUP_TS.tar.gz" \
  > "/var/www/laras-api/backups/storage-$BACKUP_TS.tar.gz.sha256"
```

---

# 25. Normal Release Berikutnya

Ambil exact remote SHA:

```bash
git ls-remote \
  https://github.com/yogawillyutomo/LARAS.git \
  refs/heads/main
```

Set:

```bash
export RELEASE_SHA=<NEW_EXACT_MAIN_SHA>
export RELEASE_DIR=/var/www/laras-api/releases/$RELEASE_SHA
```

Backup DB terlebih dahulu.

Clone exact release:

```bash
git clone --no-checkout \
  https://github.com/yogawillyutomo/LARAS.git \
  "$RELEASE_DIR"

git -C "$RELEASE_DIR" checkout --detach "$RELEASE_SHA"
```

Link shared state:

```bash
rm -rf "$RELEASE_DIR/apps/api/storage"
ln -s /var/www/laras-api/shared/storage "$RELEASE_DIR/apps/api/storage"
ln -s /var/www/laras-api/shared/.env "$RELEASE_DIR/apps/api/.env"
```

Prepare bootstrap cache:

```bash
mkdir -p "$RELEASE_DIR/apps/api/bootstrap/cache"
chown -R "$USER":www-data "$RELEASE_DIR/apps/api/bootstrap/cache"
chmod -R g+rwX "$RELEASE_DIR/apps/api/bootstrap/cache"
```

Install and validate:

```bash
cd "$RELEASE_DIR/apps/api"
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
```

Migration + caches:

```bash
php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Activate:

```bash
ln -s "$RELEASE_DIR" /var/www/laras-api/current.new
mv -Tf /var/www/laras-api/current.new /var/www/laras-api/current
```

Optional reload:

```bash
sudo systemctl reload php8.3-fpm
```

Smoke test kembali.

---

# 26. Rollback Aplikasi

Lihat release:

```bash
ls -lah /var/www/laras-api/releases
```

Pilih previous known-good release:

```bash
export PREVIOUS_SHA=<PREVIOUS_GOOD_SHA>
```

Switch symlink:

```bash
ln -s "/var/www/laras-api/releases/$PREVIOUS_SHA" \
  /var/www/laras-api/current.rollback

mv -Tf \
  /var/www/laras-api/current.rollback \
  /var/www/laras-api/current
```

Reload:

```bash
sudo systemctl reload php8.3-fpm
```

Smoke test lagi.

**Jangan otomatis menjalankan `php artisan migrate:rollback` saat application rollback.** Database rollback dan application rollback adalah dua hal berbeda.

---

# 27. Checklist Go / No-Go

## Sebelum deploy

- [ ] exact `main` SHA diketahui
- [ ] exact-release local validation gate PASS (GitHub Actions may be unavailable)
- [ ] DNS `api-laras.bakaranproject.com` benar
- [ ] firewall benar
- [ ] PostgreSQL tidak public
- [ ] `.env` production lengkap
- [ ] `APP_DEBUG=false`
- [ ] persistent APP_KEY sudah tersedia
- [ ] DB backup tersedia jika bukan first deploy
- [ ] storage backup tersedia bila diperlukan
- [ ] previous release masih tersedia

## Setelah deploy API

- [ ] `nginx -t` PASS
- [ ] TLS valid
- [ ] `/up` PASS
- [ ] `/api/v1/me` -> JSON 401
- [ ] allowed CORS PASS
- [ ] rejected origin tidak direfleksikan
- [ ] CSRF cookie endpoint PASS
- [ ] Laravel logs bersih
- [ ] PHP-FPM sehat

## Setelah frontend redeploy

- [ ] `VITE_API_ORIGIN` benar
- [ ] `VITE_PUBLIC_SCAN_ORIGIN` benar
- [ ] login PASS
- [ ] logout PASS
- [ ] browser refresh/direct route PASS
- [ ] Asset QR route PASS
- [ ] privacy/authorization PASS
- [ ] physical QR/print test dijadwalkan/dilakukan

---

# 28. Perintah Diagnostik

Laravel log:

```bash
tail -f /var/www/laras-api/shared/storage/logs/laravel.log
```

Nginx error:

```bash
sudo tail -f /var/log/nginx/laras-api.error.log
```

Nginx access:

```bash
sudo tail -f /var/log/nginx/laras-api.access.log
```

PHP-FPM:

```bash
sudo journalctl -u php8.3-fpm -f
```

Nginx:

```bash
sudo journalctl -u nginx -f
```

PostgreSQL:

```bash
sudo journalctl -u postgresql -f
```

Current release:

```bash
readlink -f /var/www/laras-api/current
```

Exact Git SHA current release:

```bash
git -C /var/www/laras-api/current rev-parse HEAD
```

---

# 29. Critical Rules

1. Deploy hanya dari exact Git SHA.
2. Jangan deploy uncommitted/local working tree.
3. Jangan copy `.env` production ke Git.
4. Jangan rotate APP_KEY pada normal release.
5. Jangan expose PostgreSQL 5432.
6. Jangan gunakan `chmod 777`.
7. Jangan menjalankan seeder production otomatis.
8. Jangan menggunakan wildcard credentialed CORS.
9. Jangan menghapus previous known-good release sebelum release baru stabil.
10. Jangan menganggap CI hijau sama dengan runtime UAT hijau.
11. Jangan deploy S6/PC Agent hanya karena PR-nya hijau.
12. Frontend tetap di Vercel; VPS hanya menjalankan Laravel API.

---

# 30. Release Flow Ringkas

```text
Merge ke main
   ↓
Pastikan CI hijau
   ↓
Ambil exact main SHA
   ↓
Backup DB/storage
   ↓
Materialize releases/<SHA>
   ↓
Link shared .env + storage
   ↓
composer install --no-dev
   ↓
composer check-platform-reqs
   ↓
migrate --force
   ↓
config/route/view cache
   ↓
Atomic current symlink switch
   ↓
Nginx/PHP-FPM
   ↓
API smoke test
   ↓
Vercel VITE_API_ORIGIN
   ↓
Redeploy frontend
   ↓
Browser/Auth/QR UAT
   ↓
Release Accepted
```
