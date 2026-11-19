# Stock Manifest Server

Stock manifest API server for an online shop — items, categories, stock and attachments.

**Stack:** Laravel 12, Sanctum (token-based), MySQL

Companion frontend: `stock-manifest-client` (React PWA).

## Features

- Token-based auth (Sanctum personal access tokens), with token abilities derived from user permissions
- Token lifetime auto-extended on each request (`extend.token.lifetime` middleware)
- RBAC: roles (`SUPER_ADMIN`, `ADMIN`, `USER`) with role hierarchy, permissions with dependencies
- Login rate limiting (per-IP)
- Admin-only user management; no public register; role-hierarchy access checks (`check.role.access`)
- Items: search (name/price), category filter, sorting (price, category, weight, selling price, cargo price, createdAt, stock status), pagination; stock in/out with THB/MMK pricing
- Categories: CRUD with soft delete / restore
- Item images and documents: per-item attachments (sync, show, download; size/count limits configurable)
- Settings: exchange rate (THB/MMK)
- Soft delete + restore (items, categories, users); user force delete
- Versioned seeders (permissions, users, settings, categories, items)
- API resources for response shaping (camelCase JSON API)
- Postman collection sync (`php artisan postman:sync`)
- Health check at `/api/health`

## Requirements

- PHP ^8.2
- MySQL

## Setup

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Configure `.env` (database credentials, CORS/frontend URL, `TOKEN_LIFETIME` / `TOKEN_REMEMBER_LIFETIME`) before migrating. `db:seed` runs the versioned seeders (permissions, admin user, settings, categories, items).

## API

Base path: `/api/v1`

| Area | Endpoints |
| --- | --- |
| Auth | login, me, check, logout, change-password |
| Users | list, trashed, show, create, update, update-password, suspend, restore, delete, restore-from-trash, force-delete |
| Categories | list, show, create, update, delete, restore |
| Items | list, show, create, update, delete, restore, sync-image, attachment show/download |
| Settings | get, update |
