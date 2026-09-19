# Toolman / Teknisi Operational Coverage

**Status:** Proposed / product contract candidate  
**Issue:** #95  
**Product:** LARAS — Laboratory Asset & Resource Administration System  
**Scope:** nine operational duties for school laboratory Toolman/Technician reporting

## 1. Decision

In LARAS, the real-school **Toolman** function maps to the existing product role:

```text
teknisi
```

Do not add a separate Toolman role only to mirror administrative terminology.

The tenth generic duty, "other duties from supervisor", is intentionally outside this contract. This contract covers the nine concrete laboratory duties that should leave operational evidence and produce accountable reports.

## 2. Authorization boundary

Technician/Toolman is primarily an executor, observer, evidence recorder, requester, and reporter.

Loan custody transactions and stock authority belong to Kepala Lab / Admin Lab.

```text
TEKNISI / TOOLMAN
observe
execute technical work
record evidence
request
report
        |
        v
ADMIN LAB / KEPALA LAB
approve
loan custody transaction
stock transaction
administrative authorization
```

This is an authority boundary, not a visibility ban. Technician reporting may consume bounded read projections from canonical Loan and Inventory evidence without granting mutation authority.

### Current implementation discrepancy

At the time this contract is written, the current `teknisi` role still includes:

- `maintenance.consume-stock`;
- `work-orders.consume-stock`.

Those permissions must not be removed blindly before a safe replacement workflow exists. The implementation sequence should first introduce explicit part/resource request orchestration, then migrate stock-consumption authority to Kepala Lab/Admin Lab while preserving Maintenance and Work Order execution.

## 3. Nine-duty coverage

| No. | Real Toolman duty | LARAS authority / target workflow | Required report output |
| --- | --- | --- | --- |
| 1 | Daily activity book | Technician Daily Log plus system-derived activity evidence | Daily Activity Book |
| 2 | Laboratory administration work program | Lab Work Program plus realization evidence | Work Program & Realization Report |
| 3 | Bookkeeping of work equipment, machines, and practice materials | Asset + Device + Inventory | Equipment / Material Register |
| 4 | Recording outgoing/incoming borrowed equipment | Canonical Loan read projection; mutation remains Admin Lab / Kepala Lab authority | Borrowing & Return Recap |
| 5 | Laboratory schedule administration and usage rules | Schedule + Operational Calendar + Reservation + versioned Lab Policy | Laboratory Usage Schedule / Recap |
| 6 | Care and maintenance of equipment/materials | Preventive Maintenance + Incident + Work Order + Inventory evidence | Maintenance & Repair Report |
| 7 | Serving teacher/student equipment and practice-material needs | Resource Request orchestration; durable fulfillment via Loan, consumables via Inventory | Equipment / Material Service Recap |
| 8 | Recording assets and reporting their condition | Asset condition + Device + Incident + Maintenance/Work Order evidence | Asset Condition Report |
| 9 | Proposing procurement/replacement of spare parts and damaged equipment | Lightweight Procurement / Replacement Proposal | Procurement / Replacement Proposal Report |

## 4. Reporting principle

The reporting system must avoid double entry.

A Technician should not have to retype evidence that already exists in LARAS. Activity such as starting Maintenance, recording an Incident, progressing a Work Order, or submitting a Resource Request should be reusable as report evidence.

Manual Daily Log exists only for legitimate work that has no canonical operational object, for example:

- opening/closing a laboratory;
- cleaning and arranging a work area;
- checking a room before use;
- other laboratory-operational activity that is not already represented by another canonical domain.

Reports are read models and evidence outputs. They are not new mutation authorities.

## 5. Technician Daily Log

Candidate model:

```text
TechnicianDailyLog
  id
  schoolId
  technicianMembershipId
  laboratoryId?
  activityDate
  startedAt?
  completedAt?
  category
  description
  referenceType?
  referenceId?
  source              manual | system
  createdAt
  updatedAt
```

System-derived references may point to canonical entities such as:

- MaintenanceExecution;
- Incident;
- WorkOrder;
- ResourceRequest;
- ProcurementProposal;
- LaboratorySession;
- Inventory evidence where visibility policy permits;
- Loan summary evidence where visibility policy permits.

A generated daily report combines system evidence with legitimate manual entries without duplicating canonical history.

## 6. Lab Work Program

The work-program feature is not a Word-processing replacement.

Candidate structure:

```text
LabWorkProgram
  school
  laboratory / scope
  academicYear
  semester?
  status
  items[]

LabWorkProgramItem
  title
  target
  period
  responsibleMembership
  indicator
  evidenceBinding?
  realization
```

Realization should be derived from canonical evidence whenever possible.

Example:

```text
Program: Preventive maintenance Lab RPL 1
Target: 36 PCs
Period: July-December 2026
Responsible: Technician
Realization: 29 / 36
Evidence: MaintenanceExecution
```

## 7. Resource Request

Resource Request orchestrates a service request. It never owns Asset custody or Inventory balance.

```text
Teacher / authorized requester
        |
        v
Resource Request
        |
        +--> durable item --> canonical Loan fulfillment
        |
        +--> consumable ----> canonical Inventory issue
```

Candidate states:

```text
draft
submitted
preparing
ready
partially_fulfilled
fulfilled
rejected
cancelled
```

Technician may receive, prepare, record shortages, and mark readiness according to permission policy. Actual Loan/stock mutation remains with Admin Lab / Kepala Lab.

## 8. Procurement / Replacement Proposal

LARAS only owns the laboratory-needs proposal and its evidence. It does not become a procurement, accounting, invoice, or payment ERP.

Candidate proposal types:

- new equipment;
- replacement Asset;
- replacement spare part;
- stock replenishment.

Candidate evidence may reference:

- Asset condition;
- Incident;
- Work Order;
- Maintenance finding;
- Inventory below minimum stock.

Candidate lifecycle:

```text
draft
submitted
reviewed
approved
rejected
revision_required
```

Approval means the laboratory need has been approved inside LARAS. Downstream government/school procurement and finance processes remain outside this domain unless a future explicit integration contract is accepted.

## 9. Lab Policy

Laboratory usage rules should be versioned evidence, not hard-coded frontend text.

Candidate minimum semantics:

```text
LabPolicy
  schoolId
  laboratoryId?
  title
  version
  effectiveFrom
  effectiveUntil?
  content
  status
```

Schedule and usage workflows may display the currently effective policy without making LabPolicy another scheduling authority.

## 10. Technician Reporting Center

Target navigation:

```text
LARAS
└── Reports
    └── Technician / Toolman
        ├── Daily Activities
        ├── Work Program & Realization
        ├── Equipment / Material Register
        ├── Borrowing & Return Recap
        ├── Laboratory Usage
        ├── Maintenance & Repair
        ├── Resource Service
        ├── Asset Condition
        └── Procurement / Replacement Proposals
```

Minimum filters:

- date range;
- daily / monthly / semester / yearly period;
- Laboratory;
- Technician;
- status/category when relevant.

Target outputs:

- web view;
- PDF for official printable reporting;
- spreadsheet/export where tabular follow-up is useful.

All report rows must carry enough provenance to trace the canonical evidence behind them.

## 11. Existing domain reuse

This contract must preserve current authority boundaries:

- Asset owns fixed-asset administrative identity;
- Device owns managed technical-device identity;
- Inventory owns quantity and stock movement;
- Loan owns durable temporary custody;
- Maintenance owns preventive execution;
- Incident owns problem/report evidence;
- Work Order owns corrective execution;
- Schedule/Calendar/Reservation own their established operational scopes;
- shared Person/academic authority remains external/canonical according to Bakaran Platform integration contracts.

No new Toolman feature may create a second balance, second custody ledger, second Person master, or duplicate operational history.

## 12. Delivery sequence

Recommended sequence:

1. contract and current-domain/report mapping;
2. Technician Daily Log;
3. Lab Work Program + realization;
4. Resource Request orchestration;
5. Procurement / Replacement Proposal;
6. Lab Policy if no accepted domain already covers the requirement;
7. Technician Reporting Center + PDF/export;
8. stock/loan permission hardening after replacement request/approval workflows are safe;
9. browser UAT and report-evidence validation.

Issue #95 is the implementation tracking authority for this contract until a later accepted milestone/ADR supersedes it.

## 13. Acceptance invariants

The feature set is not complete until:

- all nine duties have an explicit LARAS evidence source;
- all nine have a reportable output;
- Technician does not need duplicate data entry for already-recorded canonical actions;
- Technician cannot mutate Loan custody merely to create a report;
- Technician cannot directly mutate Inventory balance after the target permission migration;
- Resource Request cannot decrement stock or create Loan custody by itself;
- Procurement Proposal does not become finance/accounting authority;
- report data remains tenant-scoped and permission-scoped;
- report rows can be traced to canonical evidence;
- historical reports remain reproducible according to the accepted snapshot/live-read policy;
- browser/PDF/export UAT demonstrates usable administrative output.
