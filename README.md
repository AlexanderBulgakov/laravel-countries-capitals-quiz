# laravel-docker

Minimal Docker setup for local Laravel development: `app` (PHP-FPM), `nginx`,
`db` (PostgreSQL), `valkey`.

## Usage

1. Copy the environment example:

   ```
   cp env.example .env
   ```

2. Build and start:

   ```
   docker compose up -d --build
   ```

   If `src/` is empty, the `app` container bootstraps a fresh Laravel project
   into it on first run (see `docker/php/entrypoint.sh`) and generates
   `APP_KEY`. If `src/` already has an app, it's reused as-is; `composer install`
   only runs when `vendor/` is missing.

3. Open <http://localhost> (port configurable via `APP_PORT` in `.env`).

## Layout

- `docker-compose.yml` — `app`, `nginx`, `db` (PostgreSQL), `valkey` (Redis-protocol
  compatible, BSD-licensed — Laravel still uses the `redis` driver/client).
- `docker/php/Dockerfile` — PHP-FPM image with the extensions Laravel needs.
- `docker/php/entrypoint.sh` — first-run bootstrap.
- `docker/nginx/default.conf` — standard Laravel nginx config.
- `src/` — the Laravel app (bind-mounted, lives on the host).
