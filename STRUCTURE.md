# The New Platform — Structure and Where to Start

**City of Kigali — Digital Inspection Platform v2**

The old platform keeps collecting data while this is built. Nothing is
switched off until v2 does the same job better.

---

## The stack

| Layer | Choice | Why |
|---|---|---|
| Language | **PHP 8.3+** | You already know it. Rewriting in another language buys nothing. |
| Framework | **Laravel 11** | Migrations, ORM, policy-based authorization, queues, validation, testing — all the things you are currently hand-rolling. |
| Database | **PostgreSQL 16 + PostGIS** | Parcel geometry, and automatic distance compliance. See below. |
| Front end | **Inertia.js + Vue 3** | SPA feel without maintaining a separate API and token auth. |
| Maps | **MapLibre GL + Leaflet** | Parcel polygons, inspection pins, district choropleths. |
| Charts | **Apache ECharts** | Far beyond the hand-drawn bars in today's `app.js`. |
| Offline | **PWA + IndexedDB (Dexie) + sync queue** | Inspectors work where there is no network. |
| Queue | **Laravel Horizon + Redis** | Report generation and AI drafting must not block a page load. |
| Documents | **PHPWord + Gotenberg** | DOCX for editing, PDF for signing. |

### Why PostGIS matters more than it sounds

Read your own petrol checklist again:

- 300 m minimum from sensitive areas (schools, hospitals, markets)
- 75 m from the nearest road junction
- 15 m / 20 m from power lines
- 30 m from residential plots

Today an inspector eyeballs those and ticks a box. With parcel geometry
in PostGIS, the system *computes* them:

```sql
SELECT ST_Distance(station.geom::geography, school.geom::geography) AS metres
FROM   entities station, sensitive_areas school
WHERE  station.id = :id
ORDER  BY metres LIMIT 1;
```

The inspector confirms a measurement instead of estimating one, and the
enforcement letter cites an actual figure. That is the single strongest
argument for the migration, and it is worth doing before the data grows.

---

## Directory structure

```
cok-inspection/
├── app/
│   ├── Actions/                    # One class per business operation
│   │   ├── Inspections/
│   │   │   ├── StartInspection.php
│   │   │   ├── SubmitInspection.php
│   │   │   └── ScoreInspection.php
│   │   ├── Documents/
│   │   │   ├── DraftEnforcementLetter.php
│   │   │   ├── SubmitForReview.php
│   │   │   ├── ApproveDocument.php
│   │   │   └── SignDocument.php
│   │   └── Sync/
│   │       └── IngestOfflineBatch.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Entity.php              # Building, station, road segment...
│   │   ├── EntityType.php
│   │   ├── Parcel.php              # UPI + PostGIS geometry
│   │   ├── Inspection.php
│   │   ├── InspectionAnswer.php
│   │   ├── ChecklistTemplate.php   # VERSIONED — see below
│   │   ├── ChecklistSection.php
│   │   ├── ChecklistItem.php
│   │   ├── Document.php            # Report or letter
│   │   ├── DocumentSignature.php
│   │   └── AuditEntry.php
│   │
│   ├── Policies/                   # Who may do what — one per model
│   │   ├── InspectionPolicy.php
│   │   └── DocumentPolicy.php
│   │
│   ├── States/                     # Document workflow state machine
│   │   └── Document/
│   │       ├── Draft.php
│   │       ├── UnderReview.php
│   │       ├── Approved.php
│   │       ├── Signed.php
│   │       └── Issued.php
│   │
│   ├── Services/
│   │   ├── Ai/
│   │   │   ├── DraftingService.php
│   │   │   ├── Deidentifier.php    # Strips names/phones before any API call
│   │   │   └── BuildingCodeRetriever.php   # RAG over the Code
│   │   ├── Geo/
│   │   │   └── ProximityChecker.php        # The distance rules above
│   │   └── Scoring/
│   │       └── WeightedScorer.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/               # Validation lives here, not in controllers
│   │   └── Middleware/
│   │
│   └── Jobs/                       # Queued: never block a page load
│       ├── GenerateReportDocument.php
│       ├── DraftLetterWithAi.php
│       └── ProcessInspectionPhotos.php
│
├── database/
│   ├── migrations/                 # Schema in version control, never phpMyAdmin
│   └── seeders/
│       ├── ChecklistTemplateSeeder.php
│       └── KigaliAdministrativeSeeder.php   # District/sector/cell/village
│
├── resources/js/
│   ├── Pages/
│   │   ├── Dashboard.vue
│   │   ├── Inspections/{Index,Create,Show}.vue
│   │   ├── Map.vue
│   │   ├── Documents/{Inbox,Review,Sign}.vue
│   │   └── Admin/{Users,Checklists}.vue
│   ├── Components/
│   │   ├── Checklist/
│   │   ├── Map/
│   │   └── Charts/
│   └── offline/
│       ├── db.js                   # Dexie / IndexedDB schema
│       ├── queue.js                # Outbound sync queue
│       └── sync.js                 # Conflict resolution
│
├── storage/app/
│   ├── photos/                     # NOT base64 in the database
│   └── documents/
│
├── tests/
│   ├── Feature/
│   │   ├── PermissionTest.php      # Write these FIRST
│   │   └── DocumentWorkflowTest.php
│   └── Unit/
│       └── WeightedScorerTest.php
│
└── .env                            # Never committed
```

---

## The three hard parts

Everything else is ordinary CRUD. These three will hurt if you get them
wrong, so design them before you write features on top of them.

### 1. Versioned checklists

Today, editing a `checklist_items` row silently rewrites the score of
every past inspection. For an enforcement record that is unacceptable —
a letter cites a specific requirement as it stood on a specific date.

```
checklist_templates
  id, entity_type_id, version, effective_from, effective_to,
  published_at, published_by

checklist_sections   -> template_id
checklist_items      -> section_id

inspections
  ...
  checklist_template_id   <- FROZEN at inspection time, never updated
```

Rule: **published templates are immutable.** Changing a weight creates
version 2. Old inspections stay bound to version 1 and render exactly as
they were signed.

### 2. Permissions, not a role ladder

Your current `hasRoleLevel()` breaks the moment reality arrives:
*"inspector for petrol stations in Gasabo only"*, *"can approve but not
sign"*, *"desk-review officer sees permits but not field inspections"*.

Use `spatie/laravel-permission` plus a scope table:

```
roles                 Inspector, Senior Inspector, Chief Inspector,
                      Director, Desk Reviewer, Admin

permissions           inspection.create, inspection.submit,
                      inspection.approve, document.draft,
                      document.review, document.sign, document.issue,
                      entity.merge, checklist.publish, user.manage

user_scopes           user_id, district_id (nullable = all),
                      entity_type_id (nullable = all)
```

Then a Laravel Policy answers *"may this user sign this document?"* in
one place, and `tests/Feature/PermissionTest.php` proves it stays true.

### 3. Documents as a state machine with real signatures

```
draft → under_review → approved → signed → issued → served
                    ↘ returned_for_revision ↗
```

Every transition is a row in the audit trail: who, when, from what state,
to what state, and why. No status column that people update ad hoc.

**On signatures.** Rwanda recognises electronic signatures under Law
No. 18/2010, and RISA publishes guidance distinguishing simple, advanced
and qualified signatures. A pasted signature image will not survive a
challenge from a station owner. Aim for at least an *advanced* signature:

- Freeze the approved document to an immutable PDF
- Compute and store its SHA-256 hash
- Bind the hash to the signer's verified identity and a trusted timestamp
- Store the signature record separately from the document
- Any later alteration changes the hash and is therefore detectable

Design the pipeline so the signature applies to a frozen artifact, never
to a live database row that can drift.

---

## The AI layer

Non-negotiable: **AI drafts, a human reviews, only a human signs.**

```
Field findings
  → Deidentifier  (strip owner names, phones, emails, UPI)
  → Retrieve relevant Building Code / station regulation clauses
  → Claude API (queued job, never a blocking request)
  → Draft letter, state = draft
  → Inspector edits
  → Review chain
  → Signature
```

The de-identification step matters legally, not just ethically. Sending
inspection data to an API outside Rwanda is a cross-border transfer of
personal data. The model does not need to know the owner's name to
describe a missing hose reel — send the findings, merge the identity back
in server-side.

Log every prompt and response against the inspection. When someone asks
in two years how a letter was produced, you need an answer.

---

## Phased roadmap

Ship something inspectors can hold every few months. Do not disappear for
a year.

### Phase 0 — Now (done by this patch)
Old platform hardened, still collecting data.

### Phase 1 — Foundation (weeks 1–8)
Laravel + PostGIS skeleton. Users, roles, permissions, audit log.
Migrate the existing schema and all inspection data. Building and petrol
checklists as versioned templates.
**Nobody uses it yet.** Ship when `PermissionTest` passes.

### Phase 2 — Field parity (weeks 9–16)
Inspection capture matching today's functionality, plus **offline mode**.
Photos to disk. Dashboard with ECharts. Report generation.
**Cut over here.** Run both in parallel for a month, then retire the old.

### Phase 3 — Map and parcels (weeks 17–24)
Import UPI parcel geometry. Map view. Automatic proximity compliance.
This is where the platform starts doing something paper never could.

### Phase 4 — Document workflow (weeks 25–34)
Letters and reports through review → approval → signature → issuance.
Online editing. Advanced e-signature. Notification of the served party.

### Phase 5 — New inspection types (weeks 35–44)
Road, hygiene, foundation, ongoing construction, desk review for building
permits. If phases 1–2 were built right, each of these is a seeder plus a
form, not a new subsystem.

### Phase 6 — AI drafting (weeks 45–52)
RAG over the Building Code. Draft letters from findings. Always reviewed.

---

## Where to start on Monday

1. **`git init`.** Private repo. Today. This is the highest-leverage thing
   you will do all month — the `index1.php` / `indexok.php` / `app1.js`
   sprawl exists purely because you have no history to fall back on.
   Commit the current live code *as it is* first, then commit the patch.
   Now you can always get back.

2. **Set up a local environment.** Laravel Herd or Docker (Laravel Sail).
   Restore a copy of the production database locally so you can break
   things freely.

3. **Deploy the patch** following `DEPLOY.md`.

4. **Start the hosting conversation.** Since this is a CoK system holding
   personal data, the National Data Centre is likely the correct and
   compliant destination. That has institutional lead time, so raise it
   now — it constrains every other decision, and you do not want to
   discover it in month ten.

5. **Then, and only then,** `laravel new cok-inspection` and start
   Phase 1.
