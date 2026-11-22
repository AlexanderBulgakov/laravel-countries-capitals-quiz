# Countries & Capitals Quiz

A country/capital guessing quiz built with Laravel — global leaderboard,
increasing difficulty levels, live weather data for capitals.
A learning/portfolio project focused on backend fundamentals: external API
sync, queues and scheduling, caching, and tests.

![CI](https://github.com/AlexanderBulgakov/laravel-countries-capitals-quiz/actions/workflows/ci.yml/badge.svg)
![Status](https://img.shields.io/badge/status-work%20in%20progress-yellow)

> 🚧 **Work in progress.** 7/9 phases complete. Not feature-complete yet —
> see commit history for current progress.

The quiz is playable end to end, for guests and registered users alike:
pick "guess the capital" or "guess the country" and answer 10 questions
against a per-question timer. It plays like a game show — questions get
harder as you go (countries ranked into thirds by population), any wrong
answer or timeout ends the run, but prize milestones at 3, 6 and 10 correct
answers are kept. Three one-time lifelines: 50/50, +10 seconds, and skip
(the skipped question earns nothing, so the top prize is out of reach).
Registered users get every attempt saved, with a filterable history and a
per-question breakdown of each run. A public leaderboard ranks users by
completed quizzes (cached, tie-aware, invalidated on new completions).

The country catalog at `/countries` supports combined filters: region and
subregion, population/area/density ranges, sovereignty, spoken language,
and international memberships (UN, EU, NATO…). Filters apply without a
page reload (Alpine.js + Blade fragments), and the URL stays clean and
shareable.

Each country has its own page with key facts and the current weather in
its capital(s), fetched from Open-Meteo. A scheduled command syncs all
capitals hourly through a queued job batch (chunks of 50 per request,
retries with backoff); the page shows how fresh the data is ("updated
25 minutes ago") and dims it when a sync has been missed.

**Development approach:** built with Claude Code as a pairing/mentoring
tool. The first two phases of code started as solutions proposed by Claude
in conversation; I read, tested, and integrated them myself — fixing
issues and adjusting along the way. Starting from the third phase, Claude
shifted to a mentoring role — advising rather than writing code.

## Tech stack

- Laravel (PHP 8.4), PostgreSQL, Valkey (Redis-protocol compatible) for cache/queues/sessions
- Blade + Alpine.js for frontend interactivity, Tailwind CSS for styling
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

3. Populate the countries data:

   - Sign up at <https://restcountries.com/> and create an API key — REST
     Countries v5 requires one.
   - Add it to `.env`:

     ```
     RESTCOUNTRIES_API_KEY=your-key-here
     ```

   - Run the sync command:

     ```
     docker compose exec app php artisan countries:sync
     ```

     Idempotent — safe to re-run any time; existing data stays untouched
     if the API is unavailable.

4. Fetch the weather for capitals (no API key needed; requires the countries
   data from the previous step):

   ```
   docker compose exec app php artisan weather:sync
   ```

   This dispatches a batch of queued jobs, processed by the `queue` service.
   After that, the `scheduler` service repeats the sync hourly on its own.

5. Open <http://localhost> (port configurable via `APP_PORT` in `.env`).

## Running tests

Tests run against a separate `laravel_testing` database (on the same `db`
Postgres server) so they never touch real local data. Create it once:

```
docker compose exec db createdb -U laravel laravel_testing
```

Then run tests as usual:

```
docker compose exec app php artisan test
```

## Project structure

- `docker-compose.yml` — `app`, `nginx`, `db` (PostgreSQL), `valkey` (Redis-protocol
  compatible, BSD-licensed — Laravel still uses the `redis` driver/client),
  `node` (Vite dev server for frontend assets, port `5173`), `queue`
  (`queue:work`) and `scheduler` (`schedule:work`). `queue` and `scheduler`
  reuse the `app` service definition via a YAML anchor and start only after
  `app` is healthy (dependencies installed, `APP_KEY` generated) — otherwise
  they would race the first-run bootstrap.
- `docker-compose.override.yml` — local-dev-only additions, auto-merged by
  `docker compose up` with no extra flags needed: `mailpit` (catches
  outgoing email locally — web UI on port `8025`, SMTP on `1025` — instead
  of a real mail provider).
- `docker/php/Dockerfile` — PHP-FPM image with the extensions Laravel needs.
- `docker/php/entrypoint.sh` — first-run bootstrap.
- `docker/nginx/default.conf` — standard Laravel nginx config.
- `src/` — the Laravel app (bind-mounted, lives on the host).
