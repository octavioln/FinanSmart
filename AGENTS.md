# AGENTS.md

FinanSmart is a Spanish-language personal-finance app built on Laravel 12 + Livewire 3 (TALL stack). The finance pages are full-page Livewire components; there is no SPA and no API beyond `/api/user`.

## Runtime and dependencies

- PHP 8.3 is required. In this environment `php` on PATH is already the Laragon 8.3 build; if it is not, use:
  ```powershell
  $php = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
  ```
  Confirm with `& $php -v`. Tests also need `pdo_sqlite`; if `& $php -m` does not list it, the suite fails with "could not find driver".
- `composer.lock` pins Laravel 12 / Livewire 3. The current `vendor/` tree matches (Laravel 12.70.2, Livewire 3.8.11, Pint, PHPUnit 11.5). Verify with `& $php artisan --version`; if it is not 12.x, or Livewire tests fail because `$this->dispatch` is missing, reinstall with:
  ```powershell
  & $php "C:\laragon\bin\composer\composer.phar" install
  ```
  Use `install`, not `update`.
- Frontend: `npm install`, then `npm run dev` for HMR or `npm run build` for production assets. Vite inputs are `resources/css/app.css`, `resources/js/app.js`, and `resources/js/main.js`; HMR also watches `app/Http/Livewire/**`.

## Commands

- Full suite (SQLite `:memory:` configured in `phpunit.xml`): `& $php artisan test`. Current baseline: 124 passed, 8 skipped, 385 assertions.
- One suite: `& $php artisan test --testsuite=Unit` (or `Feature`)
- One class: `& $php artisan test --filter=TransactionsLivewireTest`
- Format changed files: `& $php vendor/bin/pint --dirty`; check-only with `--test`. There is no CI and no composer lint/test script.
- Reconcile balances: `& $php artisan finance:reconcile`; add `--fix` to set `available_amount` to the calculated balance. It compares each account's `opening_balance` plus its non-deleted transactions.
- Subscription reminders: `& $php artisan finance:subscriptions:due --days=7` prints and logs overdue/near-due subscriptions **without charging anything**. It is scheduled daily at 08:00 in `app/Console/Kernel.php`, so the server needs `php artisan schedule:run` every minute. Overdue entries also appear in an amber panel on the dashboard.
- Database setup: `& $php artisan migrate --seed` (or `& $php artisan db:seed`). `DatabaseSeeder` calls `AccountSeeder`, `CategorySeeder`, and `AdminUserSeeder`. Account and category seeders are idempotent (`firstOrCreate` by name) and preserve existing balances; the admin seeder only creates a user when `ADMIN_EMAIL` and `ADMIN_PASSWORD` are set.
- Public registration is disabled. To create the first user (or a new one) on a fresh database:
  ```powershell
  $env:ADMIN_EMAIL="tu@correo.com"
  $env:ADMIN_PASSWORD="una-contraseña-segura"
  & $php artisan db:seed --class=AdminUserSeeder
  ```
  `AdminUserSeeder` is idempotent by email and does not overwrite an existing user.
- Serve locally with `& $php artisan serve` (or the Laragon virtual host).

## Architecture and business rules

- Livewire components live in `app/Http/Livewire` (namespace `App\Http\Livewire`, set in `config/livewire.php`), with views in `resources/views/livewire`; routes in `routes/web.php` bind component classes directly. Auth is Jetstream/Fortify/Sanctum (`app/Actions/Fortify`, `app/Actions/Jetstream`); there are no finance controllers, FormRequests, or policies.
- Keep the legacy Laravel 10 skeleton: `bootstrap/app.php` binds `app/Http/Kernel.php` and `app/Console/Kernel.php`, even though the locked framework is Laravel 12. Do not apply Laravel 11/12 skeleton patterns (such as moving middleware into `bootstrap/app.php`) without a deliberate migration.
- Finance data is global, not scoped per user: `accounts`, `categories`, `transactions`, and `subscriptions` have no `user_id`. Authentication only gates the pages; do not assume `auth()->id()` filtering exists.
- Account balances are denormalized in `accounts.available_amount`. Mutate them only through `Account::updateAvailableAmount('Cargo'|'Abono', $amount)` or `Account::applyTransactionEffect($type, $amount, $reverse)`; the strings are exact and any other value silently no-ops. The effect is applied atomically with a SQL update. Multi-entry flows (cash withdrawal, savings, transfer) create two transactions sharing `transactions.transaction_group_id` and `operation_type`; edit/delete always operates on the whole group inside `DB::transaction()`. Single-entry rows have `transaction_group_id = null`.
- Accounts carry an explicit `opening_balance`; `available_amount` starts equal to it and then reflects movements. A new account should always be created with both fields consistent. Changing `opening_balance` from the UI adjusts `available_amount` by the difference and is rejected if the result would be negative.
- Special accounts are resolved by flags, not IDs: `Account::cash()` for ATM withdrawals and `Account::savings()` for savings transfers. The accounts UI keeps at most one active `is_cash` and one active `is_savings` account.
- Internal categories are resolved by `categories.system_key` (`Category::SYSTEM_SAVINGS`, `SYSTEM_TRANSFER`, `SYSTEM_DEPOSIT`), not by IDs 8/18/19. Purchases and subscriptions only accept active categories with `system_key = null`; edit flows preserve an existing internal category.
- `accounts.is_active` and `categories.is_active` represent archiving. Archived records stay available for historical transactions but disappear from operation dropdowns. Prefer archiving over soft deletion; the UI blocks archiving the configured cash/savings accounts and records that still have subscriptions.
- `Transaction::getAll()` / `getMostRecent()` and `Subscription::getAll()` use the query builder, manually filter `t.deleted_at`/`s.deleted_at` plus soft-deleted accounts/categories, and whitelist the sort column/direction. Keep those guards when adding new queries.
- `Transactions::save()` always forces `type = 'Cargo'`; the other operation types have dedicated methods: `saveWithdraw` (`Account::cash()`), `saveDeposit` (`Category::SYSTEM_DEPOSIT`), `saveSavings` (`Account::savings()`, savings/deposit categories), `saveTransfer` (`Category::SYSTEM_TRANSFER`), and `saveSubscription` (copies the subscription's account, category, and amount). New user-facing copy should be Spanish, matching the rest of the UI.
- Subscription payments are identified by `transactions.subscription_id` plus `transactions.billing_period` (`Y-m`), enforced by a unique index on `(subscription_id, billing_period)`. The period is chosen explicitly in the payment modal (defaulting to the next unpaid period) and is independent of `effective_date`; the same period therefore cannot be paid twice. Soft-deleting a payment nulls `billing_period` so the period can be paid again. Both the guard in `saveSubscription()` and the unique index protect this rule.
- Subscription control fields (`last_paid_at`, `next_payment_date`) are derived from the payment ledger by `Subscription::refreshSchedule()`, triggered from `Transaction` model events (`saved`, `deleted`, `restored`, `forceDeleted`). Do not update them manually in components. `is_active` acts as pause/resume: paused subscriptions keep their history but are hidden from payment dropdowns and the due reminder. Statuses are `current` / `due_soon` (≤7 days) / `overdue` / `paused`, with Spanish labels from `Subscription::statusLabel()`. A new model instance defaults `is_active` to `true` via `$attributes` so a freshly created subscription is not treated as paused before reloading.

## Test notes

- Tests use `RefreshDatabase` and SQLite `:memory:`. `TransactionsLivewireTest`, `AccountsLivewireTest`, and `CategoriesLivewireTest` seed `AccountSeeder` + `CategorySeeder` in `setUp`; the accounting tests no longer depend on id 1/3 for cash/savings because those are resolved by flags. `SubscriptionsLivewireTest` and the unit tests use model factories instead. `SubscriptionPhaseTwoTest` seeds the real seeders and freezes time with `Carbon::setTestNow('2026-11-02 10:00:00')`, so due-date expectations are stable; reset it in `tearDown()`.
- Current suite: 124 passing, 8 skipped, 385 assertions. The 8 skips are Jetstream/Fortify features that are disabled: API tokens (3), email verification (3), and public registration (2). Account deletion and 2FA are enabled; teams are not enabled, so do not assume team scoping. Public registration is intentionally disabled because finance data is global.
- With a stale Livewire 2 vendor tree, `$this->dispatch` is unavailable and Livewire tests fail; reinstall dependencies (`composer install`, not `update`) to get Livewire 3.
