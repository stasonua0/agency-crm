# Agency CRM MVP

Laravel 12 CRM MVP for a digital agency.

## Stack

- PHP 8.4
- Laravel 12
- PostgreSQL
- Redis
- Inertia.js
- Vue 3
- Tailwind CSS
- Vuexy-inspired application shell

The licensed Vuexy source package is kept outside this repository. Do not commit the purchased template archive, license files, `.env`, database dumps, API keys, or production credentials.

## Local Setup

Install dependencies:

```bash
composer install
npm install
```

Copy environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Start PostgreSQL and Redis:

```bash
docker compose up -d pgsql redis
```

Run migrations and seed local users:

```bash
php artisan migrate:fresh --seed
```

Start the app:

```bash
php artisan serve
npm run dev
```

Open:

```text
http://127.0.0.1:8000
```

## Local Users

All seeded users use the password:

```text
password
```

Accounts:

```text
owner@example.com
finance@example.com
viewer@example.com
```

## Verification

Run backend tests:

```bash
php artisan test
```

Build frontend assets:

```bash
npm run build
```

## Stage 1 Scope

Implemented:

- Laravel 12 project shell
- PostgreSQL and Redis local services
- Inertia.js + Vue 3
- Authentication
- Roles: `owner`, `finance_manager`, `viewer`
- CRM navigation
- Dashboard placeholder
- Module placeholders

Not implemented yet:

- Clients, projects, and services CRUD
- Financial core
- Invoices and acts
- Tochka Bank integration
- DaData integration
- Email delivery
- Reports
- Audit log persistence
