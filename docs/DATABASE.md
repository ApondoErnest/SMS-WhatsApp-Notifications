# Database Design — Visite Technique Platform

Schema reference for the multi-tenant MySQL database.

---

## Entity Relationship Diagram

```mermaid
erDiagram
    inspection_centers ||--o{ users : has
    inspection_centers ||--|| center_settings : has
    inspection_centers ||--o{ customers : has
    inspection_centers ||--o{ imported_batches : has
    inspection_centers ||--o{ message_templates : has
    inspection_centers ||--o{ subscriptions : has

    customers ||--o{ vehicles : owns
    vehicles ||--o{ inspections : has
    inspections ||--o{ notification_schedules : generates
    notification_schedules ||--o{ notification_logs : logs

    plans ||--o{ subscriptions : offers

    inspection_centers {
        bigint id PK
        uuid uuid UK
        string name
        string slug UK
        boolean is_active
        timestamps created_at updated_at
        timestamp deleted_at
    }

    users {
        bigint id PK
        bigint inspection_center_id FK
        string name
        string email UK
        string password
        timestamps created_at updated_at
    }

    customers {
        bigint id PK
        uuid uuid UK
        bigint inspection_center_id FK
        string full_name
        string phone_e164
        boolean sms_opt_in
        boolean whatsapp_opt_in
        timestamp marketing_consent_at
        timestamps created_at updated_at
        timestamp deleted_at
    }

    vehicles {
        bigint id PK
        uuid uuid UK
        bigint inspection_center_id FK
        bigint customer_id FK
        string license_plate
        string make
        string model
        timestamps created_at updated_at
        timestamp deleted_at
    }

    inspections {
        bigint id PK
        uuid uuid UK
        bigint inspection_center_id FK
        bigint vehicle_id FK
        bigint imported_batch_id FK
        date inspection_date
        date expiry_date
        string certificate_number
        timestamps created_at updated_at
        timestamp deleted_at
    }
```

---

## Tenant Scoping Rules

1. Every table below (except `plans` and platform-level tables) includes `inspection_center_id`.
2. `BelongsToTenant` trait applies `TenantScope` global scope.
3. Super-admin queries use `Model::withoutGlobalScopes()`.
4. Foreign keys cascade on tenant soft-delete (configurable; default restrict with soft-delete on center).

---

## Table Specifications

### `inspection_centers`

Tenant root entity.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `uuid` | CHAR(36) | UNIQUE | Public identifier |
| `name` | VARCHAR(255) | NOT NULL | Display name |
| `slug` | VARCHAR(100) | UNIQUE | URL-safe identifier |
| `is_active` | BOOLEAN | DEFAULT true | Disable to block logins |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete |

**Indexes:** `slug`, `is_active`

---

### `center_settings`

One row per inspection center.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `inspection_center_id` | BIGINT UNSIGNED | FK, UNIQUE | |
| `twilio_account_sid` | TEXT | NULLABLE | Encrypted cast |
| `twilio_auth_token` | TEXT | NULLABLE | Encrypted cast |
| `twilio_sms_from` | VARCHAR(20) | NULLABLE | |
| `twilio_whatsapp_from` | VARCHAR(20) | NULLABLE | |
| `default_phone_country` | CHAR(2) | DEFAULT 'CM' | ISO 3166-1 alpha-2 |
| `locale` | VARCHAR(5) | DEFAULT 'fr' | |
| `timezone` | VARCHAR(50) | DEFAULT 'Africa/Douala' | |
| `reminder_days` | JSON | | e.g. `[30,15,7,1]` |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

### `users`

Extended Laravel users table.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `inspection_center_id` | BIGINT UNSIGNED | FK, NULLABLE | Null for super-admin |
| `name` | VARCHAR(255) | NOT NULL | |
| `email` | VARCHAR(255) | UNIQUE | |
| `password` | VARCHAR(255) | NOT NULL | |
| `email_verified_at` | TIMESTAMP | NULLABLE | |
| `two_factor_secret` | TEXT | NULLABLE | Fortify 2FA |
| `remember_token` | VARCHAR(100) | NULLABLE | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:** `inspection_center_id`, `email`

---

### `customers`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `uuid` | CHAR(36) | UNIQUE | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `full_name` | VARCHAR(255) | NOT NULL | |
| `phone_e164` | VARCHAR(20) | NOT NULL | Normalized E.164 |
| `email` | VARCHAR(255) | NULLABLE | |
| `sms_opt_in` | BOOLEAN | DEFAULT true | |
| `whatsapp_opt_in` | BOOLEAN | DEFAULT true | |
| `marketing_consent_at` | TIMESTAMP | NULLABLE | GDPR-style consent |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Indexes:**
- `idx_customers_tenant_phone` — (`inspection_center_id`, `phone_e164`)
- `idx_customers_tenant_name` — (`inspection_center_id`, `full_name`)

---

### `vehicles`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `uuid` | CHAR(36) | UNIQUE | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `customer_id` | BIGINT UNSIGNED | FK | |
| `license_plate` | VARCHAR(20) | NOT NULL | Normalized uppercase |
| `make` | VARCHAR(100) | NULLABLE | |
| `model` | VARCHAR(100) | NULLABLE | |
| `year` | SMALLINT | NULLABLE | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Indexes:**
- `idx_vehicles_tenant_plate` — UNIQUE (`inspection_center_id`, `license_plate`)

---

### `inspections`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `uuid` | CHAR(36) | UNIQUE | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `vehicle_id` | BIGINT UNSIGNED | FK | |
| `imported_batch_id` | BIGINT UNSIGNED | FK, NULLABLE | |
| `inspection_date` | DATE | NOT NULL | |
| `expiry_date` | DATE | NOT NULL | Certificate expiry |
| `certificate_number` | VARCHAR(100) | NULLABLE | |
| `result` | ENUM | NULLABLE | `pass`, `fail`, `conditional` |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |
| `deleted_at` | TIMESTAMP | NULLABLE | |

**Indexes:**
- `idx_inspections_dedup` — UNIQUE (`inspection_center_id`, `vehicle_id`, `expiry_date`)
- `idx_inspections_expiry` — (`inspection_center_id`, `expiry_date`)

Duplicate import detection uses `license_plate` (via vehicle) + `expiry_date` + `inspection_center_id`.

---

### `imported_batches`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `uuid` | CHAR(36) | UNIQUE | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `user_id` | BIGINT UNSIGNED | FK | Uploader |
| `filename` | VARCHAR(255) | NOT NULL | Original name |
| `storage_path` | VARCHAR(500) | NOT NULL | |
| `status` | ENUM | NOT NULL | `pending`, `processing`, `completed`, `failed` |
| `is_dry_run` | BOOLEAN | DEFAULT false | |
| `total_rows` | INT UNSIGNED | DEFAULT 0 | |
| `processed_rows` | INT UNSIGNED | DEFAULT 0 | |
| `created_count` | INT UNSIGNED | DEFAULT 0 | |
| `updated_count` | INT UNSIGNED | DEFAULT 0 | |
| `skipped_count` | INT UNSIGNED | DEFAULT 0 | |
| `error_count` | INT UNSIGNED | DEFAULT 0 | |
| `error_log` | JSON | NULLABLE | Row-level errors |
| `started_at` | TIMESTAMP | NULLABLE | |
| `completed_at` | TIMESTAMP | NULLABLE | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:** (`inspection_center_id`, `status`, `created_at`)

---

### `notification_schedules`

Generated reminders (idempotent).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `inspection_id` | BIGINT UNSIGNED | FK | |
| `channel` | ENUM | NOT NULL | `sms`, `whatsapp` |
| `reminder_days_before` | TINYINT | NOT NULL | 30, 15, 7, 1 |
| `scheduled_at` | TIMESTAMP | NOT NULL | When to send |
| `status` | ENUM | NOT NULL | `pending`, `sent`, `failed`, `cancelled` |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:**
- `idx_schedules_dedup` — UNIQUE (`inspection_id`, `channel`, `reminder_days_before`)
- `idx_schedules_due` — (`status`, `scheduled_at`)

---

### `notification_logs`

Actual send attempts and delivery status.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `notification_schedule_id` | BIGINT UNSIGNED | FK | |
| `customer_id` | BIGINT UNSIGNED | FK | |
| `channel` | ENUM | NOT NULL | `sms`, `whatsapp` |
| `recipient` | VARCHAR(20) | NOT NULL | E.164 or whatsapp address |
| `message_body` | TEXT | NOT NULL | Rendered content |
| `provider_message_id` | VARCHAR(100) | NULLABLE | Twilio SID |
| `status` | ENUM | NOT NULL | `queued`, `sent`, `delivered`, `failed`, `undelivered` |
| `error_code` | VARCHAR(50) | NULLABLE | |
| `error_message` | TEXT | NULLABLE | |
| `sent_at` | TIMESTAMP | NULLABLE | |
| `delivered_at` | TIMESTAMP | NULLABLE | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:**
- `idx_logs_provider` — (`provider_message_id`)
- `idx_logs_tenant_status` — (`inspection_center_id`, `status`, `created_at`)

---

### `message_templates`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | BIGINT UNSIGNED | PK, AI | |
| `inspection_center_id` | BIGINT UNSIGNED | FK | |
| `channel` | ENUM | NOT NULL | `sms`, `whatsapp` |
| `name` | VARCHAR(100) | NOT NULL | |
| `body` | TEXT | NOT NULL | With `{{placeholders}}` |
| `twilio_content_sid` | VARCHAR(100) | NULLABLE | WhatsApp template SID |
| `is_active` | BOOLEAN | DEFAULT true | |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

**Indexes:** UNIQUE (`inspection_center_id`, `channel`, `name`)

---

### `plans` (SaaS placeholder)

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(100) | e.g. Starter, Pro |
| `slug` | VARCHAR(50) UNIQUE | |
| `max_imports_per_month` | INT | |
| `max_notifications_per_month` | INT | |
| `price_monthly_cents` | INT | Stripe-ready |
| `is_active` | BOOLEAN | |

---

### `subscriptions` (SaaS placeholder)

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | |
| `inspection_center_id` | FK | |
| `plan_id` | FK | |
| `status` | ENUM | `active`, `cancelled`, `past_due` |
| `starts_at` | TIMESTAMP | |
| `ends_at` | TIMESTAMP NULLABLE | |
| `stripe_subscription_id` | VARCHAR NULLABLE | Phase 2 billing |

---

## Spatie Permission Tables

Standard tables from `spatie/laravel-permission`:

- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

Roles: `super-admin`, `center-admin`, `operator`

---

## Activity Log

`activity_log` table from `spatie/laravel-activitylog` — tracks imports, settings changes, manual sends.

---

## Migration Order

1. `inspection_centers`, `center_settings`
2. `users` (extend default migration)
3. Spatie permission tables
4. `customers`, `vehicles`, `inspections`
5. `imported_batches`
6. `message_templates`
7. `notification_schedules`, `notification_logs`
8. `plans`, `subscriptions`

---

## Seed Data (Development)

| Seeder | Data |
|--------|------|
| `RoleSeeder` | super-admin, center-admin, operator roles |
| `PlanSeeder` | Starter, Pro plans |
| `DemoTenantSeeder` | One inspection center with sample customers |

---

## Related Documentation

- [CSV-FORMAT.md](CSV-FORMAT.md) — Import columns mapped to these tables
- [ARCHITECTURE.md](ARCHITECTURE.md) — Service layer using this schema
