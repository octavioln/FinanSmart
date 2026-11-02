# AGENTS.md

FinanSmart is a Spanish-language personal-finance app built on Laravel + Livewire (TALL stack). The finance pages are full-page Livewire components; there is no SPA and no API beyond `/api/user`.

## Runtime and dependencies

- The default `php` on PATH is 7.4 and cannot run this project. Use the Laragon PHP 8.3 binary, which also has `pdo_sqlite` required by the tests:
  ```powershell
  $php = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
  ```
  Other local builds: PHP 8.2.4 lacks `pdo_sqlite`, so the whole suite fails with "could not find driver".
- `composer.lock` pins Laravel 12 / Livewire 3, but the local `vendor/` tree can be stale (e.g. Laravel 10 / Livewire 2). If `& $php artisan --version` is not 12.x, or tests fail because `$this->dispatch` does not exist, reinstall dependencies with the same PHP binary (not the default `composer`, which uses PHP 7.4):
  ```powershell
  & $php "C:\laragon\bin\composer\composer.phar" install
  ```
  Use `install`, not `update`.
- Frontend: `npm install`, then `npm run dev` for HMR or `npm run build` for production assets. Vite inputs include both `resources/js/app.js` and `resources/js/main.js`.

## Commands

- Full suite (SQLite `:memory:` configured in `phpunit.xml`): `& $php artisan test`
- One suite: `& $php artisan test --testsuite=Unit` (or `Feature`)
- One class: `& $php artisan test --filter=TransactionsLivewireTest`
- Format changed files: `& $php vendor/bin/pint --dirty`; check-only with `--test`. There is no CI and no composer lint/test script.
- Database setup: `& $php artisan migrate`, then seed **explicitly**:
  ```powershell
  & $php artisan db:seed --class=AccountSeeder
  & $php artisan db:seed --class=CategorySeeder
  ```
  `DatabaseSeeder` is empty, so `migrate --seed` and plain `db:seed` create no accounts or categories. The seeders use `create()` and are not idempotent; do not re-run them on an already-seeded database.

## Architecture and business rules

- Put Livewire components in `app/Http/Livewire` (namespace `App\Http\Livewire`, set in `config/livewire.php`), not `app/Livewire`; views go in `resources/views/livewire`. Routes bind component classes directly in `routes/web.php`. Domain logic lives in those components and models; there are no finance controllers, FormRequests, or policies.
- Keep the legacy Laravel 10 skeleton: `bootstrap/app.php` binds `app/Http/Kernel.php` and `app/Console/Kernel.php`, even though the locked framework is Laravel 12. Do not apply Laravel 11/12 skeleton patterns (such as moving middleware into `bootstrap/app.php`) without a deliberate migration.
- Finance data is global, not scoped per user: `accounts`, `categories`, `transactions`, and `subscriptions` have no `user_id`. Authentication only gates the pages; do not assume `auth()->id()` filtering exists.
- Account balances are denormalized in `accounts.available_amount`. Mutate them only through `Account::updateAvailableAmount('Cargo'|'Abono', $amount)`; the strings are exact and anything else silently no-ops. Multi-entry flows (cash withdrawal, savings, transfer) create two transactions and update both accounts; deleting a transaction reverses its type.
- Seeded IDs are hard-coded in `Transactions`: account 1 = Efectivo/cash, 2 = Nómina, 3 = Ahorro/savings; category 8 = Ahorro, 18 = Traspaso, 19 = Depósito. Reordering or inserting seed rows before these breaks `saveWithdraw`, `saveSavings`, `saveTransfer`, and deposit logic. `Subscriptions` also excludes category IDs 18 and 19.
- `Transaction::getAll()` / `getMostRecent()` use the query builder and manually filter `deleted_at`. `Subscription::getAll()` also uses the query builder but omits that filter, so soft-deleted subscriptions still appear in the table. Preserve or fix each behavior deliberately.
- `Transactions::save()` always forces `type = 'Cargo'`; the other operation types have dedicated methods. New user-facing copy should be Spanish, matching the rest of the UI.

## Test notes

- Tests use `RefreshDatabase`, SQLite `:memory:`, and seed `AccountSeeder` + `CategorySeeder` in `setUp`; keep relying on the fixed IDs above.
- Several Jetstream tests skip themselves when features are disabled (API tokens, teams, email verification). This is expected.
- With a stale Livewire 2 vendor tree, `SubscriptionsLivewireTest::test_can_delete_subscription` fails on `$this->dispatch`; after `composer install` (Livewire 3) it should pass.
