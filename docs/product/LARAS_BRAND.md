# LARAS — Brand & Canonical Domain Contract

**Status:** LOCKED  
**Product brand:** LARAS  
**Expanded name:** Laboratory Asset & Resource Administration System  
**Descriptor:** Laboratory Management Platform  
**Owner / publisher:** Bakaran Project  
**Canonical public origin:** `https://laras.bakaranproject.com`

## Brand rule

User-facing product surfaces use **LARAS**. The former **SMARTLAB / SmartLab** name is a legacy technical/repository identifier only where compatibility or historical evidence still requires it. It must not be reintroduced into new user-facing copy.

Preferred presentation:

> **LARAS**  
> Laboratory Management Platform  
> by Bakaran Project

The expanded name, **Laboratory Asset & Resource Administration System**, may be used in documentation, metadata, onboarding, and formal product descriptions. Compact navigation surfaces may use the LARAS wordmark without repeating the expansion.

## Canonical domain rule

All physical Asset QR labels resolve through the permanent public origin:

`https://laras.bakaranproject.com/q/<public-uuid>`

The domain is an enduring product address, not a hosting-provider address. Vercel, a VPS, another frontend host, the API host, or other infrastructure may change later without changing the physical QR contract. DNS/routing must preserve this public origin for already-issued labels.

Physical labels must never encode a Vercel preview hostname, raw server IP, API hostname, or another transient deployment URL.

## Runtime configuration

- `VITE_PUBLIC_SCAN_ORIGIN` must be `https://laras.bakaranproject.com` for production label generation.
- `VITE_API_ORIGIN` remains the deployed Laravel API origin when the web host does not provide an equivalent verified `/api` proxy.
- QR rendering must fail closed when the canonical public origin is absent or the resulting URL exceeds the locked QR capacity.
- TLS/HTTPS is mandatory for the canonical public origin in production.

## Repository rebrand rule

S5.6 has been merged to `main`, so repository-facing public identity may now converge to LARAS before first production-server deployment.

The intended sequence is:

1. align repository entry-point documentation and active operational docs to LARAS;
2. keep compatibility-sensitive internal identifiers unchanged unless a concrete migration benefit exists;
3. pass exact-head CI for the rebrand tranche;
4. merge the rebrand tranche only after normal review gates pass;
5. rename the GitHub repository from `SMARTLAB` to `LARAS` as an explicit repository-administration action;
6. update developer remotes and external automation/integration references after the GitHub rename;
7. deploy production/UAT from the post-rebrand verified `main`.

The repository rename is an identity/administration operation, not an excuse to rewrite history or internal namespaces.

## Compatibility rule

The following may intentionally retain the legacy SMARTLAB/smartlab identifier when changing them would create compatibility risk without user-facing benefit:

- historical branch, commit, pull-request, and documentation evidence;
- database/schema/table names already shipped;
- PHP/TypeScript internal names where there is no public-facing value in renaming them;
- existing API paths and permission keys;
- package identifiers such as `@smartlab/web` and `smartlab/api` until separately reviewed;
- legacy environment-variable names such as `SMARTLAB_*`;
- local test identities such as `@smartlab.local`;
- browser storage keys such as `smartlab_pplg_ui`, so existing preferences are not silently lost;
- historical filenames whose rename would break durable documentation links.

Do **not** perform a blind repository-wide `SMARTLAB → LARAS` replacement.

## GitHub repository rename contract

After this documentation/code tranche is merged and its CI is green, the repository should be renamed administratively from:

`yogawillyutomo/SMARTLAB`

to:

`yogawillyutomo/LARAS`

GitHub normally redirects the previous repository URL after a rename, but active developer clones and configured integrations should still be updated explicitly rather than relying indefinitely on redirects.

Recommended developer remote update after the rename:

```bash
git remote set-url origin https://github.com/yogawillyutomo/LARAS.git
```

Repository homepage metadata should point to:

`https://laras.bakaranproject.com`

## Label/PDF rule

Newly generated physical labels and downloadable PDFs use **LARAS** branding. PDF filenames use the `laras-asset-labels-...pdf` prefix. The centered `BP` QR mark remains a Bakaran Project ownership mark and is independent from the product wordmark.

## Production acceptance boundary

Repository rebranding does not itself establish production readiness. Production acceptance still requires the locked runtime evidence, including:

- canonical-domain routing and TLS;
- deployed frontend/API origin configuration;
- direct `/q/<public-uuid>` navigation;
- signed-out privacy behavior;
- authenticated same-School authorization behavior;
- browser data-bearing UAT;
- PDF inspection and measured label layout;
- real printer output;
- real-phone scans of required label layouts;
- exact-head automated gates immediately before release acceptance.

## Change control

Changing the product name, canonical public origin, QR route shape, or UUID-only identifier rule after physical labels are issued is a migration event. It requires an explicit compatibility plan and must not be performed as an incidental UI refactor.
