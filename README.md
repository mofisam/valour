# Valour Empire Laundry Services

Business management app for a laundry: customers, jobs, invoices, part-payments, expenses and reports.
Plain PHP 8.1+ MVC (no framework), MySQLi prepared statements, Tailwind CSS, vanilla JS.

## Quick start (local)

1. **Create the database**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seeds/001_defaults.sql
   ```
2. **Configure** – edit `.env` (DB name, user, password). `.env.example` shows every key.
3. **Create your admin account** (the company name is optional here; you can set it later in Settings)
   ```bash
   php bin/create-admin.php "Your Name" you@example.com "a-strong-password" "Your Company Name"
   ```
4. **Run it**
   ```bash
   php -S localhost:8000 -t public
   ```
   Open http://localhost:8000 and sign in.
   (On XAMPP/WAMP/Apache: point the virtual host's DocumentRoot at `public/`.)

## Upgrading an existing install

If you imported an earlier version of the schema, run these once, in order:

1. `database/migrations/002_reminders.sql` (reminders table)
2. `database/migrations/003_accounting_and_job_options.sql` (monthly invoice numbers, White/Coloured and Long/Short/Big/Small/Nil job options, direct-cost flag on expense categories)
3. `database/migrations/004_workflows_pricing_notifications.sql` (per-service process steps, service price list, "needs pricing" flag, notifications)
4. `database/migrations/005_machine_time.sql` (services charged per hour, "In use" status)

Fresh installs only need `schema.sql` and the seed file.

## Company name and branding

Nothing is hard-coded. Sign in as admin and open **Settings → Business details** to set the company name, logo,
login headline, address and bank details. The name appears in the sidebar, login page, browser tab and invoices.

## Light and dark mode

Use the sun/moon button in the top bar (or on the login page). The choice is remembered per browser; the first visit follows the device setting.
Colours are CSS variables defined in `public/assets/css/input.css`, so a new page only needs the theme classes (`bg-surface`, `text-fg`, `text-muted`, `border-line`, `bg-canvas`).

## Reminders (admin)

**Reminders** in the sidebar lets an admin set a date and time, optional repeat (daily/weekly/monthly) and notes.
When the time arrives, a pop-up and a bell badge appear on whatever page is open (checked every minute), with Done and Snooze buttons.
Desktop notifications can be switched on from the Reminders page. Reminders only fire while the app is open in a browser;
they are not emails or SMS.

## Tailwind

Until you build the CSS, pages load the Tailwind CDN script (fine for development).
For production build the real file:

```bash
npm install
npm run build        # or: npm run dev   (watch mode)
```

Once `public/assets/css/app.css` is built, the CDN script is skipped automatically.
If you change colours, update both `tailwind.config.js` and the inline config in `app/Views/partials/head.php`.

## PDF invoices (optional)

```bash
composer require dompdf/dompdf
```
The "Download PDF" button works once dompdf is installed. Without it, use Print → Save as PDF.

## Structure

```
public/            web root: index.php (front controller), assets/
app/Core/          Router, Model, Database, Request, Session, Auth, Csrf, Validator, View, Paginator
app/Controllers/   one per module (+ Api/ for JSON endpoints)
app/Models/        mysqli models (static, prepared statements only)
app/Services/      PricingService, InvoiceService, ReportService, UploadService
app/Middleware/    AuthMiddleware, RoleMiddleware (CSRF is checked globally in the Router)
app/Views/         layouts/, partials/, one folder per module
config/            app.php, database.php, routes.php
database/          schema.sql, seeds/, migrations/
storage/           logs (app.log), cache, invoices
bin/               create-admin.php
```

Request flow: `public/index.php` → `App` → `Router` (config/routes.php) → CSRF + middleware → Controller → Model/Service → View.

## Accounting terms used in the app

- **Revenue**: total billed on jobs (drafts and cancelled jobs excluded), by job date.
- **Bank / Cash**: payments actually received (Cash = cash payments; Bank = transfer, POS and other).
- **Receivables**: billed but not yet paid. The **Receivables** page shows them by customer with 0–30 / 31–60 / 61–90 / 90+ day ageing.
- **Gross profit**: revenue minus *direct costs* (expense categories ticked "Cost of service").
- **Net profit**: revenue minus all expenses.

Invoice numbers look like `VE-26-10-001` (prefix, year, month, running number; the count restarts each month).

## Roles

- **Staff**: Dashboard (no financial figures), Jobs and Settings only. They pick an existing customer in the job form, but cannot open the Customers or Expenses pages.
  - Staff create jobs with **no price**. A job they submit is flagged "Awaiting price" and every admin gets a notification.
  - A submitted job is locked for staff: they can only **update its status**. They can edit a job while it is still a draft.
  - Staff cannot record or edit payments, set prices or discounts, add customers or cancel jobs.
- **Administrator**: everything, plus customers, expenses, pricing jobs, payments, reports, receivables, activity log, reminders, notifications, services & prices, expense categories, staff accounts and business settings.

## Services: process and prices

Under **Services & prices** the admin sets, for each service:

- **Process steps**: which statuses a job passes through (Pending → Washing / Dry cleaning / Ironing → Ready → Delivered). An ironing-only service simply has no Washing step, so staff only see the statuses that apply.
- **Price list**: the price of every clothing type for that service (per-kg services are priced by weight). When staff submit a job, the admin opens it and clicks **Price from price list**, or edits the job to type prices in by hand.

## Security: automatic sign-out

Anyone who has not used the app for **10 minutes** is signed out (change it with `IDLE_MINUTES` in `.env`).
After 9 minutes a warning with a countdown appears, with a "Stay signed in" button. Background checks (reminder alerts) do not count as activity.
The server enforces the limit too, so closing or freezing the browser tab does not keep a session alive.

## Services charged by time

A service can be **Per item**, **Per kg** or **Per hour (machine time)**. For machine time, staff enter how long the customer used the machine
and the admin's hourly rate does the rest; no clothing list is needed.

## Bank & Cash

Click **Bank / Cash received** on the admin dashboard for the detail page: cash vs bank (transfer, POS, other), a chart, filters and every payment.
It is not in the sidebar.

## Security notes

- All SQL uses bound parameters. All output goes through `e()`.
- CSRF token required on every POST; sessions are regenerated on login; login is throttled (5 tries → 5-minute lock per session).
- Only `public/` should be web-accessible. Set `APP_DEBUG=false` in production.
- Uploaded logos are restricted to JPG/PNG/WebP and PHP execution is disabled in `public/assets/uploads/`.
- Use HTTPS in production (the session cookie becomes `Secure` automatically).

## Migrating data from the old database

The old table names map as follows: `daily_expenses → expenses`, `profiles + user_roles → users + roles`,
`jobs.customer_name` and `customers.total_amount_spent` are dropped (computed instead), and
`jobs.amount_paid` becomes one row in `payments` per job. Ask for a migration script if you want it written.

## Not included yet

Password reset by email, SMS/WhatsApp notifications, automated tests.
