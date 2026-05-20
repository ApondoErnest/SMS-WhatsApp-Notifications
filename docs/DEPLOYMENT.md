# Production Deployment — Visite Technique Platform

Guide for deploying the Laravel application to a production server.

---

## Server Requirements

| Component | Version |
|-----------|---------|
| Ubuntu | 22.04 LTS or 24.04 LTS |
| PHP | 8.3-FPM |
| Nginx | Latest stable |
| MySQL | 8.0 |
| Redis | 7.x |
| Node.js | 20+ (build only) |
| Supervisor | Latest |
| Composer | 2.x |
| SSL | Let's Encrypt (Certbot) |

**Minimum specs:** 2 vCPU, 4 GB RAM, 40 GB SSD (scale up for high notification volume).

---

## Pre-Deployment Checklist

- [ ] `.env` configured for `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_KEY` generated
- [ ] Database created with dedicated user (not root)
- [ ] Redis password set (if exposed)
- [ ] Twilio production credentials and approved WhatsApp templates
- [ ] Webhook URLs registered in Twilio Console (HTTPS)
- [ ] DNS pointing to server
- [ ] Firewall: ports 22, 80, 443 only
- [ ] Backup strategy configured

---

## Server Setup (Ubuntu)

### Install Packages

```bash
sudo apt update
sudo apt install -y nginx mysql-server redis-server supervisor \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-xml \
  php8.3-mbstring php8.3-zip php8.3-bcmath php8.3-gd php8.3-redis
```

### Create Database

```sql
CREATE DATABASE visite_technique CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'visite'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON visite_technique.* TO 'visite'@'localhost';
FLUSH PRIVILEGES;
```

### Deploy Application

```bash
sudo mkdir -p /var/www/visite-tech-platform
sudo chown -R $USER:www-data /var/www/visite-tech-platform

cd /var/www/visite-tech-platform
git clone <repository-url> .
composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edit .env with production values
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm ci && npm run build
```

### Permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Nginx Configuration

`/etc/nginx/sites-available/visite-tech-platform`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/visite-tech-platform/public;

    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 12M;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/visite-tech-platform /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### SSL with Certbot

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## Queue Workers — Laravel Horizon

Recommended over raw `queue:work` for monitoring and balancing.

### Install Horizon

```bash
php artisan horizon:install
```

### Supervisor Configuration

`/etc/supervisor/conf.d/horizon.conf`:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/visite-tech-platform/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/visite-tech-platform/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

### Alternative: queue:work (without Horizon)

`/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/visite-tech-platform/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/visite-tech-platform/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Scheduler (Cron)

```bash
sudo crontab -u www-data -e
```

```
* * * * * cd /var/www/visite-tech-platform && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks:

| Command | Frequency |
|---------|-----------|
| `notifications:dispatch-due` | Every 15 minutes |
| `notifications:retry-failed` | Hourly |
| `backup:run` | Daily 02:00 |
| `imports:cleanup` | Daily 03:00 |
| `logs:cleanup` | Weekly Sunday 04:00 |

---

## Redis Configuration

`/etc/redis/redis.conf`:

```
bind 127.0.0.1
requirepass your_redis_password
```

Update `.env`:

```env
REDIS_PASSWORD=your_redis_password
```

---

## PHP-FPM Tuning

`/etc/php/8.3/fpm/pool.d/www.conf` (adjust for server RAM):

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
```

```bash
sudo systemctl restart php8.3-fpm
```

---

## Twilio Webhooks (Production)

Register in Twilio Console → Phone Number → Messaging:

| Type | URL |
|------|-----|
| SMS Status Callback | `https://yourdomain.com/webhooks/twilio/sms` |
| WhatsApp Status Callback | `https://yourdomain.com/webhooks/twilio/whatsapp` |

Must use HTTPS. Verify `X-Twilio-Signature` validation is enabled.

---

## Backups

### Spatie Laravel Backup

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Configure `config/backup.php`:

- Database dump daily
- `storage/app/tenants` directory included
- Destination: S3 or local encrypted volume

### Manual MySQL Backup

```bash
mysqldump -u visite -p visite_technique | gzip > backup_$(date +%Y%m%d).sql.gz
```

Retention: 30 days daily, 12 months monthly.

---

## Monitoring

| Tool | Environment | Access |
|------|-------------|--------|
| Horizon | Production | `/horizon` (gate: super-admin, center-admin) |
| Telescope | Staging only | `/telescope` |
| Health check | Production | `GET /health` |
| Sentry | Production | Optional, `SENTRY_LARAVEL_DSN` |

### Log Rotation

`/etc/logrotate.d/visite-tech-platform`:

```
/var/www/visite-tech-platform/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0640 www-data www-data
}
```

---

## Staging Environment

Mirror production with:

- Separate database and Redis DB index
- Twilio **test** credentials
- `APP_ENV=staging`, `APP_DEBUG=true` (Telescope enabled)
- Subdomain: `staging.yourdomain.com`

---

## Zero-Downtime Deployment (Optional)

```bash
php artisan down --refresh=15
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
php artisan horizon:terminate
php artisan up
```

Use Deployer or GitHub Actions for automated pipelines (see [TESTING.md](TESTING.md)).

---

## Security Hardening

- [ ] `APP_DEBUG=false`
- [ ] Disable directory listing in Nginx
- [ ] Fail2ban on SSH
- [ ] MySQL bind to localhost only
- [ ] Redis bind to localhost + password
- [ ] Encrypt Twilio tokens in `center_settings`
- [ ] 2FA enforced for admin roles
- [ ] Rate limit login and CSV upload routes

---

## Related Documentation

- [NOTIFICATIONS.md](NOTIFICATIONS.md) — Twilio webhook setup
- [TESTING.md](TESTING.md) — CI/CD pipeline
- [PLAN.md](PLAN.md) — Phase 6 acceptance criteria
