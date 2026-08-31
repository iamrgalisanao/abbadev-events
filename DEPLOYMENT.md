# Deploying ABBADev Events to api.abbadev.com

Laravel 13 + Filament 5 app. Deploys as a standard Laravel site behind Nginx or
Apache with PHP-FPM and MySQL. Receipts are stored on the private local disk.

## 1. Requirements

- Ubuntu VPS, PHP 8.4 with extensions: `intl`, `pdo_mysql`, `mbstring`, `bcmath`,
  `gd` (or `imagick`), `curl`, `zip`, `fileinfo`
- MySQL 8, Composer 2, Git
- DNS: `api.abbadev.com` → VPS IP

## 2. Clone and install

```bash
sudo mkdir -p /var/www && sudo chown -R $USER:$USER /var/www
cd /var/www
git clone https://github.com/iamrgalisanao/abbadev-events.git abbadev-events
cd abbadev-events
composer install --no-dev --optimize-autoloader
```

## 3. Database

```sql
CREATE DATABASE abbadev_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'abbadev_events'@'127.0.0.1' IDENTIFIED BY 'a-strong-password';
GRANT ALL PRIVILEGES ON abbadev_events.* TO 'abbadev_events'@'127.0.0.1';
FLUSH PRIVILEGES;
```

## 4. Environment

```bash
cp .env.production.example .env
php artisan key:generate
nano .env   # set DB_PASSWORD, N8N_*_TOKEN, GCASH_*, CORS_ALLOWED_ORIGINS
```

Key values:
- `DB_PASSWORD` — the MySQL password above.
- `N8N_PAYMENT_CONFIRMED_URL` / `N8N_PAYMENT_CONFIRMED_TOKEN` — the confirmed-payment webhook + Header-Auth token.
- `GCASH_NUMBER` / `GCASH_ACCOUNT_NAME` — keep in sync with the `/seminar` page.
- `CORS_ALLOWED_ORIGINS` — `https://abbadev.com,https://www.abbadev.com`.

## 5. Migrate + first admin user

```bash
php artisan migrate --force        # no --seed in production
php artisan db:seed --class=EventSeeder --force   # seeds the seminar event only
```

Create a real admin (don't use the seeded dev credentials in production):

```bash
php artisan tinker --execute="\App\Models\User::create(['name'=>'Rommel','email'=>'you@abbadev.com','password'=>bcrypt('CHANGE-ME')]);"
```

Panel access is restricted to `@abbadev.com` emails (`User::canAccessPanel`).

## 6. Storage + permissions

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
```

Receipts live in `storage/app/private/receipts` — private, streamed to admins via
`/admin/receipts/{payment}`. Back this directory up.

## 7. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan filament:optimize
```

## 8. Web server + TLS

Copy the vhost from `deploy/` (nginx or apache), enable it, then TLS via certbot.
See the header comments in `deploy/api.abbadev.com.nginx.conf` /
`deploy/api.abbadev.com.apache.conf`. Adjust the PHP-FPM socket to your version.

## 9. Verify

```bash
# Step 1 — create a registration
curl -sX POST https://api.abbadev.com/api/registrations \
  -H 'Content-Type: application/json' \
  -d '{"name":"Test User","email":"test@example.com","event":"idea-to-intelligent-system"}'
```

You should get a `registration_number` and the GCash pay-to details. Then open
`https://api.abbadev.com/admin`, log in, and confirm the payment queue loads.

## 10. Updates

```bash
cd /var/www/abbadev-events && git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan filament:optimize
```

## Optional: queue worker

Phase 1 calls n8n synchronously, so no worker is required. If you later move
n8n/email dispatch onto the queue, install `deploy/abbadev-events-queue.service`.
