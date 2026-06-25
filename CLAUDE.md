# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Full-stack admin dashboard (Vue 3 frontend + Laravel 13 backend) for data management with CSV/XLSX import, role-based access control, and real-time dashboard visualizations. Built on the PrimeVue Sakai template.

**Frontend:** Vue 3.4 (Composition API with `<script setup>` syntax), Vite 5.3, PrimeVue 4.3 (Aura theme), Vue Router 4, Axios, Tailwind CSS + PrimeUI, Chart.js, papaparse, xlsx

**Backend:** Laravel 13, Laravel Sanctum (token auth), MySQL/SQLite, Maatwebsite/Laravel-Excel, database-backed queues

## Initial Setup

```bash
# Backend setup (from backend/ directory)
cd backend
composer install                    # Install PHP dependencies
cp .env.example .env               # Create environment file
php artisan key:generate           # Generate app key
touch database/database.sqlite     # Create SQLite database file (if using SQLite)
php artisan migrate:fresh --seed   # Run migrations and seed data

# Frontend setup (from project root)
npm install                        # Install Node dependencies

# Alternative: One-command setup (from backend/ directory)
composer setup  # Runs all setup steps including npm install and build
```

**Database:** SQLite is the default (configured in `.env.example`). The database file is `backend/database/database.sqlite`. To use MySQL, update `DB_CONNECTION` and related variables in `backend/.env`.

## Development Commands

```bash
# Frontend (from project root)
npm run dev          # Vite dev server at http://localhost:5173
npm run build        # Production build
npm run lint         # ESLint with auto-fix

# Backend (from backend/ directory)
cd backend
php artisan serve                 # Laravel server at http://127.0.0.1:8000
php artisan migrate:fresh --seed  # Reset DB + seed data
php artisan test                  # Run PHPUnit tests (Feature and Unit tests in tests/)
php artisan test --filter TestName  # Run specific test
composer test                     # Clear config cache and run tests

# Alternative: Run all services concurrently (from backend/ directory)
composer dev  # Starts Laravel server, queue worker, logs (Pail), and Vite dev server
```

The Vite dev server proxies `/api` requests to `http://127.0.0.1:8000` (configured in `vite.config.mjs`), so both servers must be running during development.

**Default Credentials (after seeding):**
- Admin: NPP `1001` / Password `password123`
- User: NPP `1002` / Password `password123`

## Authentication Architecture

Token-based auth using Laravel Sanctum with localStorage on the frontend:

1. **Login flow:** POST `/api/login` with NPP + password → backend returns token + user data → stored in `localStorage` as `user_token` and `user_data`
2. **API calls:** All services attach `Authorization: Bearer ${token}` header
3. **Router guards** (`src/router/index.js`): `meta: { requiresAuth: true }` checks `useAuth().isAuthenticated`; `meta: { requiresAdmin: true }` checks `useAuth().isAdmin`; unauthorized access redirects to `/auth/login` or `/forbidden`
4. **Logout:** Calls POST `/api/logout`, clears localStorage, redirects to `/auth/login`

Key composable: `src/composables/useAuth.js` — module-level refs (`user`, `token`) shared across all components. Provides `isAuthenticated`, `isAdmin`, `isUser`, `login()`, `logout()`, `initAuth()`, `canAccess()`.

## Role-Based Access Control

Two roles: `admin` and `user` (ENUM in `users.role` column, default: `user`).

| Feature | User | Admin |
|---------|------|-------|
| Dashboard & Charts | ✓ | ✓ |
| Data CRUD (view/create/edit/delete) | ✓ | ✓ |
| Data Import (CSV/XLSX) | ✓ | ✓ |
| User Management | ✗ | ✓ |

**Frontend enforcement:** `useAuth.js` composable + router `meta` + conditional rendering in `AppMenu.vue`
**Backend enforcement:** `CheckRole` middleware (`app/Http/Middleware/CheckRole.php`) + `role:admin` middleware on user management routes in `routes/api.php`

## API Routes

All routes prefixed with `/api`. Base URL: `http://127.0.0.1:8000/api`

**Public:**
- `POST /login`, `POST /register`

**Protected (auth:sanctum):**
- `POST /logout`, `GET /profile`, `PUT /profile`
- `GET /dashboard/stats`, `GET /dashboard/line-chart`, `GET /dashboard/bar-chart`, `GET /dashboard/pie-chart`
- `GET /dashboard/preferences`, `PUT /dashboard/preferences`, `DELETE /dashboard/preferences` (user dashboard layout preferences)
- `GET /data`, `GET /data/{id}`, `POST /data`, `PUT /data/{id}`, `DELETE /data/{id}`
- `POST /data/batch-delete`, `POST /data/batch-update`, `GET /data/export` (export data as CSV/XLSX)
- `POST /import`, `GET /import/history`
- `GET /filters`, `GET /filters/{id}`, `POST /filters`, `PUT /filters/{id}`, `DELETE /filters/{id}` (saved filter management)

**Admin only (auth:sanctum + role:admin):**
- `GET /users`, `POST /users`, `DELETE /users/{id}`, `POST /users/{id}/reset-password`
- `GET /audit-trail`, `GET /audit-trail/{id}` (audit trail viewing)
- `GET /activity-logs`, `GET /activity-logs/{id}` (activity log viewing)

## Frontend Service Layer

Services in `src/service/` follow a consistent pattern — exported objects with async methods that accept a token and params:

```javascript
export const ServiceName = {
    async methodName(token, params) {
        const response = await axios.get('http://127.0.0.1:8000/api/endpoint', {
            headers: { 'Authorization': `Bearer ${token}` },
            params
        });
        return response.data;
    }
};
```

Services: `AuthService`, `DataService`, `ImportService`, `UserService`, `DeviceService` (dashboard stats)

## Import System (Dual Format)

Endpoint: `POST /api/import` — accepts CSV/XLSX/XLS files up to 10MB.

The import controller (`ImportController.php`) auto-detects two formats:

**Standard format** — header row with required columns: `category`, `value` (numeric), `date` (Y-m-d), `title`. Optional: `description`, `status` (active/inactive), `metadata` (JSON string).

**BNI device format** — auto-detected when headers contain ≥3 of: `computer_name`, `operating_system`, `manufacturer`, `product_name`. Maps BNI device fields to the Data model (e.g., `computer_name` → `title`, `group_id` → `category`, `ram_size` → `value`). Sample BNI files are in the `bni/` folder.

CSV files are parsed with native PHP `fgetcsv()` for performance; XLSX/XLS use Maatwebsite/Excel. Valid rows are inserted in a DB transaction. Import history is tracked in the `import_histories` table.

## Database Schema

Four tables (migrations in `backend/database/migrations/`):

- **users** — `id`, `name`, `npp` (unique login identifier), `password`, `role` (enum: admin/user), timestamps
- **data** — `id`, `category`, `value`, `date`, `title`, `description`, `status` (enum: active/inactive), `metadata` (JSON), `created_by`, `updated_by`, timestamps. Has indexes on category, date, status, created_at.
- **data_histories** — `id`, `data_id`, `action` (enum: created/updated/deleted), `old_values` (JSON), `new_values` (JSON), `changed_by`, timestamps. Auto-populated via Eloquent model events in `Data.php`.
- **import_histories** — `id`, `filename`, `status` (enum: success/failed/partial), `success_count`, `error_count`, `total_rows`, `errors` (JSON), `warnings` (JSON), `user_id`, timestamps

## Layout System

`src/layout/composables/layout.js` exposes `useLayout()` composable:
- `layoutConfig` — reactive theme settings (primary color, dark mode, menu mode: static/overlay)
- `layoutState` — reactive UI state (menu visibility, sidebar)
- `toggleDarkMode()` — adds/removes `.app-dark` class on `document.documentElement`

All protected routes render inside `AppLayout.vue` (topbar + sidebar + content area). Menu items are defined in `AppMenu.vue` with role-based conditional rendering.

## Key Patterns

- **`@` path alias** → `src/` directory (configured in `vite.config.mjs`)
- **PrimeVue auto-import** — components resolved automatically via `unplugin-vue-components` with `PrimeVueResolver`; no manual imports needed
- **Vue 3 `<script setup>` syntax** — all Vue components use the `<script setup>` composition API pattern, not standard `setup()` function
- **Composables** — shared reactive state extracted into composable functions (`useAuth`, `useLayout`)
- **Soft deletes** — data records set to `status: inactive` rather than hard-deleted
- **Data history tracking** — automatic via Eloquent model events (`created`, `updated`, `deleted`) in `Data.php`; stores old/new values and the acting user
- **Global plugins** — Toast and ConfirmationService configured in `main.js`
- **NPP-based login** — users authenticate with NPP (employee ID number), not email
- **Queue-based processing** — background jobs use database-backed queues (`QUEUE_CONNECTION=database` in `.env`)

## PRD Reference

`prd.md` contains the product requirements document (in Indonesian). Note: the PRD references "Viewer" role and "Admin-only CRUD" which differ from the current implementation where the `user` role can also perform CRUD and import operations.
