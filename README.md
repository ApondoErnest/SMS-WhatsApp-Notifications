# Visite Notify

Laravel web application for a roadworthiness inspection center (*Visite Technique*). Import daily CSV inspection files, track certificate expiry dates, and send automated SMS and WhatsApp reminders to customers.

**Strategy:** One inspection center first, multi-center SaaS later.

**SMS:** Africa's Talking | **WhatsApp:** Direct Meta WhatsApp Cloud API

## Features

- Secure staff login with role-based access (admin, operator)
- Daily CSV import (semicolon-delimited) with validation and preview
- Duplicate detection via record hash
- Searchable imported records table with filters, pagination, export
- Certificate expiry tracking and automatic reminder scheduling
- SMS reminders via Africa's Talking
- WhatsApp reminders via Meta WhatsApp Cloud API
- Delivery status tracking and logs
- Import history with failed-row reports
- Reports and analytics dashboard
- Prepared for future multi-center SaaS expansion (center_id in schema from day one)

## Tech Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel, PHP 8.3+ |
| Frontend | Blade + Livewire + Tailwind CSS |
| Database | MySQL 8 |
| Queue | Redis + Laravel Horizon |
| SMS | Africa's Talking |
| WhatsApp | Meta WhatsApp Cloud API (direct) |
| Auth | Laravel Breeze (Blade) |
| Permissions | Spatie Laravel Permission |
| CSV Import | Maatwebsite Excel |
| Web server | Nginx (production) |
| SSL | Let's Encrypt |
| CDN/Security | Cloudflare |

## CSV File Format

Semicolon-delimited (`;`), with these columns:

| Column | Notes |
|--------|-------|
| `Regitration date` | Intentional spelling from source system |
| `Inspection date` | |
| `Expiration date` | |
| `Cat.` | Vehicle class |
| `Type` | Inspection type |
| `Licence plate` | |
| `Category` | Vehicle category |
| `Customer` | Customer name |
| `Phone number` | Cameroon phone numbers |
| `Status` | |

## Prerequisites

- PHP 8.3+ with extensions: cli, mysql, curl, xml, mbstring, zip, bcmath, gd
- Composer 2.x
- MySQL 8
- Redis
- Node.js 20+ and npm
- Git

## Quick Start

```bash
git clone <repository-url>
cd SmS-WhatsApp-Notifications

composer install
npm install

cp .env.example .env
php artisan key:generate

# Create MySQL database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS visite_notify"

php artisan migrate --seed
npm run dev

php artisan serve
```

Access at `http://localhost:8000`.

### Demo Accounts

| Email | Password | Role |
|-------|----------|------|
| `admin@visite-notify.local` | `password` | admin |
| `operateur@visite-notify.local` | `password` | operator |

## Environment Variables

See [`.env.example`](.env.example). Key settings:

| Variable | Description |
|----------|-------------|
| `DB_DATABASE` | `visite_notify` |
| `QUEUE_CONNECTION` | `redis` |
| `AFRICASTALKING_USERNAME` | Africa's Talking username |
| `AFRICASTALKING_API_KEY` | Africa's Talking API key |
| `AFRICASTALKING_SENDER_ID` | SMS sender ID |
| `META_WHATSAPP_TOKEN` | Meta permanent access token |
| `META_WHATSAPP_PHONE_NUMBER_ID` | WhatsApp phone number ID |
| `META_WHATSAPP_BUSINESS_ACCOUNT_ID` | WhatsApp business account ID |
| `META_WHATSAPP_VERIFY_TOKEN` | Webhook verification token |

## Database Schema

All tables include `center_id` for future multi-center expansion.

| Table | Purpose |
|-------|---------|
| `inspection_centers` | Center profile |
| `users` | Staff accounts |
| `import_batches` | CSV upload tracking |
| `inspection_records` | Inspection data with record_hash |
| `failed_import_rows` | Rejected CSV rows |
| `notification_schedules` | Reminder schedule per record |
| `notification_logs` | Send history and delivery status |
| `notification_templates` | SMS/WhatsApp message templates |
| `audit_logs` | User action tracking |

## Project Structure

```
app/
├── Http/Controllers/         # Auth, dashboard (more in later phases)
├── Jobs/                     # ProcessCsvImport, SendSms, SendWhatsApp, etc.
├── Models/                   # InspectionRecord, ImportBatch, NotificationLog, etc.
├── Services/
│   ├── CsvImport/            # CsvImportService, DuplicateDetectionService
│   ├── Phone/                # PhoneNumberService (E.164 normalization)
│   ├── Notification/         # NotificationSchedulerService
│   ├── Sms/                  # AfricaTalkingSmsService
│   └── WhatsApp/             # MetaWhatsAppService
```

## Development Phases

| Phase | Scope |
|-------|-------|
| 1 | Foundation: Laravel, Breeze auth, center profile, dashboard |
| 2 | CSV import core: upload, validate, normalize, save |
| 3 | Duplicate prevention: record_hash, unique constraint |
| 4 | Imported records: table, search, filters, export |
| 5 | Notification scheduling: reminder dates, enable/disable |
| 6 | Africa's Talking SMS integration |
| 7 | Meta WhatsApp Cloud API integration |
| 8 | Reports and dashboard analytics |
| 9 | Security hardening and audit logs |
| 10 | VPS deployment (Nginx, Supervisor, SSL) |
| 11 | Stabilization and real-world testing |
| 12 | Dockerization |
| 13 | Multi-center SaaS expansion |

See [`docs/PLAN.md`](docs/PLAN.md) for the full plan.

## License

Proprietary -- All rights reserved.
