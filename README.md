# LARAS

**Laboratory Asset & Resource Administration System**  
**Laboratory Management Platform**  
by **Bakaran Project**

LARAS is a school laboratory management platform for planning laboratory use, operating laboratory sessions, managing devices and assets, inventory, loans, maintenance, incidents, corrective Work Orders, Asset QR identity, and the foundation for privacy-bounded PC telemetry.

Canonical public origin: `https://laras.bakaranproject.com`

> Repository note: the GitHub repository rename is complete. The canonical repository is `yogawillyutomo/LARAS`. Historical commits, branch names, database/schema identifiers, package names, environment variables, storage keys, and test identities may retain the legacy `SMARTLAB` / `smartlab` identifier where renaming would create compatibility risk without user-facing benefit.

Canonical brand contract: [LARAS Brand & Canonical Domain Contract](docs/product/LARAS_BRAND.md).  
Canonical human documentation entry point: [LARAS Documentation](docs/README.md).

## Repository map

```text
laras/
├── apps/
│   ├── web/                  # React + TypeScript + Vite frontend
│   └── api/                  # Laravel REST API
├── services/
│   └── pc-agent/             # Windows telemetry agent workstream
├── packages/
│   ├── contracts/            # OpenAPI / HTTP contracts
│   └── design-tokens/        # Shared visual-token documentation
├── infrastructure/
│   ├── docker/
│   ├── nginx/                # LARAS API reverse-proxy examples
│   └── deployment/           # Production-UAT deployment, backup, restore, rollback
├── docs/
│   ├── README.md             # Canonical human documentation index
│   ├── product/
│   ├── architecture/
│   ├── development/
│   ├── backlog/
│   └── reviews/
├── scripts/
├── .github/
├── AGENTS.md
└── README.md
```

## Source-of-truth posture

LARAS is progressively moving laboratory workflows to server-authoritative Laravel + PostgreSQL domains. Repository code, migrations, committed contracts, exact-head tests/CI, and runtime/UAT evidence take precedence over stale narrative snapshots.

Server-authoritative domains already include, among others:

- authentication, active SchoolMembership, product-local RBAC, and identity administration;
- Laboratories, Devices, Layouts, Transfers, Incidents, and academic master references;
- published timetable consumption, Operational Calendar, availability, reservations, dated schedule exceptions, and Priority Events;
- LaboratorySession and ActivityReport workflows, including controlled offline draft sync and execution evidence;
- Assets, Inventory, Loans, Preventive Maintenance, reconciliation, and operational-state projection;
- corrective Work Orders, Inventory part consumption, corrective custody, and managerial verification;
- Asset QR public identity and batch label generation under the permanent `laras.bakaranproject.com` scan origin.

For current architecture and milestone details, use [docs/README.md](docs/README.md) and [Current Architecture State](docs/architecture/CURRENT_STATE.md).

## Frontend

```bash
cd apps/web
npm ci
npm run dev
```

Validation:

```bash
cd apps/web
npm ci
npm run lint
npm run typecheck
npm run test
npm run build
```

## API

Create the Laravel environment from `apps/api/.env.example`, configure a dedicated PostgreSQL database, then use the normal Laravel workflow.

```bash
cd apps/api
composer install --no-interaction
php artisan test
```

Local/UAT administrator password reset remains intentionally explicit and compatibility-safe:

```powershell
$env:SMARTLAB_UAT_ADMIN_PASSWORD="<choose-a-password-of-at-least-12-characters>"
php artisan db:seed --class=UatAdminSeeder
```

The legacy `SMARTLAB_UAT_ADMIN_PASSWORD` key and `uat.admin@smartlab.local` test identity are internal compatibility identifiers, not public product branding.

## Repository-wide validation

Linux/macOS:

```bash
./scripts/check-all.sh
```

Windows PowerShell:

```powershell
./scripts/check-all.ps1
```

GitHub CI additionally validates frontend lint/typecheck/tests/build, OpenAPI syntax, relative Markdown links, Composer metadata, Laravel tests, PostgreSQL migrations/seeders, and PostgreSQL concurrency gates.

## Deployment

Production-UAT deployment guidance is under [`infrastructure/deployment/`](infrastructure/deployment/README.md).

Important release boundaries:

- `https://laras.bakaranproject.com` is the durable public product origin;
- physical QR labels must not encode transient Vercel preview URLs or infrastructure hostnames;
- Laravel API deployment, browser/privacy/auth UAT, PDF inspection, real printer output, and real-phone scans remain evidence gates for production acceptance;
- changing hosting later must preserve the canonical public hostname and `/q/<public-uuid>` route contract.

## Compatibility policy

This rebrand does **not** perform a blind namespace rewrite. Internal names such as `@smartlab/web`, `smartlab/api`, existing database/schema identifiers, historical document filenames, legacy environment variables, browser storage keys such as `smartlab_pplg_ui`, and historical Git references may remain until a concrete compatibility-safe migration is justified.

New user-facing copy, metadata, labels, and product documentation should use **LARAS**.

## Working agreement

1. Use one issue or task per branch.
2. Keep frontend, backend, agent, and infrastructure changes separated unless a contract change requires coordinated edits.
3. Update machine-readable contracts before or alongside API-breaking work.
4. Every pull request must include validation evidence.
5. Do not merge when required CI or locked runtime gates fail.
6. Do not present browser-local prototype data as canonical operational truth after a domain has moved to the API.
7. Do not rename compatibility-sensitive internal identifiers merely for cosmetic consistency.
