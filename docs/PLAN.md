# Master Development Plan — Visite Technique Platform

Complete professional development plan for a Laravel-based SMS and WhatsApp notification platform for roadworthiness inspection centers.

**Architecture decisions:** Multi-tenant SaaS from day one | Twilio for SMS + WhatsApp

---

## Part 1 — Project Overview

### 1.1 Project Vision

Develop an enterprise-grade Laravel web application that allows roadworthiness inspection centers to:

- Import daily CSV inspection files
- Store inspection data securely (tenant-isolated)
- Automatically track certificate expiry dates
- Send automated SMS reminders via Twilio
- Send automated WhatsApp reminders via Twilio
- Generate reports and analytics
- Improve customer retention
- Modernize operational workflows
- Scale as a multi-tenant SaaS product

### 1.2 Main Business Workflow

```text
Inspection Center (Tenant)
        ↓
Export Daily CSV from local system
        ↓
Upload CSV into Platform (or dry-run validate)
        ↓
CSV Validation & Async Processing (Queue)
        ↓
Store Data in MySQL (tenant-scoped)
        ↓
Generate Notification Schedules
        ↓
Scheduler dispatches due reminders
        ↓
Queue Jobs → Twilio SMS / WhatsApp
        ↓
Webhook Callbacks → Update Delivery Status
        ↓
Dashboard Analytics & Reports
```

---

## Part 2 — Environment Setup

### 2.1 Development Machine Requirements

| Component | Recommended |
|-----------|-------------|
| RAM | 16 GB minimum |
| CPU | Intel i5/i7 or Ryzen 5/7 |
| Storage | SSD |
| OS | Ubuntu / macOS / Windows WSL |

### 2.2 Required Software

| Tool | Purpose |
|------|---------|
| PHP 8.3 | Laravel runtime |
| Composer | PHP dependencies |
| Node.js 20+ | Frontend tooling |
| MySQL 8 | Primary database |
| Redis 7 | Queue, cache, sessions |
| Docker | Recommended dev environment |
| Git | Version control |

### 2.3 Docker Development Environment (Recommended)

Use `docker-compose.yml` in the project root:

- `app` — PHP 8.3-FPM with required extensions
- `nginx` — Web server
- `mysql` — MySQL 8
- `redis` — Redis 7
- `mailpit` — Local email testing

Manual installation steps (Ubuntu) remain available in [DEPLOYMENT.md](DEPLOYMENT.md) for non-Docker setups.

### 2.4 Laravel Project Initialization

```bash
composer create-project laravel/laravel .
composer require livewire/livewire
composer require laravel/breeze --dev
php artisan breeze:install livewire
composer require spatie/laravel-permission
composer require maatwebsite/excel
composer require laravel/horizon
composer require twilio/sdk
composer require giggsey/libphonenumber-for-php
composer require spatie/laravel-activitylog
composer require spatie/laravel-backup
```

```bash
npm install -D tailwindcss postcss autoprefixer
npm install chart.js sweetalert2 axios
npm run build
```

---

## Part 3 — Project Initialization

### 3.1 Git Repository

```bash
git init
git add .
git commit -m "Initial Laravel setup with documentation"
```

### 3.2 Environment Configuration

Copy `.env.example` to `.env`. Key settings:

```env
APP_NAME="Visite Technique Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=visite_technique
DB_USERNAME=visite
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379

TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_SMS_FROM=
TWILIO_WHATSAPP_FROM=
DEFAULT_PHONE_COUNTRY=CM
```

```bash
php artisan key:generate
php artisan migrate
```

---

## Part 4 — Frontend Development

### 4.1 Technology Stack

| Component | Technology |
|-----------|------------|
| UI | Blade templates |
| Dynamic UI | Livewire 3 |
| Styling | Tailwind CSS |
| Charts | Chart.js |
| Alerts | SweetAlert2 |
| Icons | Heroicons |

### 4.2 Main Pages

| Page | Purpose |
|------|---------|
| Login | Authentication |
| Super-admin dashboard | Platform-wide metrics |
| Center dashboard | Tenant analytics |
| CSV Upload | Import with dry-run and progress |
| Customers | Customer management |
| Vehicles | Vehicle management |
| Inspections | Inspection history |
| Notifications | Schedule and delivery logs |
| Templates | Message templates per channel |
| Settings | Tenant Twilio and reminder config |
| Reports | Analytics and exports |

### 4.3 Dashboard Widgets

- Total inspections (tenant-scoped)
- Vehicles expiring in 7 / 15 / 30 days
- SMS sent (today / month)
- WhatsApp sent (today / month)
- Failed notifications
- Recent CSV uploads and batch status

---

## Part 5 — Authentication & Authorization

### 5.1 Laravel Breeze

Install with Livewire stack. Enable login throttling and password reset.

### 5.2 Roles (Spatie Permission)

| Role | Permissions |
|------|-------------|
| `super-admin` | `manage-tenants`, `view-platform-metrics` |
| `center-admin` | `manage-users`, `manage-settings`, `import-csv`, `manage-templates` |
| `operator` | `import-csv`, `view-customers`, `view-reports` |

### 5.3 Security Enhancements

- 2FA via Laravel Fortify for `super-admin` and `center-admin`
- Session timeout configuration
- Activity logging via `spatie/laravel-activitylog`
- Rate limiting on login and CSV upload routes

---

## Part 6 — Database Design

See [DATABASE.md](DATABASE.md) for full schema.

### 6.1 Core Tables

| Table | Purpose |
|-------|---------|
| `users` | Authentication (linked to tenant) |
| `inspection_centers` | Tenant root |
| `center_settings` | Per-tenant config (encrypted Twilio creds) |
| `customers` | Customer data |
| `vehicles` | Vehicle data |
| `inspections` | Inspection records |
| `imported_batches` | CSV import tracking |
| `notification_schedules` | Scheduled reminders |
| `notification_logs` | Send history and delivery status |
| `message_templates` | SMS/WhatsApp templates |
| `plans` / `subscriptions` | SaaS billing placeholder |

### 6.2 Tenant Scoping

All tenant data includes `inspection_center_id`. Global scope `TenantScope` applied via `BelongsToTenant` trait. Super-admin routes bypass scope explicitly.

### 6.3 Duplicate Detection

Unique constraint: `(inspection_center_id, license_plate, expiry_date)` on inspections.

---

## Part 7 — CSV Import System

See [CSV-FORMAT.md](CSV-FORMAT.md) for column specification.

### 7.1 Workflow

```text
Upload CSV
      ↓
Validate Headers & Row Data
      ↓
Normalize Phone Numbers (E.164, default CM)
      ↓
Detect Duplicates (plate + expiry per tenant)
      ↓
Dry-run OR Persist (async queue job)
      ↓
Upsert Customers / Vehicles / Inspections
      ↓
Generate Notification Schedules
```

### 7.2 Validation Rules

- Required columns present
- Valid phone numbers
- Valid dates (inspection date, expiry date)
- Expiry date in the future or within grace period
- Duplicate rows flagged, not double-inserted

### 7.3 Packages

- `maatwebsite/excel` — Import parsing
- `giggsey/libphonenumber-for-php` — Phone normalization

---

## Part 8 — Business Logic

### 8.1 Core Modules

| Module | Responsibility |
|--------|----------------|
| TenantModule | Center CRUD, settings, onboarding |
| CustomerModule | Customer CRUD, consent, preferences |
| VehicleModule | Vehicle CRUD linked to customer |
| InspectionModule | Inspection records, expiry tracking |
| ImportModule | CSV upload, validation, batch processing |
| NotificationModule | Schedule generation, dispatch, logging |

### 8.2 Notification Schedule Generation

On import or daily scheduler:

1. Find inspections with `expiry_date` in reminder windows (30, 15, 7, 1 days)
2. Respect customer `sms_opt_in` / `whatsapp_opt_in`
3. Create `notification_schedules` rows (idempotent unique key)
4. Scheduler marks due rows and dispatches queue jobs

---

## Part 9 — SMS Integration (Twilio)

### 9.1 Architecture

```text
Notification Scheduler
       ↓
SendNotificationJob (Queue)
       ↓
NotificationChannelInterface
       ↓
TwilioSmsChannel
       ↓
Twilio REST API
       ↓
Webhook → notification_logs.status
```

### 9.2 Provider Abstraction

`NotificationChannelInterface` allows future Orange/MTN channels without changing scheduler logic.

---

## Part 10 — WhatsApp Integration (Twilio)

### 10.1 Twilio WhatsApp Business API

- Template messages via approved Content SIDs
- Same Twilio client, `whatsapp:+` addressing
- Delivery status via shared webhook handler

See [NOTIFICATIONS.md](NOTIFICATIONS.md) for setup details.

---

## Part 11 — Queue & Scheduler

### 11.1 Redis Queue + Horizon

```env
QUEUE_CONNECTION=redis
```

```bash
php artisan horizon:install
php artisan horizon
```

### 11.2 Scheduler Tasks

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `notifications:dispatch-due` | Every 15 minutes | Send due reminders |
| `notifications:retry-failed` | Hourly | Retry transient failures |
| `imports:cleanup` | Daily | Archive old batch files |
| `logs:cleanup` | Weekly | Prune old notification logs |
| `backup:run` | Daily | Database backup (Spatie) |

Crontab entry:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Part 12 — Security

### 12.1 Application

- CSRF on web routes (exempt webhook routes)
- XSS prevention (Blade escaping)
- SQL injection prevention (Eloquent)
- Mass assignment protection (`$fillable` / `$guarded`)
- Encrypted casts for Twilio credentials and sensitive fields

### 12.2 Infrastructure

- HTTPS / SSL in production
- Firewall rules (ports 80, 443 only)
- Daily encrypted database backups
- Telescope and Horizon gated to authorized users

---

## Part 13 — Testing

See [TESTING.md](TESTING.md).

- Pest feature tests for CSV import, duplicate detection, schedule generation
- Mock Twilio client in tests
- Livewire component tests for upload UI
- GitHub Actions CI pipeline

---

## Part 14 — Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md).

- Nginx + PHP-FPM
- Supervisor or Horizon for queue workers
- Redis and MySQL on same VPC or managed services
- SSL via Let's Encrypt

---

## Part 15 — Monitoring

| Tool | Purpose |
|------|---------|
| Laravel Horizon | Queue monitoring |
| Laravel Telescope | Debugging (non-production) |
| `/health` endpoint | Uptime checks (DB, Redis, queue) |
| Sentry (optional) | Error tracking |
| Structured JSON logs | `tenant_id`, `batch_id` context |

---

## Part 16 — Delivery Phases

See [ROADMAP.md](ROADMAP.md) for timeline.

### Phase 1 — Foundation (2 weeks)

- Laravel scaffold, Docker Compose
- Multi-tenant middleware and models
- Breeze auth + Spatie roles
- Super-admin tenant CRUD

**Acceptance:** Super-admin can create a center; center-admin can log in to isolated dashboard.

### Phase 2 — Data Layer (2 weeks)

- Migrations for all core tables
- CSV import with dry-run and async processing
- Duplicate detection and phone normalization

**Acceptance:** CSV import creates customers, vehicles, inspections; duplicates skipped.

### Phase 3 — Notifications (2 weeks)

- Twilio SMS and WhatsApp channels
- Schedule generation and dispatcher
- Webhooks and retry policy

**Acceptance:** Due reminders sent via Twilio test credentials; status updated via webhook.

### Phase 4 — Frontend (3 weeks)

- Dashboard widgets and Chart.js analytics
- CRUD pages for customers, vehicles, inspections
- CSV upload UI with Livewire progress

**Acceptance:** Operator completes full workflow from UI without CLI.

### Phase 5 — Quality (1 week)

- Pest test suite (>80% on critical paths)
- GitHub Actions CI
- Pint, PHPStan, security hardening

**Acceptance:** CI green on `main`; 2FA enabled for admins.

### Phase 6 — Production (1 week)

- Nginx, Supervisor, Horizon deployment
- Backups, health checks, staging environment
- Production runbook

**Acceptance:** Application deployed to staging with real Twilio test sends.

---

## Part 17 — Final Production Features

- Daily CSV import automation (async, tracked)
- Automated expiry reminders (multi-window)
- SMS notifications via Twilio
- WhatsApp notifications via Twilio
- Professional analytics dashboard
- Customer, vehicle, and inspection management
- Enterprise queue processing (Horizon)
- Secure authentication with RBAC
- Multi-tenant SaaS architecture
- Audit logging and consent tracking
- Provider abstraction for future local carriers
- Future billing integration (Stripe-ready schema)

---

## Related Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md) — Technical design
- [DATABASE.md](DATABASE.md) — Schema reference
- [CSV-FORMAT.md](CSV-FORMAT.md) — Import specification
- [NOTIFICATIONS.md](NOTIFICATIONS.md) — Twilio integration
- [DEPLOYMENT.md](DEPLOYMENT.md) — Production setup
- [TESTING.md](TESTING.md) — Test plan
- [ROADMAP.md](ROADMAP.md) — Milestones and timeline
