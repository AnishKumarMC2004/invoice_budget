# Finora — Invoice & Budget Management

A Laravel 12 application for managing sales and purchase invoices, customers, suppliers, a reusable product/service catalog, budgets, expenses, payments, and financial reports. The responsive Blade interface uses Bootstrap 5 and a small set of custom styles.

## Requirements

- PHP 8.2+
- PHP extensions required by Laravel, including `pdo_mysql`; enable `pdo_sqlite` to run the test suite
- Composer
- MySQL 8+ (or MariaDB with equivalent Laravel support)
- Internet access for the Bootstrap/font CDNs and live currency data

## Setup

1. Create a MySQL database, for example `invoice_budget`.
2. Copy `.env.example` to `.env` and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for your local database.
3. Configure the seeded admin credentials before seeding:

   ```dotenv
   ADMIN_NAME="Finora Administrator"
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=change-this-password
   ```

4. Install and initialize the application:

   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve
   ```

5. Open `http://127.0.0.1:8000` and sign in with the `ADMIN_EMAIL` and `ADMIN_PASSWORD` values from `.env`.

The seeder adds sample customers, a supplier, catalog items, an invoice, a budget, and an expense so the dashboard is populated on first launch. Re-running the seeder updates the admin and avoids duplicating the sample financial records.

## SQLite quick start

For a local demo without MySQL, configure `.env` with:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Create the empty database file before migrating:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

## Test and lint

```bash
php artisan test
vendor/bin/pint --test
```

The feature tests use an in-memory SQLite database and do not require a separate test database.

## Financial behavior

- Invoice line subtotal = quantity × unit price.
- Discount is calculated per line and rounded to cents.
- Tax is calculated on the discounted line amount and rounded to cents.
- Invoice totals are computed server-side; the form preview is informational.
- Invoices and financial reports use USD as the consistent base currency; reference exchange rates are displayed separately on the dashboard.
- Payments are recorded against an invoice, cannot exceed its remaining balance, and update paid / partial / pending / overdue status.
- Budget actuals are the sum of linked expenses. Monthly budgets only accept expenses dated in that month and year; yearly budgets accept expenses in that year.
- Reports aggregate sales, purchase invoices, and operating expenses by issue/expense date for the selected year.

## Currency API

The dashboard requests the latest USD reference rates for EUR, GBP, and INR from the public [Frankfurter API](https://www.frankfurter.app/). Responses are cached for six hours and shown with the API's effective date. If the service is unreachable, the dashboard displays an explicit unavailable state rather than invented rates. The authenticated JSON endpoint is `/api/exchange-rates`.

## Main areas

- `/dashboard` — financial overview, six-month sales/purchase trend, budget pulse, open invoices, and exchange rates
- `/invoices/sales` and `/invoices/purchase` — invoice listing, search, status filter, create/edit/delete, itemized detail, and payments
- `/budgets` and `/expenses` — monthly/yearly budgets and categorized expenses
- `/customers`, `/suppliers`, `/products` — billing contacts and reusable items
- `/reports` — annual monthly financial analysis
- `/login` — admin sign-in
