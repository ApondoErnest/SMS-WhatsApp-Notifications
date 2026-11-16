# Phase 11 — Stabilization

Burn-in checklist after Docker production deploy. Automated tests cover most of this; use the smoke sections for live VPS verification.

## Automated regression (local / CI)

```bash
php artisan test --filter='CsvImportTest|SmsNotificationTest|ReportsTest|NotificationSchedulingTest|MessagePersonalizerTest|WhatsAppNotificationTest|SecurityHardeningTest'
```

Expected: all green (CSV import + duplicates, Nexah SMS + DLR, reports, scheduling windows, WhatsApp Http::fake, security hardening, compact plates).

| Area | Coverage |
|------|----------|
| Real CSV / daily import | `CsvImportTest` — validation, chunking, large-file guards |
| Duplicate prevention | Same file — reimport, cross-center, within-file |
| Nexah SMS | `SmsNotificationTest` — send, language, balance, DLR |
| Reports | `ReportsTest` — overview, imports, expiry, notifications + CSV export |
| WhatsApp (offline) | `WhatsAppNotificationTest` — Meta template send + webhooks mocked |
| Scheduling | `NotificationSchedulingTest` — past reminder dates skipped, dispatch window |

## Production smoke (VPS)

Run on Hostinger after `git pull` + `up -d --build`.

### Preconditions

In `.env.docker`:

```bash
NOTIFICATIONS_SENDING_ENABLED=true
SMS_SENDING_ENABLED=true
```

Then:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan config:cache
```

### Checklist

1. **Health**
   - `curl -I https://notify.noblevehicletestingcompany.com/up` → 200
   - `curl -sI https://notify.noblevehicletestingcompany.com/livewire/livewire.min.js | head -n 1` → 200
2. **Solde SMS** — Centres page shows a numeric credit count after refresh
3. **CSV import** — upload a real centre CSV under the target center; import history shows completed with expected imported/duplicate/failed counts
4. **Duplicates** — re-upload the same file; rows land as duplicates, no second schedules for the same hash
5. **Past expiry** — a row whose all reminder dates are before today creates **no** pending SMS/WhatsApp schedules
6. **SMS** — due schedule (or one-shot `scripts/vps-import-and-sms.php`) delivers to a Cameroon number; message uses the **SMS** template and compact plate (`LT003MM`)
7. **Reports** — `/reports`, `/reports/imports`, `/reports/expiry`, `/reports/notifications` load; CSV exports download
8. **Queues** — `docker compose … logs --tail=50 worker scheduler` shows no repeating failures
9. **Nexah DLR** — callback URL registered (see [DEPLOY-VPS.md](DEPLOY-VPS.md) § Nexah DLR webhook)

## WhatsApp Meta go-live (deferred until real credentials)

Do **not** enable centre WhatsApp or live Meta sends until the items below are done. Offline unit/feature coverage already exists.

1. Create / confirm Meta WhatsApp Business app + phone number
2. Set production env (never commit secrets):

```bash
META_WHATSAPP_TOKEN=...
META_WHATSAPP_PHONE_NUMBER_ID=...
META_WHATSAPP_BUSINESS_ACCOUNT_ID=...
META_WHATSAPP_VERIFY_TOKEN=...
META_WHATSAPP_APP_SECRET=...   # enables X-Hub-Signature-256 verification
META_WHATSAPP_DEFAULT_TEMPLATE=visite_expiry_reminder
META_WHATSAPP_DEFAULT_LANGUAGE=fr
```

3. Approve template `visite_expiry_reminder` (FR + EN) in Meta Business Manager with named params: `customer_name`, `licence_plate`, `expiration_date`, `center_name` (plate without spaces)
4. Webhook callback: `https://notify.noblevehicletestingcompany.com/webhooks/meta/whatsapp`  
   Verify token must match `META_WHATSAPP_VERIFY_TOKEN`
5. Centre profile: enable WhatsApp; keep SMS independent
6. Smoke: import a due record → WhatsApp schedule → message delivered; opt-out / status webhooks update logs
7. Re-run `php artisan test --filter=WhatsAppNotificationTest` after any Meta client changes

## Performance knobs (Phase 11)

Applied in Compose / image (rebuild required):

| Knob | Where |
|------|--------|
| MySQL modest InnoDB / buffer | `docker/mysql/my.cnf` |
| Redis `maxmemory` + `allkeys-lru` | `docker-compose.prod.yml` redis `command` |
| Separate notifications worker | `worker-notifications` service |
| Slimmer PHP image (purge build packages) | `docker/php/Dockerfile` |
| Opcache / FPM pm | `docker/php/php.ini`, `docker/php/www.conf` |

Heavier hardening (resource limits, log shipping, blue-green) stays in **Phase 12**.
