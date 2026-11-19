# Authenticated Ticket REST API

A Laravel 12 REST API for creating and managing tickets. It uses SQLite for storage and JWT bearer tokens for authentication. Customers can create and view their own tickets; agents can view all tickets and update or delete tickets.

## Requirements

- PHP 8.2 or newer with PDO SQLite enabled
- Composer

## Run locally

```sh
composer install
copy .env.example .env
```

On macOS/Linux, use `cp .env.example .env` instead of `copy`.

Set `DB_CONNECTION=sqlite` in `.env`. Create the database file if needed (`type nul > database/database.sqlite` in Windows Command Prompt, or `touch database/database.sqlite` on macOS/Linux), then generate the app and JWT secrets and run migrations:

```sh
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`. Laravel adds the `/api` prefix to routes in `routes/api.php`.

## Authentication

Register a user with an explicit `customer` or `agent` role, or log in with an existing user's email and password. Registration hashes the password and returns a JWT. Login returns a JWT in the `token` field. Send it on every protected request as `Authorization: Bearer <token>` and send `Accept: application/json`. Registration accepts either role for this learning project; a production service should restrict agent provisioning to an administrator.

```sh
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Customer One","email":"customer@example.com","password":"password123","role":"customer"}'
```

```sh
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'
```

Registration is `POST /api/register`; login is available at both `POST /api/auth/login` and `POST /api/login`.

## Ticket endpoints

All ticket routes require a valid bearer token. JSON validation errors return `400`; unauthenticated requests return `401`; missing tickets (including another user's ticket) return `404`.

| Method | Endpoint | Result |
| --- | --- | --- |
| GET | `/api/tickets` | Customer: list own tickets; agent: list all tickets (`200`) |
| POST | `/api/tickets` | Customer: create a ticket (`201`); agent forbidden (`403`) |
| GET | `/api/tickets/{id}` | Customer: read own ticket; agent: read any ticket (`200`; inaccessible/missing is `404`) |
| PUT | `/api/tickets/{id}` | Agent: update any ticket (`200`); customer forbidden (`403`) |
| DELETE | `/api/tickets/{id}` | Agent: delete any ticket (`200`); customer forbidden (`403`) |

JWT authentication (`auth:api`) runs before the `role` middleware. The role middleware rejects callers without the required role (`403`). Ticket ownership is enforced in the query itself, so a customer requesting another customer's ticket receives `404` without learning whether it exists. `user_id` always comes from the authenticated customer token.

Ticket fields:

- `title`: required on create, string, up to 255 characters
- `description`: required on create, string, up to 10,000 characters
- `status`: optional on create (defaults to `open`); allowed values are `open` and `closed`
- Updates accept one or more of `title`, `description`, and `status`
- `user_id` is assigned from the authenticated user and cannot be supplied by clients

Create a ticket:

```sh
curl -X POST http://127.0.0.1:8000/api/tickets \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"title":"Login issue","description":"Cannot sign in","status":"open"}'
```

Customers can create tickets using the example above. Agents can update a ticket with any valid subset of the ticket fields. Ticket records are defined by the `Ticket` model and migrations in `database/migrations`; API routes are in `routes/api.php`, role checks are in `app/Http/Middleware/RequireRole.php`, and validation/access logic is in `TicketController`.

Example agent inbox request (replace the token with a JWT for an agent account):

```sh
curl http://127.0.0.1:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Authorization: Bearer AGENT_TOKEN"
```

## Project structure

- `app/Models/Ticket.php` — ticket model and mass assignable fields
- `database/migrations/` — users and tickets schema
- `app/Http/Middleware/RequireRole.php` — role authorization middleware
- `routes/api.php` — authentication and ticket API routes
- `app/Http/Controllers/AuthController.php` — registration and JWT login
- `app/Http/Controllers/TicketController.php` — validated, user-scoped ticket CRUD
