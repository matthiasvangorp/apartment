# Apartment Manager

Self-hosted apartment knowledge base and appliance tracker. Drops Hungarian PDF
documents into a folder, runs them through OCR and Claude, and builds a
searchable archive with utility-consumption analytics and a maintenance
calendar. Queried by a separate personal-agent project over HTTP, or browsed
via a small Livewire dashboard.

Built for a Belgian expat in Budapest with zero Hungarian and a pile of PDFs.

## What it does

### 1. Drop-folder ingestion

Put PDFs into `storage/app/apartment/inbox/`. Every 5 minutes a scheduled
command (`apartment:ingest`) picks them up and runs each one through:

1. **Text extraction** — `smalot/pdfparser` first. If that returns fewer than
   50 usable characters (e.g. scanned documents), fall back to rasterising
   with `pdftoppm` and OCR'ing with **Tesseract** using the Hungarian
   language pack.
2. **Structured extraction** — a single `claude-sonnet-4-5` call via
   `laravel/ai`. The raw Hungarian text goes in, a strict JSON object comes
   out with category, English title + summary, issued/period dates,
   counterparty, amount, and (for utility invoices) utility type,
   consumption value, unit, and meter serial.
3. **Dedup + persist** — documents are keyed on a SHA-256 of their extracted
   text. Re-dropping the same PDF is a no-op. New documents are inserted into
   MySQL and the source file is moved to
   `storage/app/apartment/knowledge/<category>/<yyyy-mm>/<slug>.pdf`.
4. **Event-driven linking** — when an `appliance_manual` document lands, a
   `DocumentIngested` event fires and a listener tries to match it against
   the `appliances` table by brand + model, auto-populating
   `manual_document_id`.

Every step emits a structured log event (`ingest.succeeded`, `ingest.ocr_used`,
`ingest.duplicate_skipped`, `ingest.failed_claude`, …) to
`storage/logs/apartment/ingest.log`.

### 2. Utility analytics

Utility invoices get a row in `utility_readings` per bill. A daily 09:00
Europe/Budapest job (`RecomputeUtilityStats`) computes per utility type:

- Rolling 12-month average consumption
- Year-over-year delta vs the reading closest to (latest − 1 year), within a
  ±60-day window
- Anomaly flag — `true` when the latest reading is more than 1.3× the
  trailing-6-reading average (requires at least 6 prior readings to fire)

Results land in `utility_stats` keyed on `(utility_type, window_end)`.

### 3. Appliance + maintenance tracker

`appliances` rows (brand, model, location, optional manual-document link) each
have any number of `maintenance_tasks` with a `cadence_months` value. A daily
09:00 Europe/Budapest job (`RecomputeMaintenance`) walks every task and
computes `next_due_on = (last_done_on ?? today) + cadence_months`. The
Livewire dashboard surfaces overdue / due-soon tasks and offers a
"Mark done" button that updates both `last_done_on` and `next_due_on`.

## HTTP API

Authenticated with a static bearer token from `APARTMENT_API_TOKEN`:

```
GET /api/v1/utility/summary?type=electricity
GET /api/v1/utility/readings?type=electricity&from=YYYY-MM-DD&to=YYYY-MM-DD
GET /api/v1/maintenance/upcoming?within_days=90
GET /api/v1/appliances
GET /api/v1/documents/search?q=...&category=...&limit=20
GET /api/v1/documents/{id}          # returns a 30-min signed download URL
GET /api/v1/documents/{id}/download # signed, no token
```

Responses are flat JSON, intended to be rendered into Telegram replies by a
separate personal-agent project.

## Dashboard

A small Livewire 4 admin at `https://apartment.test`:

| Page | What it shows |
|---|---|
| `/` Overview | Metric cards, electricity trend chart, upcoming maintenance, recent ingestions |
| `/documents` | Searchable (MySQL FULLTEXT) + category-filtered list with pagination |
| `/documents/{id}` | Summary, metadata, signed PDF link, linked appliance |
| `/appliances` | Cards per appliance with per-task "Mark done" buttons |
| `/utility` | Dual-axis kWh + HUF chart, anomaly metric, full readings table |

Dark mode via `.dark-mode` class on `body`, persisted in `localStorage`.
Chart.js 3.9.1 from CDN — no Vite, no npm build step.

## Stack

- **Laravel 11** on **PHP 8.3** (bumped from 8.2 because `laravel/ai` requires
  8.3+)
- **MySQL 8** with a FULLTEXT index on `documents(title_en, summary_en, raw_text)`
- **Redis 7** + `predis/predis` (no phpredis extension needed)
- **Tesseract** (`tesseract-ocr-hun`, `tesseract-ocr-eng`) + `poppler-utils`
  for OCR fallback
- **`smalot/pdfparser`** for fast text extraction
- **`laravel/ai`** + `claude-sonnet-4-5` for structured extraction
- **Livewire 4** for the dashboard, Chart.js 3.9.1 via CDN
- **Horizon** for queue introspection; **Supervisor** runs apache, the queue
  worker, and `schedule:work` inside the container
- **Traefik** reverse proxy in front for local HTTPS via `mkcert`

## Running it

This project is scaffolded from the `laravel-php82` template in
`~/docker-templates/` and is designed to run behind the shared Traefik
infrastructure at `~/docker-infrastructure/`. See the root `CLAUDE.md` in
`~/Sites/` for the broader Docker setup.

```bash
cd ~/Sites/matthiasvangorpkft/apartment
dup                   # docker-compose up -d
dartisan migrate      # first-time only
dartisan db:seed --class=ApplianceSeeder
```

Then visit `https://apartment.test`. Supervisor (defined in
`docker/supervisord.conf`) starts apache, a Redis queue worker, and
`schedule:work` automatically.

### Environment

| Variable | Purpose |
|---|---|
| `ANTHROPIC_API_KEY` | For the ingestion Claude call |
| `APARTMENT_API_TOKEN` | Bearer token the HTTP API requires |
| `APARTMENT_CLAUDE_MODEL` | Defaults to `claude-sonnet-4-5` |
| `APP_TIMEZONE` | Set to `Europe/Budapest` so daily jobs fire at 09:00 local |

## Tests

```bash
dartisan test
```

24 feature + unit tests covering the ingestion pipeline (with a mocked
Claude client), utility analytics (anomaly edge cases, YoY, idempotent
recompute), manual auto-linking, the HTTP API (auth + every endpoint), and
the Livewire Appliances "mark done" action.

Tests run against SQLite `:memory:`. The documents FULLTEXT index is
conditionally created only on MySQL; the search path falls back to `LIKE`
when running against SQLite.

## Layout

```
app/
├── Apartment/
│   ├── Analytics/UtilityAggregator.php
│   └── Ingest/{TextExtractor, ClaudeExtractor, IngestionPipeline}.php
├── Console/Commands/ApartmentIngest.php
├── Events/DocumentIngested.php
├── Http/
│   ├── Controllers/Api/{Utility,Maintenance,Appliance,Document}Controller.php
│   └── Middleware/ApartmentApiToken.php
├── Jobs/{IngestDocument, RecomputeUtilityStats, RecomputeMaintenance}.php
├── Listeners/LinkManualToAppliance.php
├── Livewire/{Overview, Documents, DocumentDetail, Appliances, Utility}.php
└── Models/{Document, UtilityReading, UtilityStat, Appliance, MaintenanceTask}.php

database/
├── migrations/        # 5 tables + Sanctum access tokens
└── seeders/ApplianceSeeder.php

docker/
├── apache.conf
├── php.ini
└── supervisord.conf   # apache + queue-worker + scheduler

routes/
├── api.php            # /api/v1/* behind apartment.token middleware
├── console.php        # apartment:ingest every 5 min; daily recomputes at 09:00 CET/CEST
└── web.php            # Livewire dashboard routes
```

## Deploying

See [`DEPLOY.md`](DEPLOY.md) for the deploy-key setup used to push to
this repo.

## License

Personal project. All rights reserved.
