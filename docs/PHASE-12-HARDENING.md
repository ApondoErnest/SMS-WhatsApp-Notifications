# Phase 12 — Docker Production Hardening

Ops polish for the Hostinger VPS stack. Product features stay in earlier phases.

## What changed

| Area | Implementation |
|------|----------------|
| Healthchecks | Every service (`app` FPM port, `nginx` `/up`, MySQL/Redis ping, workers/scheduler `/proc/1/cmdline`) |
| Restart policy | `restart: unless-stopped` on all services |
| Resource limits | `cpus` / `mem_limit` / `mem_reservation` per service in [`docker-compose.prod.yml`](../docker-compose.prod.yml) |
| Log rotation | Docker `json-file` driver: `max-size=10m`, `max-file=5`; Laravel `LOG_STACK=daily` |
| Secrets | `.env.docker` gitignored + `chmod 600`; checklist below |
| Compose split | Local: `docker-compose.yml` · Production: `docker-compose.prod.yml` only |
| Deploy notes | Rolling update + rollback in [DEPLOY-VPS.md](DEPLOY-VPS.md) § Phase 12 |

## Secrets checklist (VPS)

```bash
cd /var/www/visite-notify
cp -n .env.docker.example .env.docker
chmod 600 .env.docker
# Confirm ignored by git:
git check-ignore -v .env.docker
```

Keep out of git and chat logs:

- `APP_KEY`
- `DB_PASSWORD` / `MYSQL_ROOT_PASSWORD`
- `NEXAH_SMS_PASSWORD` / `NEXAH_SMS_WEBHOOK_TOKEN`
- Meta WhatsApp token / app secret (when enabled)

Rotate `NEXAH_SMS_WEBHOOK_TOKEN` if it was ever pasted into chat or a ticket; update the Nexah DLR URL at the same time.

Optional later (not required on this VPS): Docker Swarm/Kubernetes secrets, or a host-mounted secret file outside the repo.

## Log rotation

**Container stdout/stderr** — capped by Compose logging options (10 MB × 5 files per service).

**Laravel files** — with `LOG_STACK=daily` and `LOG_DAILY_DAYS=14`, use:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app \
  php artisan log:clear   # if available, or prune storage/logs/*.log older than 14 days
```

Host Nginx access/error logs stay under the OS `logrotate` config (unchanged).

## Rolling deploy (low downtime)

Prefer this over a blind `down`/`up` on production:

```bash
cd /var/www/visite-notify
git pull

# 1) Build new images without cutting traffic yet
docker compose -f docker-compose.prod.yml --env-file .env.docker build

# 2) Migrate while old app still serves (keep migrations backward-compatible)
docker compose -f docker-compose.prod.yml --env-file .env.docker run --rm --no-deps app \
  php artisan migrate --force

# 3) Roll app → nginx → workers/scheduler
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --no-deps --build app
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --no-deps --build nginx
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --no-deps worker worker-notifications scheduler

# 4) Cache
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan config:cache
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan route:cache
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan view:cache

# 5) Smoke
curl -fsS -o /dev/null -w "%{http_code}\n" https://notify.noblevehicletestingcompany.com/up
docker compose -f docker-compose.prod.yml --env-file .env.docker ps
```

True blue-green (two stacks + host Nginx upstream flip) is optional on this shared VPS; the rolling sequence above is the default.

## Rollback

```bash
cd /var/www/visite-notify
git log --oneline -5
git checkout <previous-good-sha>
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --build
# If a migration must be undone, run the matching down migration only when safe.
```

Volumes (`mysql_data`, `redis_data`, `storage_data`) are not removed by `up --build`. Avoid `docker compose down -v` on production.

## Verify after deploy

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker ps
# All services: State running, Health healthy (after start_period)

docker inspect --format '{{.Name}} mem={{.HostConfig.Memory}}' \
  visite-notify-app visite-notify-mysql visite-notify-redis
```
