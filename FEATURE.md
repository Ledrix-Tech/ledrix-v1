# Ledrix SaaS — Remaining Features

Product backlog for Super Admin + central DB gaps that a typical multi-tenant SaaS control plane still needs.

**Status key**

| Tag | Meaning |
|-----|---------|
| `critical` | Should land before (or immediately at) production scale |
| `soon` | Important shortly after launch |
| `later` | Enterprise / nice-to-have |
| `cleanup` | Dead code / path consolidation (not a customer feature) |

**Contrast — already shipped (not in this backlog)**  
Auth/roles/2FA/invites · tenants + limits/features · Payment Accounts (Stripe, PayFast, Meezan, JazzCash, Payoneer) · manual payment confirm · trial/subscription schedulers · invoice HTML views + due/paid emails · Stripe platform webhook + Webhook Events UI · API tokens + `/api/v1` · support/demos/referrals/announcements/audit · dual Admin/Tenant billing portal · JazzCash token auto-renew.

Ops deploy steps live in [`PRODUCTION.md`](PRODUCTION.md). This file is the **product** remaining list.

---

## 1. Critical

### 1.1 Stripe (and PayFast) auto-renew / saved-card charging
- Recurring charge for USD (Stripe) and ideally PKR hosted gateways
- Persist and use payment methods after first checkout (not Checkout one-shots only)
- Today: JazzCash token renew exists; Stripe is one-time Checkout

### 1.2 Real dunning
- Failed-payment retry ladder
- Customer “update card” flow
- Grace period with CRM access for `past_due` (or explicit soft-wall policy)
- Today: reminder/expired emails; CRM access only for `active` / `trialing`

### 1.3 PayFast / JazzCash server-side notify webhooks
- ITN / server callback endpoints that activate subscriptions without browser return
- Record into `platform_webhook_events` like Stripe
- Today: browser return routes only for those gateways

### 1.4 Unify legacy renewal approval onto modern billing
- Rewrite or remove `StripeController` send/approve + PaymentIntent path
- Must go through invoice create + `ActivateTenantSubscriptionService`
- Today: parallel path can activate without matching invoice/audit flow

### 1.5 Invoice PDF download
- Generate PDF on paid (and optionally issued) invoices
- Store `tenant_invoices.pdf_path`; download from tenant + Super Admin invoice views
- DomPDF is already a dependency but unused for SaaS invoices

### 1.6 Tax / VAT on invoices
- Configurable tax rate / region (at least per-tenant or per-country)
- Populate `tax_amount` and show on invoice HTML/PDF/email
- Today: `tax_amount` always `0`

### 1.7 Proper suspend + tenant offboard / delete
- Use suspend metadata (`suspended_reason`, `suspended_at`) consistently
- Soft-delete / offboard: revoke API tokens, cancel memberships, optional primary CRM data wipe
- Block invalid status values in SA UI
- Today: status toggle only; SoftDeletes on `tenants` unused from SA

### 1.8 Super Admin impersonation
- “Login as tenant” / enter CRM workspace for support diagnosis
- Full audit log of impersonation sessions
- Today: not built

---

## 2. Soon after launch

### 2.1 SaaS analytics dashboard
- MRR, ARR, churn, new trials, failed payments, revenue by gateway/currency
- Today: Super Admin dashboard is mostly counts

### 2.2 Self-serve plan change / cancel
- Upgrade / downgrade with clear proration rules
- Cancel at period end (and optional immediate cancel)
- Wire unused membership `cancelled` status into tenant UX

### 2.3 Usage metering depth
- Real `max_storage_mb` usage (today always `0`)
- Soft warning emails at ~80% / 100% of limits
- Optional overage policy later

### 2.4 Credit notes / void / refund SaaS invoices
- Void issued invoices
- Refund paid subscription payments with audit trail
- Credit note document (HTML/PDF)

### 2.5 Enforce 2FA for owner (optional for admin)
- Policy: owner cannot skip 2FA in production
- Today: 2FA is optional if enrolled

### 2.6 Custom domain / white-label provisioning UX
- SA (or tenant) flow to set `custom_domain`, verify DNS
- Align with plan feature flags already on packages

### 2.7 Tenant data export from Super Admin
- Zip/package: tenant profile, memberships, invoices, payments, key CRM export summary
- Today: no SA “export this tenant” action

### 2.8 Tenant-facing maintenance / status broadcast
- SA message: “platform maintenance” visible in tenant/admin CRM
- Distinct from Laravel `artisan down` and per-section portal flags

### 2.9 Deeper billing / SA automated tests
- Activate payment E2E, Stripe platform webhook, dunning command, invoice paid email, suspend/offboard, dual-org billing routes
- Today: role gates + limited ops tests

### 2.10 Cleanup orphan Central code
- Remove or rewire unused: `PaymentController`, `LimitationController`, most of `SuperManageController`
- Missing views (`data-limits`, `company-analytics`) or delete controllers

---

## 3. Later / enterprise

### 3.1 Outbound tenant webhooks
- Deliver events to customer endpoints: `invoice.paid`, `membership.expired`, `subscription.renewed`, etc.
- Signing secret, retries, delivery log
- Note: plan flag `feature_webhooks` today means CRM payment capture webhooks, not this

### 3.2 SSO / SAML / OIDC / SCIM
- Enterprise IdP login for Super Admin and/or tenant admins
- Optional SCIM user provisioning

### 3.3 GDPR / privacy tooling
- DSAR export workflow
- Right-to-erasure job (anonymize + purge primary tenant data)
- Beyond SoftDeletes alone

### 3.4 Public status / SLA page
- Incident history, uptime, subscribe for updates

### 3.5 More currencies + FX
- Beyond USD/PKR regional split
- Live or admin-managed FX rates

### 3.6 Full tenant management API
- Expand beyond `/api/v1/company/check` and `/api/v1/leads/classify`
- Memberships, invoices, usage under token abilities

### 3.7 Per-tenant backup / restore
- Ops tooling to snapshot/restore a tenant’s CRM slice

### 3.8 True Stripe Billing Subscriptions
- Stripe Subscription objects + Customer Portal instead of (or alongside) Checkout sessions

---

## 4. Suggested build order

1. Stripe recurring + rewrite/remove legacy renewal approval  
2. Invoice PDF + basic tax; PayFast/JazzCash server notify  
3. Impersonation + soft-delete/offboard + proper suspend  
4. MRR dashboard + storage metering + cancel/upgrade  
5. Outbound webhooks, GDPR, SSO when enterprise demand appears  
6. Orphan controller cleanup anytime (low risk when unrouted)

---

## 5. Tracking

| ID | Feature | Priority | Status |
|----|---------|----------|--------|
| F-01 | Stripe/PayFast auto-renew | critical | todo |
| F-02 | Real dunning + grace access | critical | todo |
| F-03 | PayFast/JazzCash server webhooks | critical | todo |
| F-04 | Unify legacy renewal approval | critical | todo |
| F-05 | Invoice PDF | critical | todo |
| F-06 | Tax/VAT | critical | todo |
| F-07 | Suspend + offboard/delete | critical | todo |
| F-08 | Impersonation | critical | todo |
| F-09 | MRR/ARR/churn dashboard | soon | todo |
| F-10 | Plan change / cancel | soon | todo |
| F-11 | Storage metering + usage alerts | soon | todo |
| F-12 | Credit notes / void / refund | soon | todo |
| F-13 | Force owner 2FA | soon | todo |
| F-14 | Custom domain provisioning UX | soon | todo |
| F-15 | Tenant export from SA | soon | todo |
| F-16 | Tenant maintenance broadcast | soon | todo |
| F-17 | Deeper SA/billing tests | soon | todo |
| F-18 | Orphan Central cleanup | cleanup | todo |
| F-19 | Outbound tenant webhooks | later | todo |
| F-20 | SSO / SCIM | later | todo |
| F-21 | GDPR export/erasure | later | todo |
| F-22 | Public status page | later | todo |
| F-23 | Multi-currency + FX | later | todo |
| F-24 | Full tenant management API | later | todo |
| F-25 | Tenant backup/restore | later | todo |
| F-26 | Stripe Billing Subscriptions | later | todo |

Update the **Status** column to `in_progress` / `done` as items ship.

---

## 6. Admin CRM (tenant workspace) — remaining for smooth SaaS

Tenant **Admin** dashboard (`/admin/*`) is the day-to-day CRM. Super Admin runs the platform.  
For smooth SaaS, tenants should not bounce to `/tenant-profile` or email SA for routine org tasks.

**Already in Admin CRM**  
Leads, clients, orders, brands, sellers, account keys, domain scripts, client payments, client tickets, Upwork routes · Organization **Billing / Support / Referrals** (`admin.org.*`).

**Should stay Super-Admin-only**  
Pricing CRUD · platform Payment Accounts · feature/limit overrides · confirm bank/Payoneer · create announcements · referral reward/expire · webhook retry · demos/contacts · SA team.

### 6.1 Critical (Admin)

| ID | Feature | Why | Today |
|----|---------|-----|-------|
| A-01 | Renew while subscription expired | Billing blocked when needed most | Done — expired admins keep session, land on `admin.org.billing`; CRM soft-redirects to renew |
| A-02 | Subscription health on Admin dashboard | Trial/expiry/pay-due visibility | Only on tenant-profile dashboard |
| A-03 | Announcements in Admin | See SA messages inside CRM | Dismiss only on tenant-profile |
| A-04 | Plan + usage / limits meter | Avoid surprise limit walls | tenant-profile only |
| A-05 | Organization overview page | SaaS home inside Admin | tenant-profile is the overview |
| A-06 | Manage admin / finance seats | Seat limits unused in CRM | Only sellers UI; admins via `go-crm` provision |
| A-07 | API token self-serve (`api_access`) | Mint/revoke without SA | Super Admin tenant detail only |

### 6.2 Soon (Admin)

| ID | Feature | Notes |
|----|---------|-------|
| A-08 | Read-only plan / feature matrix | What’s included on current plan |
| A-09 | Org settings edit | Name, country, contact (neither portal edits today) |
| A-10 | Profile link in Admin chrome | Profile route exists; not in nav |
| A-11 | Upwork sidebar links | Routes exist; no nav |
| A-12 | Cancel / auto-renew toggle | Billing renews; no cancel UX |
| A-13 | Plan change / upgrade entry | Limits say “upgrade”; no path |
| A-14 | JazzCash checkout route | Return exists; checkout not wired into billing UI |
| A-15 | Link to tenant-profile | Escape hatch until overview moves into Admin |

### 6.3 Later (Admin)

| ID | Feature |
|----|---------|
| A-16 | Admin CRM 2FA (tenant admin users) |
| A-17 | Custom domain / white-label self-serve |
| A-18 | Own-workspace audit log (read-only) |

### 6.4 Suggested Admin build order

1. **A-01** — Allow `admin.org.billing` (+ support) when lapsed; soft renew redirect instead of hard 403  
2. **A-02–A-04** — Dashboard banner + usage/limits + announcements  
3. **A-05** — Organization overview  
4. **A-06–A-07** — Team seats + API tokens  
5. **A-08–A-15** — Settings, cancel/upgrade, Upwork nav, JazzCash checkout  

### 6.5 Tracking (Admin)

| ID | Feature | Priority | Status |
|----|---------|----------|--------|
| A-01 | Renew when subscription expired (Admin billing reachable) | critical | done |
| A-02 | Subscription health on Admin dashboard | critical | todo |
| A-03 | Announcements in Admin | critical | todo |
| A-04 | Plan + usage / limits in Admin | critical | todo |
| A-05 | Organization overview page | critical | todo |
| A-06 | Manage admin / finance seats | critical | todo |
| A-07 | API token self-serve | critical | todo |
| A-08 | Read-only plan / feature matrix | soon | todo |
| A-09 | Org settings edit | soon | todo |
| A-10 | Profile link in Admin chrome | soon | todo |
| A-11 | Upwork sidebar links | soon | todo |
| A-12 | Cancel / auto-renew toggle | soon | todo |
| A-13 | Plan change / upgrade | soon | todo |
| A-14 | JazzCash checkout in billing | soon | todo |
| A-15 | Link to tenant-profile | soon | todo |
| A-16 | Admin CRM 2FA | later | todo |
| A-17 | Custom domain self-serve | later | todo |
| A-18 | Own-workspace audit log | later | todo |
