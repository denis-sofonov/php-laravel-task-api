# Reference Backend — Laravel (PHP 8.3+)

[![CI](https://github.com/denis-sofonov/php-laravel-task-api/actions/workflows/ci.yml/badge.svg)](https://github.com/denis-sofonov/php-laravel-task-api/actions/workflows/ci.yml)

A reference implementation of a clean, production-minded JSON API in Laravel —
built as a code sample to show how I structure a backend service end to end:
token authentication, a relational CRUD domain, per-user authorization,
validation, tests, static analysis, Docker and CI.

> It is intentionally a small, concrete domain (projects & tasks) rather than a
> generic boilerplate — the goal is to demonstrate engineering decisions, not to
> be a fill-in-the-blanks starter. See [Design decisions](#design-decisions--trade-offs).

The same domain is implemented across stacks: [FastAPI](https://github.com/denis-sofonov/python-fastapi-task-api) and the full-stack [Nuxt](https://github.com/denis-sofonov/ts-nuxt-task-app) and [Next.js](https://github.com/denis-sofonov/ts-next-task-app) apps.

## Tech stack

| Area | Choice |
|------|--------|
| Language / Framework | PHP 8.3+ · Laravel 13 |
| Authentication | Laravel Sanctum (Bearer tokens) |
| Database | PostgreSQL 17 (Docker) |
| Tests | Pest 4 |
| Static analysis | Larastan (PHPStan) — level 6 |
| Code style | Laravel Pint |

## Features

- **Token auth** — register / login / logout / current user.
- **CRUD domain** — `Projects` with nested `Tasks` (one-to-many).
- **Authorization** — policies enforce that users only access their own data.
- **Validation** — dedicated Form Requests per action (create vs. update).
- **API Resources** — explicit, stable JSON output (no leaking model internals).
- **Type-safe enum** — `TaskStatus` (`todo` / `in_progress` / `done`).
- **Pagination** on list endpoints.
- **46 tests** (feature + unit) covering happy paths, validation, authorization,
  rate limiting, email verification and password reset.

## Design decisions & trade-offs

The point of this repo is the reasoning, not the feature count. Key choices:

- **Sanctum tokens over JWT.** First-party, supports server-side revocation
  (logout actually invalidates a token). Trade-off: a DB lookup per request and
  not fully stateless across services — JWT would fit a multi-service mesh better.
- **API Resources for every response.** The JSON contract is decoupled from the
  database schema, so columns can change without breaking clients. Trade-off:
  a little extra boilerplate per model.
- **One Form Request per action.** Validation lives outside controllers; separate
  `Store`/`Update` requests model "create" vs. "partial update" precisely.
- **Authorization by ownership (Policies), no roles yet.** Simple and sufficient
  here. Trade-off: no RBAC — I'd add `spatie/laravel-permission` once roles appear.
- **PostgreSQL + `ILIKE` search.** Pragmatic and readable. Trade-off: a leading
  wildcard can't use a B-tree index; at scale I'd switch to a trigram/full-text index.
- **Database queue, not Redis/Horizon.** Zero extra infrastructure for a sample.
  Trade-off: Redis + Horizon is the better choice for real throughput and monitoring.
- **Offset pagination.** Fine for moderate datasets; I'd move to cursor pagination
  for very large or realtime-changing lists.
- **Thin controllers, no service layer.** Idiomatic for a domain this size.
  As business logic grows I'd extract Action/Service classes rather than fatten controllers.
- **Versioned API (`/api/v1`) from day one.** Cheap insurance against breaking clients.
- **Tests run on real PostgreSQL, not SQLite.** Same engine as production catches
  engine-specific bugs. Trade-off: the database must be running and tests are a touch slower.

## Requirements

- PHP 8.3+
- Composer 2
- Docker (for PostgreSQL)

## Getting started

```bash
# 1. Install PHP dependencies
composer install

# 2. Create your environment file and app key
cp .env.example .env
php artisan key:generate

# 3. Start PostgreSQL (creates the dev + testing databases)
docker compose up -d

# 4. Run migrations and seed demo data
php artisan migrate --seed

# 5. Serve the API
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api/v1`.

### Run the whole stack in Docker

Instead of `artisan serve`, you can run PHP-FPM + Nginx + PostgreSQL together:

```bash
docker compose up -d --build                       # build & start app, web, postgres
docker compose exec app php artisan migrate --seed # set up the database
# API: http://localhost:8080/api/v1
```

A demo user is seeded for manual testing:

```
email:    test@example.com
password: password
```

## API reference

All routes are prefixed with `/api/v1`. Protected routes require the header
`Authorization: Bearer <token>`. Auth endpoints are rate-limited (5/min);
other endpoints are limited to 60 requests/min per user.

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/register` | – | Create a user, returns user + token, sends verification email |
| POST | `/login` | – | Authenticate, returns user + token |
| GET | `/user` | ✅ | Current authenticated user |
| POST | `/logout` | ✅ | Revoke the current token |
| POST | `/forgot-password` | – | Send a password reset link |
| POST | `/reset-password` | – | Set a new password using the token |
| GET | `/email/verify/{id}/{hash}` | – (signed) | Verify email via the signed link |
| POST | `/email/verification-notification` | ✅ | Resend the verification email |

### Projects

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects` | List the user's projects (paginated) |
| POST | `/projects` | Create a project |
| GET | `/projects/{project}` | Show a project |
| PATCH | `/projects/{project}` | Update a project |
| DELETE | `/projects/{project}` | Delete a project |

### Tasks (nested under a project)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/projects/{project}/tasks` | List tasks of a project |
| POST | `/projects/{project}/tasks` | Create a task |
| GET | `/tasks/{task}` | Show a task |
| PATCH | `/tasks/{task}` | Update a task |
| DELETE | `/tasks/{task}` | Delete a task |

**List query parameters** (projects & tasks):

- `search` — case-insensitive substring match (project `name` / task `title`).
- `status` — tasks only: `todo` / `in_progress` / `done`.
- `sort` — whitelisted fields, prefix with `-` for descending.
  Tasks: `created_at`, `due_date`, `title`, `status`. Projects: `created_at`, `name`.
  Example: `GET /api/v1/projects/1/tasks?status=todo&sort=-due_date`.

### Example

```bash
# Register and capture the token
TOKEN=$(curl -s -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@example.com","password":"Password123!","password_confirmation":"Password123!"}' \
  | php -r 'echo json_decode(file_get_contents("php://stdin"))->token;')

# Create a project
curl -X POST http://127.0.0.1:8000/api/v1/projects \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"My first project"}'
```

### System

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/health` | – | Liveness probe (checks DB), 200 or 503 |
| GET | `/stats` | ✅ | Per-user counts, cached for 60s |

## Operations

**Queues.** Heavy work is pushed to a queue instead of blocking the request
(e.g. `LogProjectActivity` on project creation). Run a worker with:

```bash
php artisan queue:work
```

The queue connection is `database` by default (`QUEUE_CONNECTION` in `.env`).

**Structured logging.** A `structured` JSON log channel
(`storage/logs/structured.log`) is ready for ingestion by ELK / Loki / Datadog.
Use it via `Log::channel('structured')->info(...)`.

**Error monitoring (Sentry).** Not bundled to avoid an unused dependency.
To enable it: `composer require sentry/sentry-laravel`, then publish the config
and set `SENTRY_LARAVEL_DSN` in `.env`.

## Generated API docs

Interactive HTML docs, an OpenAPI 3 spec and a Postman collection are generated
from the code (routes, Form Requests, API Resources) with
[Scribe](https://scribe.knuckles.wtf):

```bash
composer docs   # regenerate -> public/docs/{index.html, openapi.yaml, collection.json}
```

Open `public/docs/index.html`, or import `public/docs/openapi.yaml` into any
OpenAPI tool.

## Quality checks

```bash
composer lint        # apply Laravel Pint code style
composer lint:test   # verify code style without changing files
composer analyse     # run Larastan (PHPStan) static analysis
composer test        # run the Pest test suite
composer test:coverage # run tests with coverage (needs pcov/xdebug), min 70%
composer check       # lint + analyse + test (used in CI)
```

Tests run against a dedicated `template_testing` PostgreSQL database, created
automatically by the Docker init script (`docker/postgres/initdb`).

## Project structure

```
app/
  Enums/TaskStatus.php          # type-safe task status
  Http/
    Controllers/                # thin controllers (Auth, Project, Task, Health, Stats)
    Requests/                   # Form Request validation
    Resources/                  # JSON output transformers
  Jobs/                         # queued background jobs
  Models/                       # Eloquent models + relationships
  Policies/                     # per-user authorization rules
  Providers/                    # rate limiters, password-reset URL
database/
  factories/  migrations/  seeders/
routes/api.php                  # API route definitions
tests/Feature/  tests/Unit/     # Pest tests
docker/                         # Dockerfile, nginx, postgres init
docker-compose.yml              # app (php-fpm) + web (nginx) + postgres
public/docs/                    # generated OpenAPI docs (Scribe)
```

## License

MIT.
