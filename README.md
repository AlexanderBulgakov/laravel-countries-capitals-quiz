# Countries & Capitals Quiz

A country/capital guessing quiz built with Laravel — global leaderboard,
increasing difficulty levels, live weather and currency data for capitals.
Portfolio project focused on backend fundamentals: external API sync, queues
and scheduling, caching, and tests.

![CI](https://github.com/AlexanderBulgakov/laravel-countries-capitals-quiz/actions/workflows/ci.yml/badge.svg)
![Status](https://img.shields.io/badge/status-work%20in%20progress-yellow)

> 🚧 **Work in progress.** Being built in phases (data model → quiz → auth →
> leaderboard → external data → API layer). Not feature-complete yet — see
> commit history for current progress.

## Tech stack

- Laravel (PHP 8.4), PostgreSQL, Valkey (Redis-protocol compatible) for cache/queues/sessions
- Docker Compose for local development
- Pest for tests, Pint + Larastan for code style and static analysis
- GitHub Actions CI

## Getting started

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

## Project structure

- `docker-compose.yml` — `app`, `nginx`, `db` (PostgreSQL), `valkey` (Redis-protocol
  compatible, BSD-licensed — Laravel still uses the `redis` driver/client).
- `docker/php/Dockerfile` — PHP-FPM image with the extensions Laravel needs.
- `docker/php/entrypoint.sh` — first-run bootstrap.
- `docker/nginx/default.conf` — standard Laravel nginx config.
- `src/` — the Laravel app (bind-mounted, lives on the host).
