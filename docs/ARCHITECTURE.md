# System Architecture — Visite Technique Platform

Technical design for the multi-tenant Laravel SMS/WhatsApp notification platform.

---

## High-Level Architecture

```mermaid
flowchart TB
    subgraph clients [Clients]
        Browser[Web Browser]
        TwilioWH[Twilio Webhooks]
    end

    subgraph presentation [Presentation Layer]
        Blade[Blade Views]
        Livewire[Livewire Components]
    end

    subgraph application [Application Layer]
        Controllers[HTTP Controllers]
        Middleware[TenantScope Middleware]
        Services[Domain Services]
        Jobs[Queue Jobs]
    end

    subgraph domain [Domain Layer]
        Models[Eloquent Models]
        Events[Domain Events]
    end

    subgraph infrastructure [Infrastructure]
        MySQL[(MySQL)]
        Redis[(Redis)]
        TwilioAPI[Twilio API]
        Storage[File Storage]
    end

    Browser --> Blade
    Browser --> Livewire
    Livewire --> Controllers
    Controllers --> Middleware
    Middleware --> Services
    Services --> Models
    Services --> Jobs
    Jobs --> Redis
    Jobs --> TwilioAPI
    TwilioWH --> Controllers
    Models --> MySQL
    Services --> Storage
```

---

## Multi-Tenancy Design

### Tenant Model

Each **inspection center** is a tenant (`inspection_centers` table). All business data is scoped by `inspection_center_id`.

```mermaid
flowchart LR
    SuperAdmin[Super Admin]
    CenterA[Center A Tenant]
    CenterB[Center B Tenant]

    SuperAdmin -->|manages| CenterA
    SuperAdmin -->|manages| CenterB

    CenterA --> DataA[(Scoped Data A)]
    CenterB --> DataB[(Scoped Data B)]
```

### Tenant Resolution

1. User authenticates via Laravel Breeze
2. `users.inspection_center_id` set (null for super-admin)
3. `TenantScope` middleware sets `app('currentTenant')` from authenticated user
4. `BelongsToTenant` global scope filters all Eloquent queries
5. Super-admin routes use `withoutGlobalScope(TenantScope::class)` explicitly

### Key Classes (Planned)

| Class | Responsibility |
|-------|----------------|
| `TenantScope` | Eloquent global scope filtering by `inspection_center_id` |
| `BelongsToTenant` | Trait applied to tenant-scoped models |
| `SetCurrentTenant` | Middleware resolving tenant from auth user |
| `TenantService` | Tenant CRUD, settings, onboarding |

### Data Isolation Rules

- Never query tenant data without scope (except super-admin)
- Foreign keys include `inspection_center_id` on child tables
- Composite unique indexes include `inspection_center_id`
- File uploads stored under `storage/app/tenants/{center_id}/imports/`

---

## Service Layer

```mermaid
flowchart LR
    subgraph import_services [Import]
        CsvUploadService[CsvUploadService]
        CsvValidator[CsvValidator]
        ImportProcessor[ImportProcessor]
        PhoneNormalizer[PhoneNormalizer]
    end

    subgraph notification_services [Notifications]
        ScheduleGenerator[ScheduleGenerator]
        NotificationDispatcher[NotificationDispatcher]
        TemplateRenderer[TemplateRenderer]
    end

    subgraph channels [Channels]
        ChannelInterface[NotificationChannelInterface]
        TwilioSms[TwilioSmsChannel]
        TwilioWa[TwilioWhatsAppChannel]
    end

    CsvUploadService --> CsvValidator
    CsvValidator --> ImportProcessor
    ImportProcessor --> PhoneNormalizer
    ImportProcessor --> ScheduleGenerator
    ScheduleGenerator --> NotificationDispatcher
    NotificationDispatcher --> ChannelInterface
    ChannelInterface --> TwilioSms
    ChannelInterface --> TwilioWa
    NotificationDispatcher --> TemplateRenderer
```

### Service Responsibilities

| Service | Methods (illustrative) | Notes |
|---------|------------------------|-------|
| `CsvUploadService` | `store()`, `validate()`, `dispatchImport()` | Handles file storage and batch creation |
| `CsvValidator` | `validateHeaders()`, `validateRow()` | Returns row-level errors |
| `ImportProcessor` | `processBatch()`, `upsertRecords()` | Runs in `ProcessImportJob` |
| `PhoneNormalizer` | `toE164()` | libphonenumber, tenant default country |
| `ScheduleGenerator` | `generateForInspection()` | Idempotent schedule rows |
| `NotificationDispatcher` | `dispatchDue()`, `send()` | Called by scheduler and jobs |
| `TemplateRenderer` | `render()` | Replaces `{{placeholders}}` |

---

## CSV Import Flow

```mermaid
sequenceDiagram
    participant User
    participant Livewire as UploadComponent
    participant Service as CsvUploadService
    participant Queue as Redis Queue
    participant Job as ProcessImportJob
    participant DB as MySQL

    User->>Livewire: Upload CSV
    Livewire->>Service: validate dry-run
    Service-->>Livewire: Preview results
    User->>Livewire: Confirm import
    Livewire->>Service: dispatchImport
    Service->>DB: Create imported_batch pending
    Service->>Queue: ProcessImportJob
    Queue->>Job: Execute
    Job->>DB: Upsert customers vehicles inspections
    Job->>DB: Update batch status completed
    Job->>DB: Generate notification schedules
    Job-->>Livewire: Poll progress
```

---

## Notification Flow

```mermaid
sequenceDiagram
    participant Scheduler as Laravel Scheduler
    participant Dispatcher as NotificationDispatcher
    participant Queue as Redis Queue
    participant Job as SendNotificationJob
    participant Twilio as Twilio API
    participant Webhook as Webhook Controller
    participant DB as MySQL

    Scheduler->>Dispatcher: notifications:dispatch-due
    Dispatcher->>DB: Load due notification_schedules
    Dispatcher->>Queue: SendNotificationJob per schedule
    Queue->>Job: Execute
    Job->>Twilio: Send SMS or WhatsApp
    Twilio-->>Job: Message SID
    Job->>DB: Create notification_log sent
    Twilio->>Webhook: Status callback
    Webhook->>DB: Update notification_log delivered/failed
```

---

## Queue Architecture

| Queue | Jobs | Priority |
|-------|------|----------|
| `imports` | `ProcessImportJob` | Normal |
| `notifications` | `SendNotificationJob` | High |
| `default` | `RetryFailedNotificationJob` | Low |

Horizon supervises workers per queue. Failed jobs after 3 retries move to `failed_jobs` table for manual review.

### Retry Policy

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 5 minutes |
| 3 | 30 minutes |

Permanent failures (invalid number, template rejected) are not retried; status set to `failed` with error message.

---

## Authentication & Authorization Flow

```mermaid
flowchart TD
    Request[HTTP Request]
    AuthMiddleware[auth middleware]
    RoleMiddleware[role middleware]
    TenantMiddleware[SetCurrentTenant]
    Controller[Controller Action]

    Request --> AuthMiddleware
    AuthMiddleware -->|authenticated| RoleMiddleware
    RoleMiddleware -->|authorized| TenantMiddleware
    TenantMiddleware --> Controller
```

Super-admin routes: prefix `/admin`, middleware `role:super-admin`, no tenant scope.

Tenant routes: prefix `/app`, middleware `role:center-admin|operator`, tenant scope active.

---

## Webhook Architecture

Twilio callbacks hit stateless routes (CSRF exempt):

| Route | Method | Purpose |
|-------|--------|---------|
| `/webhooks/twilio/sms` | POST | SMS delivery status |
| `/webhooks/twilio/whatsapp` | POST | WhatsApp delivery status |

Validation: `Twilio\Security\RequestValidator` with `X-Twilio-Signature` header.

Lookup `notification_logs` by `provider_message_id` (Twilio Message SID) and update `status`, `delivered_at`, `error_code`.

---

## Caching Strategy

| Key pattern | TTL | Purpose |
|-------------|-----|---------|
| `tenant:{id}:settings` | 1 hour | Center settings |
| `tenant:{id}:dashboard` | 5 minutes | Dashboard widget aggregates |
| `platform:metrics` | 15 minutes | Super-admin metrics |

Invalidate on settings update or import completion.

---

## File Storage

```
storage/app/tenants/{inspection_center_id}/imports/{batch_uuid}.csv
```

Retention: 90 days, cleaned by `imports:cleanup` scheduler.

---

## Health Check

`GET /health` returns JSON:

```json
{
  "status": "ok",
  "checks": {
    "database": "ok",
    "redis": "ok",
    "queue": "ok"
  }
}
```

Used by load balancers and uptime monitors. Returns `503` if any check fails.

---

## Localization

- Default locale: `fr` (French)
- Fallback: `en` (English)
- Per-tenant override via `center_settings.locale`
- Message templates stored per tenant in French with optional English variant

---

## Future Extensibility

| Extension | Approach |
|-----------|----------|
| Orange / MTN SMS | New `NotificationChannelInterface` implementation |
| Stripe billing | Activate `plans` / `subscriptions` tables |
| Mobile API | Laravel Sanctum token auth, same tenant scope |
| Multi-region | Tenant `timezone` on `center_settings` |

---

## Related Documentation

- [DATABASE.md](DATABASE.md) — Schema and indexes
- [NOTIFICATIONS.md](NOTIFICATIONS.md) — Twilio integration
- [PLAN.md](PLAN.md) — Development phases
