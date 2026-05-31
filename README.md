# Backend Template — Laravel REST API

A production-minded starter for building JSON APIs with Laravel. It ships with
token authentication, a relational CRUD domain, per-user authorization, a full
test suite, static analysis and CI — so it can be used as a reference of how a
clean Laravel backend is structured.

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
- **24 feature tests** covering happy paths, validation and authorization.

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

## Quality checks

```bash
composer lint        # apply Laravel Pint code style
composer lint:test   # verify code style without changing files
composer analyse     # run Larastan (PHPStan) static analysis
composer test        # run the Pest test suite
composer check       # run all of the above (used in CI)
```

Tests run against a dedicated `template_testing` PostgreSQL database, created
automatically by the Docker init script (`docker/postgres/initdb`).

## Project structure

```
app/
  Enums/TaskStatus.php          # type-safe task status
  Http/
    Controllers/                # thin controllers (Auth, Project, Task)
    Requests/                   # Form Request validation
    Resources/                  # JSON output transformers
  Models/                       # Eloquent models + relationships
  Policies/                     # per-user authorization rules
database/
  factories/  migrations/  seeders/
routes/api.php                  # API route definitions
tests/Feature/                  # Pest feature tests
docker-compose.yml              # PostgreSQL service
```

## License

MIT.
