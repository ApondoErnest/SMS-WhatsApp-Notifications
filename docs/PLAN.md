# Visite Notify — Unified Development Plan

**Laravel Vehicle Inspection Notification Platform**
One Inspection Center First → Multi-Center SaaS Later
SMS via Nexah (nexah.net) + WhatsApp via Direct Meta Cloud API

---

## 1. Project Direction

Build a Laravel web application for one roadworthiness inspection center first.

The system will:
- Allow staff to log in securely
- Import daily CSV inspection files
- Store inspection records in MySQL
- Prevent duplicate imports
- Display all imported data in a searchable table
- Track certificate expiry dates
- Schedule automatic reminders
- Send SMS through Nexah Bulk SMS API (https://nexah.net/)
- Send WhatsApp messages directly through Meta WhatsApp Cloud API
- Keep delivery logs
- Generate reports
- Later expand into a multi-center SaaS platform

---

## 2. Strategic Decisions

| Area | Decision |
|------|----------|
| Initial version | One inspection center |
| Future version | Multi-center SaaS |
| Development | Build locally first |
| Docker | **Yes — production runs in Docker on the VPS** |
| Backend | Laravel |
| Language | PHP 8.3+ |
| Database | MySQL 8 |
| Frontend | Blade + Livewire + TailwindCSS |
| Authentication | Laravel Breeze (Blade stack) |
| Permissions | Spatie Laravel Permission |
| SMS | Nexah Bulk SMS API (https://nexah.net/) |
| WhatsApp | Direct Meta WhatsApp Cloud API |
| Queue | Redis |
| Queue monitor | Laravel Horizon |
| Hosting | VPS (Docker Compose) |
| Web server | Nginx (container) |
| Process manager | Docker Compose services (app, queue, scheduler, Horizon) |
| SSL | Let's Encrypt (+ Cloudflare) |
| Security/CDN | Cloudflare |

---

## 3. System Architecture

```
Inspection Center Staff
        ↓
Laravel Web App
        ↓
CSV Import Engine
        ↓
MySQL Database
        ↓
Notification Scheduler
        ↓
Redis Queue
        ↓
Nexah Bulk SMS API  /  Meta WhatsApp Cloud API
        ↓
Delivery Logs + Reports
```

### Production Stack

```
Cloudflare → Nginx → PHP 8.3-FPM → Laravel App → Redis Queue → MySQL Database
```

---

## 4. Daily Business Workflow

1. Staff exports daily CSV file from inspection system
2. Staff logs into the app
3. Dashboard shows "Import CSV File"
4. Staff uploads CSV
5. System validates CSV structure and separator (`;`)
6. System validates each row
7. System normalizes phone numbers to E.164
8. System detects duplicates via `record_hash`
9. Valid records are saved to `inspection_records`
10. Invalid rows are stored in `failed_import_rows`
11. System creates reminder schedules
12. SMS reminders sent via Nexah
13. WhatsApp reminders sent via Meta Cloud API
14. Delivery status is stored in `notification_logs`
15. Reports are generated

---

## 5. CSV File Structure

Semicolon-delimited (`;`).

| Column | Maps to DB field |
|--------|------------------|
| `Regitration date` | `registration_date` |
| `Inspection date` | `inspection_date` |
| `Expiration date` | `expiration_date` |
| `Cat.` | `vehicle_class` |
| `Type` | `inspection_type` |
| `Licence plate` | `licence_plate` |
| `Category` | `vehicle_category` |
| `Customer` | `customer_name` |
| `Phone number` | `phone_number` |
| `Status` | `status` |

> **Important:** The column name is misspelled as `Regitration date` in the source CSV. The app must accept that exact spelling.

---

## 6. First Screen After Login (Dashboard)

Primary action: **Import CSV File** button.

Dashboard cards:
- Total imported records
- Imported today
- Duplicates skipped
- Failed rows
- Expiring this week
- Expiring this month
- SMS sent today
- WhatsApp sent today
- Failed notifications

---

## 7. Local Development Setup

### Prerequisites

- PHP 8.3+ with extensions
- Composer 2.x
- MySQL 8
- Redis
- Node.js 20+ and npm
- Git

### Laravel Setup

```bash
composer create-project laravel/laravel visite-notify
cd visite-notify
php artisan key:generate
```

### Authentication

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run dev
php artisan migrate
```

### Required Packages

```bash
composer require livewire/livewire
composer require spatie/laravel-permission
composer require maatwebsite/excel
composer require laravel/horizon
php artisan horizon:install
```

---

## 8. Environment Configuration

```
APP_NAME="Visite Notify"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=visite_notify
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

NEXAH_SMS_USER=your_nexah_username
NEXAH_SMS_PASSWORD=your_nexah_password
NEXAH_SMS_SENDER_ID=your_sender_id
NEXAH_SMS_BASE_URL=https://smsvas.com/bulk/public/index.php/api/v1

META_WHATSAPP_TOKEN=your_meta_access_token
META_WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
META_WHATSAPP_BUSINESS_ACCOUNT_ID=your_business_account_id
META_WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
```

---

## 9. Database Design

All tables include `center_id` from day one for future SaaS expansion.

### inspection_centers

| Column | Type |
|--------|------|
| id | bigint PK |
| name | varchar |
| phone | varchar(20) nullable |
| email | varchar nullable |
| address | text nullable |
| logo | varchar nullable |
| status | varchar(20) default 'active' |
| notification_language | varchar(5) default 'fr' (`fr` / `en`) — customer SMS/WhatsApp language |
| created_at, updated_at | timestamp |

### users

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| name | varchar |
| email | varchar unique |
| phone | varchar(20) nullable |
| password | varchar |
| role | varchar(30) default 'operator' |
| status | varchar(20) default 'active' |
| created_at, updated_at | timestamp |

### import_batches

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| uploaded_by | FK → users |
| filename | varchar |
| original_filename | varchar |
| total_rows | uint default 0 |
| imported_rows | uint default 0 |
| duplicate_rows | uint default 0 |
| failed_rows | uint default 0 |
| status | varchar(20) default 'pending' |
| created_at, updated_at | timestamp |

### inspection_records

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| import_batch_id | FK → import_batches |
| registration_date | date nullable |
| inspection_date | date nullable |
| expiration_date | date |
| vehicle_class | varchar(50) nullable |
| inspection_type | varchar(100) nullable |
| licence_plate | varchar(30), indexed |
| vehicle_category | varchar(50) nullable |
| customer_name | varchar, indexed |
| phone_number | varchar(30) |
| normalized_phone_number | varchar(20), indexed |
| status | varchar(30) |
| record_hash | varchar(64) UNIQUE |
| created_at, updated_at | timestamp |

### failed_import_rows

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| import_batch_id | FK → import_batches |
| row_number | uint |
| row_data | json |
| error_message | text |
| created_at, updated_at | timestamp |

### notification_schedules

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| inspection_record_id | FK → inspection_records |
| channel | varchar(20) — sms / whatsapp |
| scheduled_date | date |
| status | varchar(20) default 'pending' |
| attempts | tinyint default 0 |
| last_attempt_at | timestamp nullable |
| created_at, updated_at | timestamp |
| **Unique:** | (inspection_record_id, channel, scheduled_date) |

### notification_logs

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| inspection_record_id | FK → inspection_records |
| notification_schedule_id | FK nullable → notification_schedules |
| channel | varchar(20) |
| provider | varchar(30) — nexah / meta_whatsapp |
| phone_number | varchar(20) |
| message | text |
| provider_message_id | varchar(100) nullable, indexed |
| delivery_status | varchar(30) default 'pending' |
| error_message | text nullable |
| sent_at | timestamp nullable |
| created_at, updated_at | timestamp |

### notification_templates

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK → inspection_centers |
| channel | varchar(20) |
| language | varchar(10) default 'fr' |
| title | varchar |
| content | text |
| status | varchar(20) default 'active' |
| created_at, updated_at | timestamp |
| **Unique:** | (center_id, channel, language) |

### audit_logs

| Column | Type |
|--------|------|
| id | bigint PK |
| center_id | FK nullable → inspection_centers |
| user_id | FK nullable → users |
| action | varchar(100) |
| description | text nullable |
| ip_address | varchar nullable |
| created_at | timestamp |

---

## 10. CSV Import Module

### Workflow

1. Upload CSV
2. Validate file type (.csv)
3. Validate separator is `;`
4. Validate headers match expected columns
5. Validate each row
6. Normalize dates
7. Normalize phone numbers to E.164
8. Generate `record_hash`
9. Detect duplicates
10. Save valid records to `inspection_records`
11. Save failed rows to `failed_import_rows`
12. Show import summary
13. Generate reminder schedules

### Validation Rules

- File must be `.csv`
- Separator must be `;`
- Required columns must exist
- Licence plate must not be empty
- Customer name must not be empty
- Phone number must not be empty
- Inspection date must be valid
- Expiration date must be valid
- Status must not be empty
- Rows with missing expiration date → rejected and saved in `failed_import_rows`

---

## 11. Duplicate Prevention

### Record Hash

Generated from (center-agnostic — unique across the whole system):
- `licence_plate`
- `inspection_date`
- `expiration_date`
- `phone_number` (normalized)
- `status`

Stored in `record_hash` column with a **global** `UNIQUE` constraint.

Prevents:
- Same CSV imported twice
- Same row duplicated
- Repeated daily imports creating duplicates
- The same inspection existing in more than one center

---

## 12. Phone Number Normalization

Cameroon numbers:

| Input | Output |
|-------|--------|
| `677123456` | `+237677123456` |
| `237677123456` | `+237677123456` |
| `+237677123456` | `+237677123456` |

Store both `phone_number` (raw) and `normalized_phone_number` (E.164).
Use `normalized_phone_number` for SMS and WhatsApp.

---

## 13. Imported Records Page

### Columns

Registration date, Inspection date, Expiration date, Cat., Type, Licence plate, Category, Customer, Phone number, Status, Import date

### Features

- Search by customer name
- Search by licence plate
- Filter by expiry date
- Filter by status
- Filter by import batch
- Pagination
- Export to Excel/CSV
- View record details

---

## 14. Import History Page

### Columns

File name, Uploaded by, Upload date, Total rows, Imported rows, Duplicate rows, Failed rows, Status

### Actions

- View imported records
- View failed rows
- Download failed-row report

---

## 15. Notification Scheduling

### Default Reminder Rules

- 30 days before expiry
- 14 days before expiry
- 7 days before expiry
- 1 day before expiry

### Example

Expiration date: 15 December 2026

| Reminder | Date |
|----------|------|
| 30 days | 15 November 2026 |
| 14 days | 1 December 2026 |
| 7 days | 8 December 2026 |
| 1 day | 14 December 2026 |

Admin can enable/disable SMS and WhatsApp reminders independently.

---

## 16. SMS Integration: Nexah (Bulk SMS)

Provider: [Nexah](https://nexah.net/) — Cameroon SMS VAS / Bulk SMS platform.

Official API access is provisioned after commercial onboarding with Nexah support/sales. The REST interface used by partners is the Bulk SMS wrapper on `smsvas.com`.

### Requirements

- Nexah account (username + password) from https://nexah.net/
- Approved sender ID (`senderid`)
- Optional: partner DLR (delivery report) callback URL registered with Nexah
- Outbound HTTPS from the app to Nexah endpoints
- Inbound HTTPS webhook path for DLR callbacks (production)

### API Endpoints

Base URL (configurable via `NEXAH_SMS_BASE_URL`):

```
https://smsvas.com/bulk/public/index.php/api/v1
```

| Operation | Method | Path |
|-----------|--------|------|
| Send SMS | `POST` / `GET` | `/sendsms` |
| Account credit / balance | `POST` / `GET` | `/smscredit` |
| Delivery report (Nexah → us) | `POST` | Partner webhook URL (e.g. `/webhooks/nexah/sms`) |

Prefer `POST` with `Accept: application/json` and `Content-Type: application/json`.

### Send SMS request

| Parameter | Required | Description |
|-----------|----------|-------------|
| `user` | yes | Nexah login |
| `password` | yes | Nexah password |
| `senderid` | yes | Approved sender name |
| `sms` | yes | Message body |
| `mobiles` | yes | Comma-separated numbers; `237` prefix optional (e.g. `2376XXXXXXXX`, `6XXXXXXXX`) |

Example body:

```json
{
  "user": "user",
  "password": "password",
  "senderid": "SENDER",
  "sms": "message",
  "mobiles": "2376XXXXXXXX"
}
```

### Send SMS response (success)

`responsecode` = `1` on success, `0` on error. Per-recipient results appear under `sms[]` with `messageid`, `mobileno`, `status`, and optional error fields.

Store `messageid` as `notification_logs.provider_message_id` and set `provider` to `nexah`.

### Known error codes (send)

| Code | Meaning |
|------|---------|
| `-10019` | Inactive user |
| `-10003` | Invalid mobile number |
| `-10026` | Client SMS ID max limit exceeded |
| `-10008` | Balance not enough |

Other validation messages include missing credentials, missing message/sender, invalid sender ID, and invalid schedule date.

### Account balance

`POST /smscredit` with `user` + `password` returns `credit`, `accountexpdate`, and `balanceexpdate`. Useful for ops monitoring (optional Phase 6 stretch).

### Delivery reports (DLR)

Nexah `POST`s a JSON payload to the partner URL:

```json
{
  "dlrlist": [
    {
      "reponsecode": 1,
      "reponsedescription": "success",
      "messageid": "e12dd358cff3961752f8c1180cc209ff",
      "mobileno": "+237678018812",
      "status": "DELIVRD",
      "submittime": "2019-05-11 13:53:13",
      "senttime": "2019-05-11 13:53:14",
      "deliverytime": "2019-05-11 13:53:14"
    }
  ]
}
```

Status mapping:

| Provider status | Meaning | App delivery status |
|-----------------|---------|---------------------|
| `DELIVRD` (error code `1`) | Delivered | `delivered` |
| `UNDELIV` (error code `0`) | Not delivered | `failed` |

Partner must acknowledge by echoing `dlrlist` entries with `status` set to `1` (accepted) or `0` (rejected).

Handle DLR asynchronously via `ProcessSmsDeliveryReportJob` on the `webhooks` queue (same pattern as Meta WhatsApp webhooks).

### Environment variables

```
NEXAH_SMS_USER=your_nexah_username
NEXAH_SMS_PASSWORD=your_nexah_password
NEXAH_SMS_SENDER_ID=your_sender_id
NEXAH_SMS_BASE_URL=https://smsvas.com/bulk/public/index.php/api/v1
SMS_SENDING_ENABLED=false
```

Keep `SMS_SENDING_ENABLED=false` until credentials and templates are verified in a test send.

### Responsibilities

- Send SMS reminders via Nexah REST API
- Normalize destination numbers (Cameroon E.164 → Nexah-friendly `237…` / local form)
- Handle API responses and map error codes
- Store provider `messageid` and delivery status
- Accept Nexah DLR webhooks and update `notification_logs`
- Retry failed SMS
- Generate SMS delivery reports

### Workflow

```
Due SMS schedule found
  → SendSmsNotificationJob dispatched
    → NexahSmsService POST /sendsms
      → Provider response saved (messageid, status)
        → notification_logs updated (provider = nexah)
          → Nexah DLR webhook updates delivery status
          → Failed messages retried later
```

### Example SMS

> Cher client, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}. Veuillez passer au centre pour le renouvellement.

### Implementation notes

- HTTP client: Laravel `Http` facade calling Nexah REST directly (optional community package `gladdle/nexah-sms` exists but is not required)
- Service class: `app/Services/Sms/NexahSmsService.php`
- Webhook controller: `app/Http/Controllers/Webhooks/NexahSmsWebhookController.php`
- Route: `POST webhooks/nexah/sms` (CSRF-exempt), plus optional health/verify if Nexah requires it
- Phone format: reuse `PhoneNumberService` E.164 output; strip `+` before send if Nexah expects `237…`

---

## 17. WhatsApp Integration: Direct Meta Cloud API

### Meta Requirements

- Meta developer account
- Meta business account
- WhatsApp Business account
- Phone number connected to WhatsApp Business
- Permanent access token
- Phone Number ID
- Business Account ID
- Approved message templates
- Webhook callback URL
- Webhook verify token

### Responsibilities

- Send approved template messages
- Handle Meta API responses
- Receive webhook delivery statuses
- Store Meta message ID
- Track: sent, delivered, read, failed
- Retry failed WhatsApp messages

### Example WhatsApp Template

> Bonjour {{1}}, votre visite technique pour le véhicule {{2}} expire le {{3}}. Merci de passer au centre pour le renouvellement.

---

## 18. Queue and Scheduler System

### Queue Jobs

| Job | Queue |
|-----|-------|
| ProcessCsvImportJob | imports |
| GenerateNotificationSchedulesJob | default |
| SendSmsNotificationJob | notifications |
| SendWhatsAppNotificationJob | notifications |
| RetryFailedNotificationsJob | default |
| ProcessMetaWebhookJob | webhooks |
| ProcessSmsDeliveryReportJob | webhooks |

### Scheduler Tasks

- Check due notifications every minute
- Retry failed notifications
- Clean old temporary uploaded files
- Generate daily reports

### VPS Cron

```
* * * * * php /var/www/visite-notify/artisan schedule:run >> /dev/null 2>&1
```

---

## 19. Frontend Pages

| Page | Phase |
|------|-------|
| Login | 1 |
| Dashboard | 1 |
| Import CSV | 2 |
| Import Preview | 2 |
| Import Result | 2 |
| Import History | 4 |
| Imported Records | 4 |
| Record Details | 4 |
| Notification Schedules | 5 |
| SMS Logs | 6 |
| WhatsApp Logs | 7 |
| Templates | 7 |
| Reports | 8 |
| Users | 9 |
| Settings | 9 |

---

## 20. Backend Modules

- Authentication Module
- User Management Module
- Center Profile Module
- CSV Import Module
- Import History Module
- Inspection Records Module
- Duplicate Detection Module
- Notification Scheduler Module
- Nexah SMS Module
- Meta WhatsApp Module
- Webhook Module
- Reports Module
- Audit Log Module
- Settings Module

---

## 21. Laravel Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Middleware/
├── Models/
├── Services/
│   ├── CsvImport/
│   ├── Notification/
│   ├── Sms/
│   │   └── NexahSmsService.php
│   ├── WhatsApp/
│   │   └── MetaWhatsAppService.php
│   ├── Center/
│   └── Phone/
├── Jobs/
├── Policies/
├── Events/
├── Listeners/
└── Helpers/
```

---

## 22. Important Services

| Service | Responsibility |
|---------|----------------|
| CsvImportService | Reads and processes CSV |
| DuplicateDetectionService | Prevents duplicate records via record_hash |
| PhoneNumberService | Normalizes Cameroon phone numbers to E.164 |
| NotificationSchedulerService | Creates reminder dates |
| NexahSmsService | Sends SMS via Nexah Bulk SMS API |
| MetaWhatsAppService | Sends WhatsApp via Meta Cloud API |
| WebhookService | Processes provider callbacks |
| ReportService | Generates reports |
| AuditLogService | Tracks user actions |

---

## 23. Security Plan

- CSRF protection
- Input validation on all forms and uploads
- Secure password hashing (bcrypt)
- Role-based permissions (admin, operator, super-admin via Spatie)
- File upload validation (type, size, MIME)
- Rate limiting on login (`LOGIN_MAX_ATTEMPTS` / `LOGIN_DECAY_SECONDS`)
- Audit logs for user actions (`AuditLogService` + `/audit-logs`)
- Environment variable protection
- Webhook verification:
  - Meta: `X-Hub-Signature-256` when `META_WHATSAPP_APP_SECRET` is set
  - Nexah: shared token `NEXAH_SMS_WEBHOOK_TOKEN` (`X-Nexah-Token` or `?token=`)
- HTTPS in production (Let's Encrypt)
- Daily database backups: `php artisan backup:database` (scheduled 02:15, `DB_BACKUP_*`)
- Cloudflare CDN/WAF

---

## 24. VPS Deployment Plan (Docker)

Production is deployed **with Docker Compose on the VPS** — not a bare-metal PHP-FPM/Supervisor install.

### Recommended Specs

- Ubuntu 24.04
- 4 GB RAM
- 2 vCPU
- 80 GB SSD
- Docker Engine + Docker Compose plugin

### Production Stack

```
Cloudflare → host SSL (Caddy/Nginx/Certbot) → Docker Compose
  ├─ nginx          (reverse proxy to app)
  ├─ app            (Laravel PHP-FPM / Octane-ready image)
  ├─ queue-worker   (imports, notifications, webhooks, default)
  ├─ scheduler      (php artisan schedule:work)
  ├─ horizon        (optional monitor / supervisor alternative)
  ├─ redis
  └─ mysql
```

### Host requirements

- Docker + Compose
- Git (pull/deploy)
- TLS termination (Let's Encrypt on host or Traefik/Caddy in Compose)
- Persistent volumes for MySQL data, storage/app, and backups
- Cloudflare DNS / proxy in front

### Compose services (Phase 10 deliverables)

| Service | Role |
|---------|------|
| `nginx` | HTTP reverse proxy to `app` |
| `app` | Laravel application |
| `queue-worker` | `queue:work redis --queue=imports,notifications,webhooks,default` |
| `scheduler` | Laravel scheduler |
| `horizon` | Queue dashboard / balanced workers (optional alongside worker) |
| `redis` | Cache + queues |
| `mysql` | Production database |

### Deploy flow (target)

1. Clone/pull repo on VPS  
2. Copy production `.env`  
3. `docker compose build && docker compose up -d`  
4. `docker compose exec app php artisan migrate --force`  
5. Smoke-test SMS, WhatsApp, CSV import, webhooks  

### Backups

- Keep `backup:database` on the scheduler container  
- Persist dump files on a host-mounted volume (`DB_BACKUP_*`)

---

## 25. Docker Notes

Docker is the **default production path** (Phase 10), not a later add-on.

Local development may stay non-Docker (`npm run local` / Herd / Valet) until Compose is ready; production VPS must run Compose.

---

## 26. Future Multi-Center SaaS Expansion

Because `center_id` exists from day one, the app can later expand to:
- Multiple inspection centers
- Center-specific users
- Center-specific records
- Center-specific templates
- Center-specific reports
- Center-specific billing
- Subscription plans
- Tenant isolation
- Center switching for Super Admin

`center_id` becomes the tenant identifier.

---

## 27. Development Phases

### Phase 1 — Local Foundation
- Install Laravel
- Configure MySQL
- Install Breeze (Blade stack)
- Install Livewire
- Install Spatie permissions
- Create center profile
- Create users and roles (admin, operator)
- Create dashboard

### Phase 2 — CSV Import Core ✅ (hardened for large files)
- Build upload page
- Validate CSV file (.csv, `;` separator)
- Validate headers (including `Regitration date` spelling)
- Parse semicolon-separated values
- Normalize dates
- Normalize phone numbers (E.164)
- Save valid rows to `inspection_records` in **batched inserts** (default 500)
- Reject invalid rows to `failed_import_rows` (sample cap configurable)
- Progress fields on `import_batches` (`processed_rows`, `error_message`)
- Chunked post-import schedule generation
- Limits: `CSV_MAX_FILE_SIZE_MB` (64), `CSV_MAX_ROWS` (250000), import job timeout 3600s
- Horizon dedicated `imports` supervisor (higher memory/timeout)

### Phase 3 — Duplicate Prevention
- Generate global `record_hash` from licence_plate, inspection_date, expiration_date, normalized phone, status (not center-scoped)
- Add UNIQUE database constraint across all centers
- Skip duplicate rows during import (including when another center already has the record)
- Show duplicate count in import summary

### Phase 4 — Imported Records Management
- Create records table page with all columns
- Add search (customer, licence plate)
- Add filters (expiry date, status, import batch)
- Add pagination
- Add record detail view
- Add export to Excel/CSV

### Phase 5 — Notification Scheduling
- Generate reminder schedules (30, 14, 7, 1 days before expiry)
- Store SMS schedules
- Store WhatsApp schedules
- Show upcoming reminders
- Allow enabling/disabling reminder rules

### Phase 6 — Nexah SMS ✅
- Wire Nexah credentials via `.env` / `config/services.php`
- Implement `NexahSmsService` (`/sendsms`, `/smscredit`, DLR parse/ack)
- Implement `SendSmsNotificationJob` (queue: `notifications`)
- Save SMS logs to `notification_logs` with `provider = nexah`
- Nexah DLR webhook `POST /webhooks/nexah/sms` + `ProcessSmsDeliveryReportJob`
- Super-admin SMS balance shown inline on Centers (with refresh via `GET /sms-balance` JSON)
- Job retries (`$tries` / backoff); permanent errors fail immediately
- Feature tests: `tests/Feature/SmsNotificationTest.php`
- Deferred: dedicated SMS delivery reports UI (Phase 8)

### Phase 7 — Meta WhatsApp Cloud API
- Create Meta credentials settings
- Implement MetaWhatsAppService
- Create message templates
- Create SendWhatsAppNotificationJob
- Create webhook endpoint for delivery reports
- Track delivered/read/failed statuses

### Phase 8 — Reports and Dashboard ✅
- Reports hub at `/reports` (permission: `view-reports`)
- Import reports (`/reports/imports`)
- Expiry reports (`/reports/expiry` — week / month / expired / custom + CSV export)
- SMS & WhatsApp delivery reports (`/reports/notifications` + filters + CSV export)
- Failed notification filtering via status filter
- Daily activity table on overview
- Live dashboard card counts (existing) + link to Reports
- `App\Services\Reports\ReportService` + `tests/Feature/ReportsTest.php`

### Phase 9 — Security and Hardening ✅
- CSV upload validation (extension + MIME + size + empty-file check)
- Permission gates on imports, schedules/notifications, audit logs
- `AuditLogService` + UI (`/audit-logs`) for login, users, templates, center, imports, password
- Login rate limiting (`LOGIN_MAX_ATTEMPTS` / `LOGIN_DECAY_SECONDS`)
- Meta webhook `X-Hub-Signature-256` when `META_WHATSAPP_APP_SECRET` is set
- Nexah DLR shared token (`NEXAH_SMS_WEBHOOK_TOKEN` via header or `?token=`)
- Daily `backup:database` schedule + prune (`DB_BACKUP_*`)
- `tests/Feature/SecurityHardeningTest.php`

### Phase 10 — VPS Deployment (Docker Compose)
- Create production `Dockerfile` (+ multi-stage build for assets) — `docker/php/Dockerfile`
- Create `docker-compose.prod.yml` (nginx, app, mysql, redis, worker, scheduler)
- Add `.env.docker.example` + host Nginx site template (`deploy/vps/nginx-host/`)
- Deploy docs: `docs/DEPLOY-VPS.md` → Hostinger VPS `notify.noblevehicletestingcompany.com` (`127.0.0.1:8084`)
- Configure persistent volumes (MySQL, Redis, `storage`, backups)
- Configure SSL (host Certbot `--nginx`) alongside existing apps
- Run migrations, roles seed, `visite:create-super-admin`
- Test production SMS + webhooks (WhatsApp deferred until Meta creds ready)

### Phase 11 — Stabilization
- Test real CSV files
- Test daily import workflow
- Test duplicate prevention
- Test Nexah SMS delivery to Cameroon numbers
- Test WhatsApp template approval and sending
- Test reports
- Optimize performance (image size, worker counts, MySQL/Redis tuning)

### Phase 12 — Docker Production Hardening
- Healthchecks / restart policies
- Separate prod override compose (`docker-compose.prod.yml`)
- Log shipping / rotation
- Zero-downtime or blue-green deploy notes
- Secrets management refinements
- Resource limits per service

### Phase 13 — Multi-Center SaaS Expansion
- Add multiple centers
- Add center switching for Super Admin
- Add tenant isolation middleware
- Add center-specific settings
- Add billing plans if needed
- Scale VPS / Compose resources

---

## 28. Build Order

1. Laravel setup
2. Login system
3. Center profile
4. CSV upload
5. CSV validation
6. Database import
7. Duplicate prevention
8. Imported records table
9. Import history
10. Notification scheduling
11. Nexah SMS (https://nexah.net/)
12. Meta WhatsApp Cloud API
13. Reports
14. VPS deployment with Docker Compose
15. Stabilization
16. Docker production hardening
17. Multi-center SaaS expansion
