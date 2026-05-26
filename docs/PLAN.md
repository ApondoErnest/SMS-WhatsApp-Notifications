# Visite Notify — Unified Development Plan

**Laravel Vehicle Inspection Notification Platform**
One Inspection Center First → Multi-Center SaaS Later
SMS via Africa's Talking + WhatsApp via Direct Meta Cloud API

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
- Send SMS through Africa's Talking
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
| Docker | Dockerize after core stability |
| Backend | Laravel |
| Language | PHP 8.3+ |
| Database | MySQL 8 |
| Frontend | Blade + Livewire + TailwindCSS |
| Authentication | Laravel Breeze (Blade stack) |
| Permissions | Spatie Laravel Permission |
| SMS | Africa's Talking |
| WhatsApp | Direct Meta WhatsApp Cloud API |
| Queue | Redis |
| Queue monitor | Laravel Horizon |
| Hosting | VPS |
| Web server | Nginx |
| Process manager | Supervisor |
| SSL | Let's Encrypt |
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
Africa's Talking SMS API  /  Meta WhatsApp Cloud API
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
12. SMS reminders sent via Africa's Talking
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

AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_SENDER_ID=your_sender_id

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
| provider | varchar(30) — africastalking / meta_whatsapp |
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

Generated from:
- `center_id`
- `licence_plate`
- `inspection_date`
- `expiration_date`
- `phone_number`
- `status`

Stored in `record_hash` column with `UNIQUE` constraint.

Prevents:
- Same CSV imported twice
- Same row duplicated
- Repeated daily imports creating duplicates

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

## 16. SMS Integration: Africa's Talking

### Responsibilities

- Send SMS reminders
- Handle API responses
- Store provider message ID
- Store delivery status
- Retry failed SMS
- Generate SMS reports

### Workflow

```
Due SMS schedule found
  → SendSmsNotificationJob dispatched
    → AfricaTalkingSmsService sends request
      → Provider response saved
        → notification_logs updated
          → Failed messages retried later
```

### Example SMS

> Cher client, votre visite technique pour le véhicule {licence_plate} expire le {expiration_date}. Veuillez passer au centre pour le renouvellement.

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
- Africa's Talking SMS Module
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
│   │   └── AfricaTalkingSmsService.php
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
| AfricaTalkingSmsService | Sends SMS via Africa's Talking |
| MetaWhatsAppService | Sends WhatsApp via Meta Cloud API |
| WebhookService | Processes provider callbacks |
| ReportService | Generates reports |
| AuditLogService | Tracks user actions |

---

## 23. Security Plan

- CSRF protection
- Input validation on all forms and uploads
- Secure password hashing (bcrypt)
- Role-based permissions (admin, operator)
- File upload validation (type, size)
- Rate limiting on login
- Audit logs for user actions
- Environment variable protection
- Webhook signature/token verification
- HTTPS in production (Let's Encrypt)
- Daily database backups
- Cloudflare CDN/WAF

---

## 24. VPS Deployment Plan

### Recommended Specs

- Ubuntu 24.04
- 4 GB RAM
- 2 vCPU
- 80 GB SSD

### Production Stack

```
Cloudflare → Nginx → PHP 8.3-FPM → Laravel App → Redis → MySQL
```

### Requirements

- Composer, Node.js, Git
- Certbot (Let's Encrypt SSL)
- Supervisor (queue workers, Horizon)
- Laravel Scheduler via cron
- Database backup strategy

### Queue Worker

```bash
php artisan queue:work redis --queue=imports,notifications,webhooks,default
```

---

## 25. Dockerization (After Stability)

Dockerize only after:
- CSV import works
- Duplicate prevention works
- Imported records table works
- Africa's Talking SMS works
- Meta WhatsApp works
- Queues work
- Reports work
- VPS deployment is stable

### Future Docker Services

- nginx
- app
- mysql
- redis
- queue-worker
- scheduler
- horizon

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

### Phase 2 — CSV Import Core
- Build upload page
- Validate CSV file (.csv, `;` separator)
- Validate headers (including `Regitration date` spelling)
- Parse semicolon-separated values
- Normalize dates
- Normalize phone numbers (E.164)
- Save valid rows to `inspection_records`
- Reject invalid rows to `failed_import_rows`
- Track failed row details

### Phase 3 — Duplicate Prevention
- Generate `record_hash` from center_id, licence_plate, inspection_date, expiration_date, phone_number, status
- Add UNIQUE database constraint
- Skip duplicate rows during import
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

### Phase 6 — Africa's Talking SMS
- Create SMS credentials settings
- Implement AfricaTalkingSmsService
- Create SendSmsNotificationJob
- Save SMS logs to notification_logs
- Retry failed SMS
- Generate SMS delivery reports

### Phase 7 — Meta WhatsApp Cloud API
- Create Meta credentials settings
- Implement MetaWhatsAppService
- Create message templates
- Create SendWhatsAppNotificationJob
- Create webhook endpoint for delivery reports
- Track delivered/read/failed statuses

### Phase 8 — Reports and Dashboard
- Import reports
- Expiry reports
- SMS delivery reports
- WhatsApp delivery reports
- Failed notification reports
- Daily activity reports
- Live dashboard card counts

### Phase 9 — Security and Hardening
- Validate all uploads
- Protect admin routes
- Add audit logs
- Rate-limit login
- Secure webhooks (signature verification)
- Add backup strategy

### Phase 10 — VPS Deployment
- Install server stack (Nginx, PHP-FPM, MySQL, Redis)
- Deploy Laravel app
- Configure Nginx virtual host
- Configure MySQL production
- Configure Redis production
- Configure Supervisor workers
- Configure Laravel scheduler cron
- Configure SSL (Let's Encrypt + Cloudflare)
- Test production SMS and WhatsApp

### Phase 11 — Stabilization
- Test real CSV files
- Test daily import workflow
- Test duplicate prevention
- Test SMS delivery to Cameroon numbers
- Test WhatsApp template approval and sending
- Test reports
- Optimize performance

### Phase 12 — Dockerization
- Create Dockerfile
- Create docker-compose.yml
- Containerize app
- Containerize queue workers
- Containerize scheduler
- Containerize Redis
- Containerize MySQL
- Test containerized deployment

### Phase 13 — Multi-Center SaaS Expansion
- Add multiple centers
- Add center switching for Super Admin
- Add tenant isolation middleware
- Add center-specific settings
- Add billing plans if needed
- Scale VPS resources

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
11. Africa's Talking SMS
12. Meta WhatsApp Cloud API
13. Reports
14. VPS deployment with Nginx
15. Stabilization
16. Dockerization
17. Multi-center SaaS expansion
