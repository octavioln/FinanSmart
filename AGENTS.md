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

- Full suite (SQLite `:memory:` configured in `phpunit.xml`): `& $php artisan test`. Current baseline: 67 passed, 7 skipped, 161 assertions.
- One suite: `& $php artisan test --testsuite=Unit` (or `Feature`)
- One class: `& $php artisan test --filter=TransactionsLivewireTest`
- Format changed files: `& $php vendor/bin/pint --dirty`; check-only with `--test`. There is no CI and no composer lint/test script.
- Database setup: `& $php artisan migrate`, then seed **explicitly**:
  ```powershell
  & $php artisan db:seed --class=AccountSeeder
  & $php artisan db:seed --class=CategorySeeder
  ```
  `DatabaseSeeder` is empty, so `migrate --seed` and plain `db:seed` create no accounts or categories. The seeders use `create()` and are not idempotent; do not re-run them on an already-seeded database.
- Serve locally with `& $php artisan serve` (or the Laragon virtual host).

## Architecture and business rules

- Livewire components live in `app/Http/Livewire` (namespace `App\Http\Livewire`, set in `config/livewire.php`), with views in `resources/views/livewire`; routes in `routes/web.php` bind component classes directly. Auth is Jetstream/Fortify/Sanctum (`app/Actions/Fortify`, `app/Actions/Jetstream`); there are no finance controllers, FormRequests, or policies.
- Keep the legacy Laravel 10 skeleton: `bootstrap/app.php` binds `app/Http/Kernel.php` and `app/Console/Kernel.php`, even though the locked framework is Laravel 12. Do not apply Laravel 11/12 skeleton patterns (such as moving middleware into `bootstrap/app.php`) without a deliberate migration.
- Finance data is global, not scoped per user: `accounts`, `categories`, `transactions`, and `subscriptions` have no `user_id`. Authentication only gates the pages; do not assume `auth()->id()` filtering exists.
- Account balances are denormalized in `accounts.available_amount`. Mutate them only through `Account::updateAvailableAmount('Cargo'|'Abono', $amount)`; the strings are exact and any other value silently no-ops. Multi-entry flows (cash withdrawal, savings, transfer) create two transactions and update both accounts; deleting a transaction reverses its type.
- Seeded IDs are hard-coded in `Transactions`: account 1 = Efectivo/cash, 2 = Nómina, 3 = Ahorro/savings; category 8 = Ahorro, 18 = Traspaso, 19 = Depósito. Reordering or inserting seed rows before these breaks `saveWithdraw`, `saveSavings`, `saveTransfer`, and deposit logic. `Subscriptions` also excludes category IDs 18 and 19 from its category dropdown.
- `Transaction::getAll()` / `getMostRecent()` use the query builder and manually filter `deleted_at`. `Subscription::getAll()` also uses the query builder but omits that filter, so soft-deleted subscriptions still appear in the table. Preserve or fix each behavior deliberately.
- `Transactions::save()` always forces `type = 'Cargo'`; the other operation types have dedicated methods: `saveWithdraw` (cash account 1), `saveDeposit` (category 19), `saveSavings` (savings account 3, categories 8/19), `saveTransfer` (category 18), and `saveSubscription` (copies the subscription's account, category, and amount). New user-facing copy should be Spanish, matching the rest of the UI.

## Test notes

- Tests use `RefreshDatabase` and SQLite `:memory:`. Only `TransactionsLivewireTest` seeds `AccountSeeder` + `CategorySeeder` in `setUp` and relies on the fixed IDs above; `SubscriptionsLivewireTest` and the unit tests use model factories instead.
- Current suite: 67 passing, 7 skipped, 161 assertions. The 7 skips are Jetstream/Fortify features that are disabled: API tokens (3), email verification (3), and the registration-disabled case (1). Account deletion and 2FA are enabled; teams are not enabled, so do not assume team scoping.
- With a stale Livewire 2 vendor tree, `$this->dispatch` is unavailable and Livewire tests fail; reinstall dependencies (`composer install`, not `update`) to get Livewire 3.
