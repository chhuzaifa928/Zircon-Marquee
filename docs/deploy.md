# Zircon Marquee ERP — Deployment (Hostinger)

Production deployment and backup runbook. Pair this with
[`security-playbook.md`](security-playbook.md).

## Stack
Laravel 13 (PHP 8.3) + Filament 5 + MySQL, on Hostinger with SSL. App served
from `public/`; panel at `/admin`.

## First deploy

1. **Code** — clone the repo (branch `main`) or upload the build. Point the
   domain's document root at `public/`.
2. **Dependencies** — `composer install --no-dev --optimize-autoloader`.
3. **Environment** — copy `.env.example` → `.env` and set:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<domain>`
   - `APP_KEY` → `php artisan key:generate`
   - MySQL: a **least-privilege** DB user scoped to the `zircon` schema (not root)
   - `MAIL_*` for backup/failure notifications
4. **Database** — `php artisan migrate --force` then
   `php artisan db:seed --force` (roles, halls, settings, chart of accounts).
   Create the first user and assign `super_admin`.
5. **Storage** — `php artisan storage:link` (serves uploaded logos).
6. **Optimise** — `php artisan config:cache route:cache view:cache` and
   `php artisan filament:optimize`.
7. **Permissions** — web user writable on `storage/` and `bootstrap/cache/` only.
8. **TLS** — enable the Hostinger certificate; the app forces HTTPS in
   production and sends HSTS.

## Updates

```
git pull            # or upload
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache route:cache view:cache
php artisan filament:optimize
```

## Backups (spatie/laravel-backup)

- Config: `config/backup.php`. Backs up the database (and app files) to the
  `backups/` disk, cleans old archives, and monitors freshness/size.
- Scheduled in `routes/console.php`: `backup:clean` 01:00, `backup:run` 01:30,
  `backup:monitor` 02:00 daily.
- **Enable the scheduler** with a single server cron entry:

  ```
  * * * * * cd /home/<user>/domains/<domain> && php artisan schedule:run >> /dev/null 2>&1
  ```

- **Off-site copy**: add a second disk (S3/Google Drive/FTP) to
  `backup.destination.disks` so a copy lives off the Hostinger box.
- Manual run / restore test: `php artisan backup:run`; download an archive and
  restore the SQL dump into a scratch database to verify the procedure.

## Verify after deploy
- `https://<domain>/admin/login` loads over HTTPS with the Zircon brand.
- Log in as super_admin; confirm each role sees only its modules.
- Create a booking → payment → close event; check a report PDF.
- `php artisan backup:run` produces an archive.
