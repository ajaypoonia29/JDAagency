# AgencyOS production deployment

This directory contains target-neutral deployment checks plus Linux service examples. Review every placeholder before use.

## Required production controls

1. Use a dedicated production environment file based on `.env.production.example`.
2. Keep `APP_DEBUG=false`, use HTTPS, and disable public registration.
3. Prefer MySQL or PostgreSQL for multi-user production workloads.
4. Back up the database and `storage/app/private` before every deployment.
5. Run migrations only after a verified backup and a staging or cloned-data trial.
6. Build frontend assets and cache Laravel configuration/routes/views.
7. Run `php artisan app:production-check` before opening traffic.
8. Run the scheduler every minute and a persistent queue worker when database/Redis queues are enabled.
9. Restrict the web server document root to the `public` directory.
10. Monitor logs, failed jobs, disk space, SMTP failures, and backup success.

## Recommended deployment sequence

```bash
php artisan down --retry=60
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan app:production-check
php artisan up
```

Restart queue workers after every release:

```bash
php artisan queue:restart
```

## Backup scope

A recoverable backup must include:

- Production database dump or consistent SQLite snapshot
- `storage/app/private`
- Public uploads under `storage/app/public`, when used
- A securely stored copy of the production environment configuration

Store backups outside the application server and test restoration regularly.
