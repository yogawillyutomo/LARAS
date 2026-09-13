# Nginx

The initial LARAS production-like API deployment uses Nginx + PHP-FPM on the API VPS.

Canonical API hostname:

```text
api.laras.bakaranproject.com
```

Templates:

- `laras-api-http-bootstrap.conf.example` — HTTP-only first-certificate bootstrap;
- `laras-api.conf.example` — normal HTTPS API vhost after TLS exists.

## Bootstrap order

1. Point Cloudflare DNS `api.laras` to the VPS using an `A` record. Keep it DNS-only during initial UAT.
2. Create the ACME webroot:

   ```bash
   sudo install -d -m 755 /var/www/laras-acme/.well-known/acme-challenge
   ```

3. Install/adapt `laras-api-http-bootstrap.conf.example`, enable the site, run `nginx -t`, and reload Nginx.
4. Prove the challenge path is reachable before asking Let's Encrypt for a certificate:

   ```bash
   printf 'laras-acme-probe\n' | sudo tee /var/www/laras-acme/.well-known/acme-challenge/laras-probe >/dev/null
   curl --fail http://api.laras.bakaranproject.com/.well-known/acme-challenge/laras-probe
   sudo rm /var/www/laras-acme/.well-known/acme-challenge/laras-probe
   ```

5. Issue the first certificate without letting Certbot rewrite the application vhost:

   ```bash
   sudo certbot certonly --webroot \
     -w /var/www/laras-acme \
     -d api.laras.bakaranproject.com
   ```

6. Replace the bootstrap site with `laras-api.conf.example`.
7. Verify the configured PHP-FPM socket matches the server (`php8.3-fpm` is the repository baseline).
8. Run `nginx -t` before every reload, then reload only after syntax validation succeeds.
9. Verify certificate renewal plumbing with `sudo certbot renew --dry-run`.

The `^~` ACME location is intentional: it prevents the general hidden-path deny regex from taking precedence over `/.well-known/acme-challenge/`.

The normal template intentionally serves only Laravel's `public/` directory. Never point Nginx at the repository root or `apps/api` root.

The API allows Activity Report uploads up to 10 MiB at application level, so the template uses `client_max_body_size 12m` to leave protocol overhead without opening an unnecessarily large upload surface.

Do not enable a Cloudflare reverse proxy/orange-cloud for this API during the first UAT unless the end-to-end Sanctum, cookie, HTTPS, source-IP and upload behavior is revalidated after that change.
