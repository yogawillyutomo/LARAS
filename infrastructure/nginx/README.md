# Nginx

The initial LARAS production-like API deployment uses Nginx + PHP-FPM on the API VPS.

Canonical API hostname:

```text
api.laras.bakaranproject.com
```

Template:

- `laras-api.conf.example`

## Bootstrap order

1. Point Cloudflare DNS `api.laras` to the VPS using an `A` record. Keep it DNS-only during initial UAT.
2. Install an HTTP-only server block first so ACME validation can succeed.
3. Issue the TLS certificate for `api.laras.bakaranproject.com` (for example with Certbot).
4. Install/adapt `laras-api.conf.example`.
5. Verify the configured PHP-FPM socket matches the server (`php8.3-fpm` is the repository baseline).
6. Run `nginx -t` before every reload.
7. Reload Nginx only after syntax validation succeeds.

The template intentionally serves only Laravel's `public/` directory. Never point Nginx at the repository root or `apps/api` root.

The API allows Activity Report uploads up to 10 MiB at application level, so the template uses `client_max_body_size 12m` to leave protocol overhead without opening an unnecessarily large upload surface.

Do not enable a Cloudflare reverse proxy/orange-cloud for this API during the first UAT unless the end-to-end Sanctum, cookie, HTTPS, source-IP and upload behavior is revalidated after that change.
