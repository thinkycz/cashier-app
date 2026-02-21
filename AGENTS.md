# AGENTS.md

This file is a practical guide for future coding agents working in this repository.

## Project Snapshot
- App type: Laravel 12 + Inertia.js + Vue 3 POS/cashier app.
- Backend: PHP 8.2, Eloquent models, standard Laravel web routes/controllers.
- Frontend: Vue SFC pages under `resources/js/Pages`, Pinia store for cart state.
- Styling: Tailwind CSS.
- Database default: SQLite (`database/database.sqlite` locally, in-memory SQLite in tests).

## High-Value Paths
- Backend domain logic:
  - `app/Http/Controllers/DashboardController.php`
  - `app/Http/Controllers/ProductController.php`
  - `app/Http/Controllers/BillController.php`
  - `app/Models/Customer.php`
  - `app/Models/Product.php`
  - `app/Models/Transaction.php`
  - `app/Models/TransactionItem.php`
- Routes:
  - `routes/web.php`
  - `routes/auth.php`
- Frontend app/bootstrap:
  - `resources/js/app.js`
  - `resources/js/stores/cart.js`
- Main feature pages:
  - `resources/js/Pages/Dashboard.vue`
  - `resources/js/Pages/Products/*`
  - `resources/js/Pages/Bills/*`
- Schema/seeders:
  - `database/migrations/*customers*`
  - `database/migrations/*products*`
  - `database/migrations/*transactions*`
  - `database/migrations/*transaction_items*`
  - `database/seeders/ProductSeeder.php`

## Feature Boundaries (Important)
- Product management is fully CRUD via `Route::resource('products', ProductController::class)`.
- Bills are read-only routes right now: `Route::resource('bills', BillController::class)->only(['index', 'show'])`.
- Dashboard + cart currently operate mostly in frontend state (Pinia). There is no completed backend flow in this codebase yet for creating/updating transactions from the dashboard/cart UI.
- Keep this boundary in mind before adding frontend behavior that assumes missing backend endpoints.

## Data Model Notes
- `products`:
  - `ean` is nullable and unique.
  - `price` decimal(10,2), `vat_rate` decimal(5,2), `is_active` boolean.
- `transactions`:
  - `transaction_id` unique string, auto-generated in `Transaction::boot()` if empty.
  - `status` enum: `open|completed|cancelled`.
  - Optional `customer_id` with `set null` on delete.
- `transaction_items`:
  - FK to transactions/products with `cascade` on delete.

## Local Dev Commands
- Initial setup:
  - `composer install`
  - `cp .env.example .env`
  - `php artisan key:generate`
  - `touch database/database.sqlite`
  - `php artisan migrate`
  - `php artisan db:seed`
  - `npm install`
- Run app (single command):
  - `composer run dev`
- Alternative split terminals:
  - `php artisan serve`
  - `npm run dev`
- Production frontend build:
  - `npm run build`

## Testing & Quality
- Run tests:
  - `composer test`
  - or `php artisan test`
- Current tests are mostly auth/profile scaffolding; custom POS features have little/no feature coverage.
- If you change product/bill/dashboard behavior, add or update tests under `tests/Feature`.

## Frontend Conventions
- Use Inertia page components in `resources/js/Pages`.
- Use `@` alias (`@/`) for imports from `resources/js` (configured in `jsconfig.json`).
- Use `useForm` from Inertia for form submissions and server validation errors.
- Currency/date formatting is currently done client-side using `cs-CZ` locale and `CZK` currency.

## Backend Conventions
- Keep controllers thin; validation is currently inline in `ProductController` and standard Breeze patterns elsewhere.
- Return Inertia responses from web controllers.
- Preserve route model binding style used by existing controllers (`Product $product`, `Transaction $bill`).

## When Making Changes
1. Check whether the change belongs to frontend state only, backend persistence only, or both.
2. If introducing new transaction/cart persistence, add routes + controller actions + validation + model writes + UI wiring together.
3. Keep DB schema and casts aligned for money fields (decimal values).
4. Verify search/filter/pagination behavior on products and bills pages.
5. Run tests and at least a quick smoke check of dashboard/products/bills flows.

## Guardrails
- Do not edit `vendor/` or `node_modules/`.
- Do not commit secrets from `.env`.
- Favor small, focused patches over broad refactors unless requested.

## Known Gaps / Risks
- No explicit debounce on product/bill search watchers; rapid typing may trigger many requests.
- Dashboard cart state is not persisted server-side.
- Limited automated tests around custom POS features.
