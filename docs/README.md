# LARAS Documentation

This directory is the canonical human documentation entry point for **LARAS — Laboratory Asset & Resource Administration System**, a Laboratory Management Platform by **Bakaran Project**.

Repository source, committed contracts, migrations, tests, Git history, pull requests, exact-head CI, and runtime/UAT evidence remain higher-order evidence when narrative documentation disagrees with implementation reality. This index is an entry point, not a second source of truth.

Canonical public origin: `https://laras.bakaranproject.com`

Brand and compatibility contract: [LARAS Brand & Canonical Domain Contract](product/LARAS_BRAND.md).

> Legacy note: historical filenames, commits, branches, package identifiers, test identities, storage keys, database/schema names, and older documents may still contain `SMARTLAB` / `SmartLab` / `smartlab`. Those are compatibility or historical identifiers. New user-facing product copy uses **LARAS**.

## Documentation map

### Product

- [LARAS Brand & Canonical Domain Contract](product/LARAS_BRAND.md)
- [Toolman / Teknisi Operational Coverage](product/TOOLMAN_TEKNISI_OPERATIONAL_COVERAGE.md) — proposed nine-duty reporting contract tracked by issue #95
- [Legacy SMARTLAB Operational Workflow Specification](product/SMARTLAB_OPERATIONAL_WORKFLOW_SPEC.md) — historical filename retained for link stability
- [Legacy Product Requirements Document](product/PRD_SmartLab_PPLG_v1.0.docx) — historical artifact

### Current architecture and source of truth

- [Current Architecture State](architecture/CURRENT_STATE.md)
- [Source-of-Truth Migration](architecture/source-of-truth-migration.md)
- [Repository Structure](architecture/REPOSITORY_STRUCTURE.md)
- [Documentation Convergence Audit](architecture/DOCUMENTATION_CONVERGENCE.md)

### Core architecture

- [Backend Foundation](architecture/backend-foundation.md)
- [CI Foundation](architecture/ci-foundation.md)
- [SPA Session Authentication](architecture/spa-session-authentication.md)
- [Frontend SPA Auth Integration](architecture/frontend-spa-auth-integration.md)
- [Frontend Laboratory API Integration](architecture/frontend-laboratory-api-integration.md)

### Domain contracts

- [Academic Master Data](architecture/academic-master-data-contract.md)
- [Identity Administration](architecture/identity-administration-contract.md)
- [Laboratory](architecture/laboratory-domain-api.md)
- [Device](architecture/device-domain-contract.md)
- [Layout](architecture/layout-domain-contract.md)
- [Transfer](architecture/transfer-domain-contract.md)
- [Incident](architecture/incident-domain-contract.md)
- [Operational Calendar / Closure](architecture/operational-calendar-closure-contract.md)
- [Unified Laboratory Availability](architecture/unified-laboratory-availability-contract.md)
- [Laboratory Reservation](architecture/laboratory-reservation-contract.md)
- [Dated Schedule Exception](architecture/dated-schedule-exception-contract.md)
- [Priority Event](architecture/priority-event-contract.md)
- [Laboratory Session + Activity Report](architecture/laboratory-session-activity-report-contract.md)
- [Asset, Inventory, Loan, and Preventive Maintenance](architecture/asset-inventory-loan-maintenance-contract.md)
- [Corrective Work Order](architecture/work-order-domain-contract.md)
- [S4 Prototype Reconciliation Audit](architecture/S4_PROTOTYPE_RECONCILIATION.md)

### Architecture Decision Records

- [ADR-001 — Master Data, TESSELA, and legacy SMARTLAB scheduling boundary](architecture/ADR-001-master-data-tessela-smartlab-scheduling-boundary.md)
- [ADR-002 — Asset, Inventory, Loan, and Preventive Maintenance Boundary](architecture/ADR-002-asset-inventory-loan-maintenance-boundary.md)
- [ADR-003 — Corrective Work Order Boundary](architecture/ADR-003-corrective-work-order-boundary.md)

Active S6 telemetry ADR/contract work is intentionally isolated on its S6 planning/development stack until governance permits reconciliation and merge into `main`; this `main` documentation index does not link branch-only documents.

Existing ADR filenames are not renamed only for cosmetic brand symmetry because durable links and historical evidence matter more than filename consistency.

### Machine-readable API contracts

Machine-readable API contracts remain outside `docs/`:

- [Contract package](../packages/contracts/README.md)
- [Root OpenAPI contract](../packages/contracts/openapi.yaml)

`packages/contracts/` is the machine-readable integration-contract source of truth and must not be moved merely to make the directory tree look symmetrical.

### Reviews / UAT evidence

- [S3.6 Offline ActivityReport Draft UAT](reviews/s3.6-offline-draft-uat.md)
- [S4.6 Reconciliation & Storage-Cleared UAT](reviews/s4.6-reconciliation-uat.md)
- [S5.4 Work Order Storage-Cleared UAT](reviews/s5.4-work-order-uat.md)
- S5.6 Asset QR / label evidence and rollout material remain under the relevant S5.6 review/product/deployment documents.

Automated evidence is not a substitute for operator-required browser, privacy, physical-print, or real-device UAT where those gates are explicitly locked.

### Development

- [Agent / contributor rules](../AGENTS.md)
- [Codex Review Loop](development/CODEX_REVIEW_LOOP.md)
- [P0 Frontend Stabilization](backlog/P0_FRONTEND_STABILIZATION.md)

### Production / deployment

Executable configuration and operational guidance remain under `infrastructure/`:

- [Deployment foundation](../infrastructure/deployment/README.md)
- [Production foundation](../infrastructure/deployment/PRODUCTION_FOUNDATION.md)
- [Production UAT checklist](../infrastructure/deployment/PRODUCTION_UAT_CHECKLIST.md)
- [Backup and restore](../infrastructure/deployment/BACKUP_RESTORE.md)
- [Rollback](../infrastructure/deployment/ROLLBACK.md)
- [Nginx configuration](../infrastructure/nginx/README.md)

These documents define a production/production-like UAT foundation. They do **not** by themselves prove production readiness.

## Product authority map

| Authority | Owns | LARAS rule |
| --- | --- | --- |
| **BP Master Data / School Core target** | shared School, Person, PhysicalSpace, and academic reference identity | LARAS may map/project canonical references. Canonical Person identity does **not** grant LARAS membership or role. |
| **TESSELA** | timetable generation, constraint solving, publication/version history | LARAS consumes immutable published timetable evidence; it does not solve or silently rewrite recurring timetables. |
| **LARAS** | Laboratory operational aggregate; Device; Layout; Transfer; Incident; Availability; Reservation; dated Schedule Exception; Priority Event; LaboratorySession; ActivityReport; Asset; Inventory; Loan custody; Preventive Maintenance; Work Order; Asset QR identity; telemetry domain | Operational mutations remain tenant-scoped, server-authorized, audited, concurrency-safe, and fail closed. |
| **LARAS SchoolMembership / product-local RBAC** | LARAS tenant membership and product permissions | Platform Admin/Superadmin status and canonical Person mapping do not implicitly grant LARAS tenant-row authority. |

Laboratory may map to canonical `PhysicalSpace`, but Device, Layout, Transfer, Incident, maintenance, Work Order, telemetry, and other laboratory operational lifecycles remain product-owned unless an explicit future contract changes that authority.

## Milestone posture

The detailed implementation history is preserved in Git, pull requests, contracts, migrations, and review evidence rather than duplicated as a mutable release-status table here.

High-level posture:

- **S0–S4:** foundational source-of-truth, scheduling/operations, execution/reporting, Asset/Inventory/Loan/Preventive Maintenance work is merged in stages; preserve established authority and concurrency invariants.
- **S5:** Corrective Work Order authority and Asset QR/label identity reached merged implementation tranches; S5.6 merged with LARAS branding, canonical public QR origin, and production-deployment foundation. Runtime/browser/privacy/auth/PDF/physical-print/real-phone evidence remains a release-acceptance concern where not yet recorded.
- **S6:** PC Monitoring Telemetry is developed under a privacy-bounded, revocable-machine-auth architecture. Development may occur on isolated draft branches, but merge/deploy governance must follow the locked dependency and final reconciliation rules.
- **S7–S8:** notifications/reporting/final reads and remaining browser-local retirement stay future work unless superseded by newer repository evidence.

For current branch, exact HEAD, PR, CI, and deployment status, verify remote GitHub and the target runtime directly. This document intentionally does not embed a mutable `main` SHA.

## Bakaran Platform integration

LARAS consumes shared canonical identity/reference data without surrendering product-local laboratory authority.

Existing Bakaran Platform reference documents may still use the historical SMARTLAB name in filenames or text. That historical naming does not change current product branding or LARAS product-local RBAC boundaries.

## Repository-rebrand boundary

Public product identity is **LARAS** and the GitHub repository rename is complete. The canonical repository is `yogawillyutomo/LARAS`.

The completed GitHub rename does not authorize a blind internal-namespace rewrite. Compatibility-sensitive identifiers such as existing package names, database/schema/table names, environment variables, storage keys, historical URLs, and test identities are migrated only when there is a concrete benefit and a verified compatibility plan.

Active developer remotes, deployment automation, and integrations should use the canonical `yogawillyutomo/LARAS` repository instead of relying on the legacy repository redirect.

## Documentation taxonomy target

Long-term convergence targets:

```text
docs/
├── README.md
├── product/
├── architecture/
├── domains/
├── adr/
├── security/
├── testing/
├── operations/
├── development/
├── backlog/
├── reviews/
└── references/
```

This is a target taxonomy, not a mandate to move historical files immediately. Current durable links and evidence take precedence over cosmetic symmetry.

## Production acceptance principle

Production acceptance requires evidence, not labels. At minimum, the applicable release must prove:

1. production topology and trust boundaries;
2. TLS, environment/secrets handling, and origin configuration;
3. migration and release sequencing;
4. backup/restore and rollback capability appropriate to the rollout;
5. health/readiness behavior;
6. server authorization, tenant isolation, audit, and concurrency invariants;
7. browser/runtime UAT for the deployed release;
8. privacy checks for public/signed-out surfaces;
9. physical/PDF/real-device evidence where a feature depends on printed QR labels or device interaction;
10. exact release evidence tying commit, CI, configuration, and deployment/UAT together.

A green CI run is necessary evidence for a release candidate, but it is not alone sufficient proof of production readiness.
