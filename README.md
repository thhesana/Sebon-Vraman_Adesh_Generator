# Vraman Adesh Generator

Laravel 12 application for the Securities Board of Nepal (SEBON) that generates and tracks **domestic** and
**international travel orders (TADA)**: employees, levels, countries, cities, districts, fiscal years, USD/NPR rates,
reports, printable orders and per-employee notification mails. It is a conversion of the former `*.php` application and runs
against the **existing SQL Server schema** (there are no migrations).

## Setup

Requirements: PHP 8.2+, Composer, SQL Server, and the PHP `sqlsrv` + `pdo_sqlsrv` extensions
(Microsoft Drivers for PHP for SQL Server) enabled in `php.ini`.

```bash
composer install
cp .env.example .env        # then edit it
php artisan key:generate
php artisan serve           # or point Apache/IIS at public/
```

Important `.env` values: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`,
`MAIL_SENDER_EMAIL`, `MAIL_SENDER_NAME`, `OAUTH_CONFIG_TABLE` (see Mail). Do not run `migrate` or seeders against
the production database; the schema is managed outside this project.

Assets are plain files under `public/css` and `public/js` (no build step); Bootstrap, Chart.js, jQuery and Select2 come from CDNs.

## Structure

- `routes/web.php` is the route contract (resource controllers, all application routes behind `auth`).
- `app/Http/Controllers`, `app/Http/Requests` (FormRequests), `app/Models` (Eloquent on the legacy tables), `app/Services`
  (TADA calculation, reports, USD forex, mail).
- `resources/views` uses one layout (`layouts/app`), anonymous components in `components/` (search form, data table,
  form and select fields, pagination) and per-page assets in `public/css/<area>` and `public/js/<area>`.

## Routes overview

| Area | Routes |
|------|--------|
| Auth | `/login`, `/logout`, `/change-password` (plain-text passwords are forced to change to a hash) |
| Dashboard | `/dashboard` (last 12 months, chart) |
| USD rate | `/usd-rates`, `/usd-converter` (fetches the NRB rate and stores it via `sp_UpsertUSDForex`) |
| Master data | `/levels`, `/countries`, `/cities`, `/districts`, `/employees`, `/fiscal-years` |
| Domestic TADA | `/domestic-tada` (+ `/{batch}/edit`, `/{batch}/print`, `/report`) |
| International TADA | `/international-tada` (+ `/{batch}/edit`, `/{batch}/print`, `/history`, `/report`) |

`php artisan route:list --except-vendor` shows the full list.

## Mail

`App\Services\MailService` sends the batch notification mails with PHPMailer over Office 365 SMTP using **XOAUTH2**
(client-credentials flow, `MicrosoftClientCredentialsProvider`). `ClientID`, `ClientSecret` and `TenantID` are read
(latest row) from the `MicrosoftOAuthConfig` table, whose cross-database name is `OAUTH_CONFIG_TABLE`. Recipients come from
`Employee_Information`; a batch is marked sent in `BatchMailQueue` only when every mail succeeded, so failed batches can be retried.
Progress and failures are written to `storage/logs/mail.log`.

## Queue / after-response dispatch

Mails are slow, so the controllers use `SendBatchMails::dispatchAfterResponse('Domestic'|'International', $batchId)`:
the job runs in the same PHP process right after the HTTP response is sent, so no queue worker is required and
`QUEUE_CONNECTION=sync` is fine. (If a worker is ever needed, switch to `dispatch()` and set up the `jobs` table and a worker.)

## Legacy URL redirects

`routes/legacy.php` 301-redirects every old `*.php` URL (for example `dashboard.php`, `usd_rate.php`,
`EditDomesticTada.php?batch_id=...`, `employee_edit.php?code=...`) to its named route, so existing bookmarks keep working.

## Tests

```bash
php artisan test
```

Feature tests cover guest redirects for every protected route, the login page, the legacy redirects and view rendering.
`phpunit.xml` forces an in-memory SQLite connection, so tests never reach SQL Server.
