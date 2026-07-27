# Ledrix — Running Tests

Automated tests cover the **lead → payment link → successful payment** path, plus refund webhook helpers. Use these after code changes to payments, orders, or leads.

---

## 1. One-time setup

### Database

Tests use **`RefreshDatabase`** (migrations run on each test class). Configure a dedicated test database — do not use production.

**Option A — MySQL (matches this project default)**

1. Create database:
   ```sql
   CREATE DATABASE crm_test_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Copy or set `.env.testing`:
   ```env
   APP_ENV=testing
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=crm_test_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

**Option B — SQLite in-memory (optional, faster)**

Uncomment in `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
Note: only works if all migrations support SQLite (no MySQL-only central DB calls in test path).

### Install dependencies

```bash
composer install
```

---

### Smoke tests (admin + seller portals)

```bash
php vendor/bin/phpunit tests/Feature/AdminSmokeTest.php tests/Feature/SellerSmokeTest.php --testdox
```

Or by group:

```bash
php vendor/bin/phpunit --group smoke
```

These verify login pages, guest redirects, core authenticated routes return **200** (or expected **403**/redirect for role-gated pages), and logout works. They use `@group smoke`, `@group admin`, and `@group seller`.

### Client portal security (Phase 1)

```bash
php vendor/bin/phpunit tests/Feature/ClientPortalSecurityTest.php --testdox
```

Covers portal-access login gate, invoice IDOR protection, and ticket list scoping for authenticated clients.

### Client ↔ seller brief wiring

```bash
php vendor/bin/phpunit tests/Feature/BriefClientSellerTest.php --testdox
```

Verifies client brief submit saves to the order's `questionnairs` row and the seller sees the same answers on that order's brief tab.

### Portal security (Phase 2)

```bash
php vendor/bin/phpunit tests/Feature/SellerPortalSecurityTest.php tests/Feature/ClientPortalSecurityTest.php --testdox
```

Seller IDOR (cross-brand leads/briefs), client ticket IDOR, brief token ownership, and portal-access password reset gates.

### Tenant subscription access

```bash
php vendor/bin/phpunit tests/Unit/SubscriptionAccessServiceTest.php --testdox
```

Covers early-renew billing window, active+expired payment requirement, and past_due/expired states.

---

## 2. Commands to run tests

### All tests

```bash
php vendor/bin/phpunit
```

### Payment flow only (recommended after payment changes)

```bash
php vendor/bin/phpunit tests/Feature/PaymentLeadToSuccessTest.php tests/Unit/PaymentRecordingServiceTest.php tests/Unit/PpcWebhookVerifierTest.php
```

### Single file

```bash
php vendor/bin/phpunit tests/Feature/PaymentLeadToSuccessTest.php
```

### Single test method

```bash
php vendor/bin/phpunit --filter test_lead_to_payment_link_to_successful_stripe_payment
```

### Verbose output

```bash
php vendor/bin/phpunit --testdox tests/Feature/PaymentLeadToSuccessTest.php
```

---

## 3. What each test suite covers

| File | What it verifies |
|------|------------------|
| `tests/Feature/PaymentLeadToSuccessTest.php` | Full flow: lead → `PaymentLinkService` creates order + link → `PaymentRecordingService` records Stripe payment → order **paid**, link **paid**, lead **first_paid** |
| `tests/Unit/PaymentRecordingServiceTest.php` | Payment row created, order totals, idempotency (duplicate txn), lead conversion, webhook duplicate outcome |
| `tests/Unit/PpcWebhookVerifierTest.php` | PayPal refund webhook capture ID extraction |
| `tests/Feature/AdminSmokeTest.php` | Admin login, guest redirect, core pages (dashboard, leads, orders, payments), finance role redirects, logout |
| `tests/Feature/SellerSmokeTest.php` | Seller login, guest redirect, core pages, renewals, performance, brand-payments forbidden for sellers, logout |
| `tests/Feature/ClientPortalSecurityTest.php` | Client login portal gate, invoice ownership, ticket list scoping |
| `tests/Feature/BriefClientSellerTest.php` | Client brief submit → seller sees same order brief |
| `tests/Feature/LeadStoreTest.php` | Lead API intake (update route if API path changes) |
| `tests/Unit/SubscriptionAccessServiceTest.php` | Tenant renewal access: early pay window, active+expired needs payment, past_due/expired billing |

### Payment flow scenarios

1. **Full payment (E2E)** — $1,200 order paid in one checkout; order status `paid`, balance `0`.
2. **Milestone / partial** — $400 paid on $1,000 order; order stays `pending`, balance $600; lead still `first_paid`.
3. **Amount mismatch** — wrong cents from provider → no payment row, link stays `active`.
4. **Idempotency** — same `provider_payment_intent_id` twice → one payment row only.

---

## 4. Good results (pass criteria)

### ✅ Success looks like

```
PHPUnit ... by Sebastian Bergmann

..........                                                        10 / 10 (100%)

Time: 00:XX.XXX, Memory: XX MB

OK (10 tests, XX assertions)
```

- Exit code **0**
- **OK** — no `FAILURES`, `ERRORS`, or `RISKY`
- All payment tests green after deploy or payment refactor

### ❌ Failure means

| Output | Likely cause |
|--------|----------------|
| `SQLSTATE[HY000] [1049] Unknown database` | Create `crm_test_db` or fix `.env.testing` |
| `SQLSTATE[42S02] Base table or view not found` | Run migrations: `php artisan migrate --env=testing` |
| `Expected response status code [201] but received 404` | Lead API route changed — use `route('crm.leads.post')` (`POST /api/crm-lead-post`) |
| `Amount mismatch` / assertion on `status` | Payment business logic changed — update test or fix bug |
| Memory / timeout | MySQL slow; run single file or use SQLite |

---

## 5. When to run

| When | Command |
|------|---------|
| Before production deploy | Full payment suite + smoke tests (§2) |
| After editing `PaymentRecordingService`, `PaymentLinkService`, webhooks | Payment tests + `PpcWebhookVerifierTest` |
| In CI/CD pipeline | `php vendor/bin/phpunit --testsuite Feature --testsuite Unit` |

---

## 6. CI example (GitHub Actions snippet)

```yaml
- name: Run tests
  env:
    DB_CONNECTION: mysql
    DB_DATABASE: crm_test_db
    DB_USERNAME: root
    DB_PASSWORD: password
  run: |
    php artisan migrate --force --env=testing
    php vendor/bin/phpunit tests/Feature/PaymentLeadToSuccessTest.php tests/Unit/PaymentRecordingServiceTest.php
```

---

## 7. Related docs

- Production deploy: [PRODUCTION.md](../PRODUCTION.md)
- Cron / queues: [PRODUCTION.md §4](../PRODUCTION.md#4-cron--scheduler-queues--scheduled-tasks)
