# Visite Notify — VPS deploy (Hostinger / Docker)

Target:

- Domain: `https://notify.noblevehicletestingcompany.com`
- VPS: `89.117.37.202`
- Path: `/var/www/visite-notify`
- Docker Nginx bind: `127.0.0.1:8084`
- Host Nginx + Certbot (same pattern as gs-autobilan / nacho)

Repo: `https://github.com/ApondoErnest/SMS-WhatsApp-Notifications`

Runtime: **PHP 8.4** (FPM image `php:8.4-fpm-bookworm`)

---

## 0) On your Mac — push Docker files

Commit/push the Docker deploy files to `main` (or your deploy branch) before cloning on the VPS.

---

## 1) Clone on the VPS

```bash
cd /var/www
sudo mkdir -p visite-notify
sudo chown ernesto:ernesto visite-notify
cd /var/www/visite-notify

git clone https://github.com/ApondoErnest/SMS-WhatsApp-Notifications.git .
# If private repo, use SSH deploy key or a PAT:
# git clone git@github.com:ApondoErnest/SMS-WhatsApp-Notifications.git .
```

---

## 2) Create `.env.docker`

```bash
cd /var/www/visite-notify
cp .env.docker.example .env.docker
nano .env.docker
```

Set at least:

- Strong `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD`
- Leave `APP_KEY` empty for now (generate in step 4)
- Copy Nexah values from your local Mac `.env`:
  - `NEXAH_SMS_USER`
  - `NEXAH_SMS_PASSWORD`
  - `NEXAH_SMS_SENDER_ID`
- Generate a random `NEXAH_SMS_WEBHOOK_TOKEN` (e.g. `openssl rand -hex 32`)
- `SMS_SENDING_ENABLED=true`
- `NOTIFICATIONS_SENDING_ENABLED=false` until you are ready for scheduled sends
- Meta WhatsApp fields can stay empty for now
- `APP_URL=https://notify.noblevehicletestingcompany.com`
- `DOCKER_HTTP_PORT=8084`

---

## 3) Build & start containers

```bash
cd /var/www/visite-notify

docker compose -f docker-compose.prod.yml --env-file .env.docker config --quiet
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --build
docker compose -f docker-compose.prod.yml --env-file .env.docker ps
```

Confirm `visite-notify-nginx` is listening:

```bash
ss -lnt | grep 8084
curl -I http://127.0.0.1:8084/up
```

---

## 4) App key, migrate, seed, super-admin

```bash
cd /var/www/visite-notify

# Generate APP_KEY into the running container env file workflow:
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan key:generate --show
# Copy the printed key into .env.docker as APP_KEY=base64:...
nano .env.docker

# Recreate app/worker/scheduler so they pick up APP_KEY
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d app worker scheduler

docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan db:seed --class=RoleAndPermissionSeeder --force
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan storage:link

docker compose -f docker-compose.prod.yml --env-file .env.docker exec -it app \
  php artisan visite:create-super-admin noblevehicletestingcompany@gmail.com --name="Noble Vehicle Testing"
```

Create the first center in the UI after login (defaults templates are auto-created).

---

## 5) Host Nginx site

```bash
cd /var/www/visite-notify

sudo cp deploy/vps/nginx-host/notify.noblevehicletestingcompany.com.conf \
  /etc/nginx/sites-available/notify.noblevehicletestingcompany.com

sudo ln -sf /etc/nginx/sites-available/notify.noblevehicletestingcompany.com \
  /etc/nginx/sites-enabled/notify.noblevehicletestingcompany.com

sudo nginx -t && sudo systemctl reload nginx
```

HTTP check:

```bash
curl -I -H "Host: notify.noblevehicletestingcompany.com" http://127.0.0.1/
```

---

## 6) SSL (Certbot)

DNS must already point to this VPS (`dig +short notify.noblevehicletestingcompany.com` → `89.117.37.202`).

```bash
sudo certbot --nginx -d notify.noblevehicletestingcompany.com
```

Then:

```bash
curl -I https://notify.noblevehicletestingcompany.com/up
```

Open: https://notify.noblevehicletestingcompany.com/login

---

## 7) Nexah DLR webhook

In Nexah, set delivery callback to:

```text
https://notify.noblevehicletestingcompany.com/webhooks/nexah/sms?token=YOUR_NEXAH_SMS_WEBHOOK_TOKEN
```

---

## Useful commands

```bash
cd /var/www/visite-notify
docker compose -f docker-compose.prod.yml --env-file .env.docker logs -f --tail=100 app worker scheduler
docker compose -f docker-compose.prod.yml --env-file .env.docker restart worker
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan about
```

### Update after git push

```bash
cd /var/www/visite-notify
git pull
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d --build
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan config:cache
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan route:cache
docker compose -f docker-compose.prod.yml --env-file .env.docker exec app php artisan view:cache
```

---

## Rollback / safety

- Other apps use `8080` / `8082` / `8083` — Visite Notify uses **`8084` only**
- Own MySQL/Redis volumes — no shared DB with the other 4 apps
- To stop only this stack:

```bash
docker compose -f docker-compose.prod.yml --env-file .env.docker down
```
