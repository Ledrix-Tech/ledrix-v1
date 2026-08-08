# Ledrix — Production Runbook

Single reference for deploying and running Ledrix smoothly in production.  
Complete these steps once at launch, then run **post-deploy** after every release.

---

## Quick checklist

- [ ] Server: PHP 8.2+, MySQL, Composer, Node (for asset build if needed)
- [ ] `.env` configured (see §2) — **`APP_DEBUG=false`**, correct **`APP_URL`**
- [ ] **Both** databases migrated: primary CRM + **central** SaaS (see §3)
- [ ] `storage/` and `bootstrap/cache/` writable
- [ ] **One cron job** registered (see §4)
- [ ] SMTP mail working (send a test notification)
- [ ] `QUEUE_CONNECTION=database` (not `sync`)
- [ ] CRM payment webhooks registered in Stripe/PayPal (see §6)
- [ ] **Tenant SaaS** Stripe platform webhook registered (see §6.1)
- [ ] Super Admin → Payment Accounts filled (Stripe / PayFast / Meezan / JazzCash / Payoneer)
- [ ] `BILLING_ADMIN_EMAIL` set for ops alerts (demo/contact/support)
- [ ] Super Admin owner account created + **2FA enabled**
- [ ] Per-brand **Account Keys** filled (Stripe/PayPal + webhook secrets)
- [ ] Logo assets present under `public/admin-assets/dpm-logos/`
- [ ] Post-deploy script run: `bash scripts/post-deploy.sh`
- [ ] Verify: `php artisan schedule:list`
- [ ] Smoke-test: one USD Stripe + one PKR (PayFast or Meezan) subscription payment

---

## 1. First-time server setup

### 1.1 Clone & install

```bash
cd /var/www/ledrix   # your path
git pull origin main
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Build front-end assets if you deploy compiled assets from CI:

```bash
npm ci && npm run build
```

### 1.2 Permissions (Linux)

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

### 1.3 Web server

- Document root must be **`public/`** (not project root).
- Force HTTPS.
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `json`, `bcmath`.

---

## 2. Environment (`.env`)

Copy from `.env.example` and set at minimum:

```env
APP_NAME=Ledrix
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=UTC

# Databases
DB_CONNECTION=primary
DB_PRIMARY_HOST=127.0.0.1
DB_PRIMARY_DATABASE=ledrix_primary
DB_PRIMARY_USERNAME=...
DB_PRIMARY_PASSWORD=...

DB_SUPER_CONNECTION=central
DB_SUPER_HOST=127.0.0.1
DB_SUPER_DATABASE=ledrix_central
DB_SUPER_USERNAME=...
DB_SUPER_PASSWORD=...

# Sessions & cache (production)
SESSION_DRIVER=database
CACHE_STORE=database          # or redis for multi-server / webhook idempotency

# Queue — REQUIRED for emails/notifications
QUEUE_CONNECTION=database       # not "sync" in production

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe (CRM orders + tenant billing fallback / seed for Payment Accounts)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...   # CRM brand webhooks + platform SaaS webhook

# PayPal (env fallback when brand keys missing)
PAYPAL client vars + PAYPAL webhook_id if using env-level PayPal

# SaaS billing ops
BILLING_ADMIN_EMAIL=billing@yourdomain.com   # demo/contact/ops alerts
# Optional gateway env seeds (prefer Super Admin → Payment Accounts in prod):
# JAZZCASH_*, PAYFAST_*, MEEZAN_*, PAYONEER_RECEIVER_EMAIL
```

**Never commit `.env` or real passwords to git.**

---

## 3. Database migrations

Run **both** connections on every deploy (post-deploy scripts do this):

```bash
# Primary CRM DB (default connection)
php artisan migrate --force

# Central SaaS / Super Admin DB (tenants, invoices, billing, SA users)
php artisan migrate --database=central --path=database/migrations/central --force
```

Confirm both DBs are reachable. Confirm `jobs` and `failed_jobs` tables exist when using `QUEUE_CONNECTION=database`.
---

## 4. Cron & scheduler (queues + scheduled tasks)

Ledrix uses **one cron entry** for everything: queued mail, notifications, lead auto-reply, ticket checks, tenant trials, etc.

### Linux (recommended)

```bash
crontab -e
```

Add (replace path):

```cron
* * * * * cd /path/to/ledrix && php artisan schedule:run >> /path/to/ledrix/storage/logs/scheduler.log 2>&1
```

See also: `scripts/cron.example`

**You do not need a separate `queue:work` cron.** The scheduler drains the queue every minute (`routes/console.php`).

### Windows (XAMPP / local server)

Task Scheduler → trigger **every 1 minute** → run:

```powershell
powershell -ExecutionPolicy Bypass -File "F:\path\to\ledrix\scripts\schedule-run.ps1"
```

### Verify

```bash
php artisan schedule:list
```

You should see `process-queue` every minute plus daily/hourly tasks.

### Local development (no cron)

Either:

```env
QUEUE_CONNECTION=sync
```

or run in a terminal:

```bash
php artisan schedule:work
```

---

## 5. Deploy after every release

### Linux

```bash
bash scripts/post-deploy.sh
```

### Windows

```powershell
.\scripts\post-deploy.ps1
```

This runs: maintenance mode → migrate → config/route/view cache → `queue:restart` → up.

Manual equivalent:

```bash
php artisan down --retry=60
php artisan migrate --force
php artisan migrate --database=central --path=database/migrations/central --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

---

## 6. Payment webhooks

Register these URLs in **Stripe** and **PayPal** dashboards. Base URL = your `APP_URL`.

| Purpose | Method | URL |
|--------|--------|-----|
| **SaaS tenant Stripe** (subscriptions) | POST | `{APP_URL}/api/webhooks/platform/stripe` |
| PPC Stripe capture | POST | `{APP_URL}/api/webhooks/stripe` |
| PPC PayPal capture | POST | `{APP_URL}/api/webhooks/paypal` |
| PPC Stripe refunds | POST | `{APP_URL}/api/webhooks/stripe/refund` |
| PPC Stripe disputes | POST | `{APP_URL}/api/webhooks/stripe/dispute` |
| PPC PayPal refunds | POST | `{APP_URL}/api/webhooks/paypal/refund` |
| PPC PayPal disputes | POST | `{APP_URL}/api/webhooks/paypal/dispute` |
| Upwork Stripe refund | POST | `{APP_URL}/api/webhooks/upwork-stripe/refund` |
| Upwork Stripe dispute | POST | `{APP_URL}/api/webhooks/upwork-stripe/dispute` |
| Upwork Stripe capture | POST | `{APP_URL}/api/webhooks/upwork/stripe` |
| Upwork PayPal capture | POST | `{APP_URL}/api/webhooks/upwork/paypal` |

### 6.1 Tenant SaaS billing (Ledrix subscriptions)

Managed in **Super Admin → Payment Accounts** (preferred over `.env` alone).

| Provider | Role |
|----------|------|
| Stripe | International USD checkout |
| PayFast | Pakistan PKR hosted checkout |
| Meezan | PKR bank transfer + Raast QR (manual confirm) |
| JazzCash | PKR merchant checkout (if enabled) |
| Payoneer | Manual USD invoice (SA confirms) |

**Stripe platform webhook (required for reliable activation):**

1. Stripe Dashboard → Webhooks → Add endpoint  
   URL: `{APP_URL}/api/webhooks/platform/stripe`  
   Event: `checkout.session.completed` (minimum)
2. Copy signing secret → Super Admin → Payment Accounts → Stripe → **Webhook secret**  
   (or `STRIPE_WEBHOOK_SECRET` as seed)

Browser return URLs also activate subscriptions (`/billing/stripe/success`, PayFast/JazzCash returns) and write **Webhook Events** in Super Admin. Prefer the Stripe platform webhook so activation does not depend on the browser.

**Tenant payment emails (queued — cron required):**

| When | Mail |
|------|------|
| Invoice issued / payment due | `TenantSubscriptionDueMail` + link to invoice |
| Payment confirmed / subscription activated | `TenantSubscriptionActivatedMail` + paid invoice details + **View invoice** link |

Tenant invoice UI: `/tenant-profile/billing/invoices/{id}` (also under Admin CRM → Billing).

### Stripe events to subscribe

**Capture** (`/api/webhooks/stripe`):

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `payment_intent.payment_failed`

**Refunds** (`/api/webhooks/stripe/refund`):

- `charge.refunded`
- `charge.refund.updated`

**Disputes** (`/api/webhooks/stripe/dispute`):

- `charge.dispute.created`
- `charge.dispute.updated`
- `charge.dispute.closed`

### PayPal events to subscribe

**Capture** (`/api/webhooks/paypal`):

- `PAYMENT.CAPTURE.COMPLETED`
- `CHECKOUT.ORDER.COMPLETED`
- Failure events as configured in PayPal dashboard

**Refunds** (`/api/webhooks/paypal/refund`):

- `PAYMENT.CAPTURE.REFUNDED`
- `PAYMENT.SALE.REFUNDED`

**Disputes** (`/api/webhooks/paypal/dispute`):

- `CUSTOMER.DISPUTE.CREATED`
- `CUSTOMER.DISPUTE.UPDATED`
- `CUSTOMER.DISPUTE.RESOLVED`

### Per-brand webhook secrets (required for PPC)

In **Admin → Account Keys** (module `ppc`), each brand needs:

| Field | Used for |
|-------|----------|
| `stripe_webhook_secret` | Stripe capture/refund/dispute verification for that brand |
| `paypal_webhook_id` | PayPal signature verification for that brand |

Without these, webhooks may reject or fail in production.

---

## 7. Email & branding

- Logo: `public/admin-assets/dpm-logos/logo-ic.png` (emails use `APP_URL` + `asset()`).
- All mail templates use `resources/views/emails/layouts/ledrix.blade.php`.
- Most notifications / SaaS mails implement `ShouldQueue` — **cron must be running** (§4).
- Ops alerts (demo requests, contact form) go to `BILLING_ADMIN_EMAIL` (falls back to `MAIL_FROM_ADDRESS`).

### Test mail

```bash
php artisan tinker
# Mail::raw('Test', fn ($m) => $m->to('you@example.com')->subject('Ledrix test'));
```

### Super Admin

- Login: `{APP_URL}/super-admin/login`
- Enable **2FA Security** for owner/admin accounts
- Seed owner via seeder / existing invite flow if needed
- Team invites are owner-only

### Tenant renewal approval email

- Super admin sends from tenant detail → **Send renewal approval email**
- Public approve link route: `GET /renew/approve/{token}` (`super-renew.approve`)

### Paid subscription renewal (automated)

Daily scheduler runs `tenants:process-subscriptions` (see `routes/console.php`):

| When | Email / action |
|------|----------------|
| 7 days before `end_date` | `TenantSubscriptionRenewalReminderMail` |
| 3 days before `end_date` | Same template |
| 1 day before `end_date` | Same template (urgent subject) |
| After `end_date` (active → past_due) | `TenantSubscriptionExpiredMail` |
| Grace period elapsed | Status → `expired` |

Optional `.env` overrides:

```env
SUBSCRIPTION_RENEWAL_REMINDER_DAYS=7,3,1
SUBSCRIPTION_EARLY_RENEW_DAYS=7
SUBSCRIPTION_PAST_DUE_GRACE_DAYS=7
```

Tenants can **renew early** from `/tenant-profile/billing` when within `SUBSCRIPTION_EARLY_RENEW_DAYS` of expiry. Successful payment clears reminder timestamps and extends from the current `end_date` (no lost days).

Manual run:

```bash
php artisan tenants:process-subscriptions
```

---

## 8. Caching & performance

| Setting | Production suggestion |
|---------|------------------------|
| `CACHE_STORE` | `database` or `redis` |
| `SESSION_DRIVER` | `database` |
| Config | `php artisan config:cache` after deploy |
| Routes | `php artisan route:cache` after deploy |
| Views | `php artisan view:cache` after deploy |

Payment webhook idempotency uses cache locks — prefer **database** or **redis** cache in production, not `file` on multi-server setups.

---

## 9. Monitoring & troubleshooting

### Failed queue jobs

```bash
php artisan queue:failed
php artisan queue:retry all          # retry all failed
php artisan queue:flush              # clear failed (careful)
```

Failed jobs are pruned weekly by the scheduler (`queue:prune-failed`).

### Logs

| Log | Location |
|-----|----------|
| Application | `storage/logs/laravel.log` |
| Scheduler cron | `storage/logs/scheduler.log` |

### Common issues

| Symptom | Fix |
|---------|-----|
| Emails never send | Check `QUEUE_CONNECTION=database`, cron running, `jobs` table, SMTP creds |
| Subscription paid but no invoice email | Cron/queue; check `failed_jobs`; tenant email on central `tenants` |
| SaaS Stripe paid but not activated | Platform webhook URL + webhook secret in Payment Accounts; check Super Admin → Webhook Events |
| Central tables missing | `php artisan migrate --database=central --path=database/migrations/central --force` |
| Stripe webhook 400/500 | Brand `stripe_webhook_secret` in Account Keys; `APP_URL` correct |
| PayPal webhook rejected | Brand `paypal_webhook_id`; production requires webhook_id |
| Logo broken in email | `APP_URL` must be public HTTPS; file exists at `public/admin-assets/dpm-logos/logo-ic.png` |
| 419 / session errors | `SESSION_DOMAIN`, HTTPS, `APP_URL` aligned |

---

## 10. Security checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS everywhere; `APP_URL` uses `https://`
- [ ] Strong DB passwords; DB not publicly exposed
- [ ] `.env` not web-accessible
- [ ] Admin/seller use POST logout (already implemented)
- [ ] Rate limits on auth routes active
- [ ] Stripe/PayPal **live** keys only on production server
- [ ] Super Admin 2FA enabled for owner (and admins)
- [ ] Default seeder passwords changed / not used in production

---

## 11. Optional: high-volume queue (Supervisor)

If you send **many** emails per minute, add a dedicated always-on worker **in addition to** cron (not instead of scheduler for other tasks):

```ini
; /etc/supervisor/conf.d/ledrix-worker.conf
[program:ledrix-worker]
command=php /path/to/ledrix/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/ledrix/storage/logs/worker.log
```

After deploy:

```bash
php artisan queue:restart
sudo supervisorctl reread && sudo supervisorctl update
```

For most Ledrix installs, **cron + scheduler alone is enough**.

---

## 12. Reference files in this repo

| File | Purpose |
|------|---------|
| `scripts/cron.example` | Copy/paste crontab line |
| `scripts/post-deploy.sh` | Linux post-deploy |
| `scripts/post-deploy.ps1` | Windows post-deploy |
| `scripts/schedule-run.ps1` | Windows Task Scheduler helper |
| `routes/console.php` | All scheduled tasks |
| `.env.example` | Environment template |
| `tests/TESTING.md` | How to run automated tests (payment flow) |

---

## 13. Post-launch verification (5 minutes)

```bash
php artisan schedule:list
php artisan route:list --path=webhooks
php artisan migrate:status
php artisan migrate:status --database=central --path=database/migrations/central
curl -I https://yourdomain.com
# Trigger a test SaaS Stripe checkout (test mode) — confirm invoice email + /tenant-profile/billing invoice View
# Or: php artisan tinker → send test Mail::raw
tail -f storage/logs/scheduler.log   # confirm cron hits every minute
```

When all checks pass, Ledrix is ready for production traffic.
