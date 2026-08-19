# Ledrix SaaS — Remaining Features

Product backlog for gaps across **Super Admin**, **Admin (org)**, **Seller**, and **Client**.  
Ops deploy steps: [`PRODUCTION.md`](PRODUCTION.md).

**Status key**

| Tag | Meaning |
|-----|---------|
| `critical` | Before (or at) production scale |
| `soon` | Shortly after launch |
| `later` | Enterprise / nice-to-have |
| `cleanup` | Dead code / path consolidation |

When an item ships, **remove its row** from this file (do not keep `done` rows here).

---

## Already shipped (not in backlog)

**Platform / central**  
Auth/roles/2FA/invites · tenants + limits/features · Payment Accounts (Stripe, PayFast, Meezan, JazzCash, Payoneer) · manual payment confirm (incl. Meezan bank transfer) · trial/subscription schedulers · renewal reminder emails (7d / 3d / 1d) · invoice HTML + due/paid emails · Stripe platform webhook + Webhook Events UI · API tokens + `/api/v1` · support/demos/referrals/announcements/audit · dual Admin/Tenant billing · JazzCash token auto-renew · cancel-at-period-end UX.

**Admin CRM org portal (A-01–A-18 — complete)**  
Expired renew path · dashboard health/usage/announcements · overview · team seats · API tokens · plan matrix · org settings · profile/Upwork nav · billing cancel/auto-renew (JazzCash) · JazzCash checkout · tenant-portal links · Admin 2FA (optional) · custom domain/white-label self-serve · workspace audit log.

**Seller CRM (core)**  
Leads · assignments · orders / renewals · clients · briefs hub · brands · payments · tickets · payment links · finish lead · feature-gated CTAs.

**Client portal (core)**  
Invoices / pay CTA · briefs (catalogued services) · tickets · profile · portal access gating.

**Front / marketing**  
Pricing · trial/demo LPs · Meta Pixel + domain verification · Terms · Privacy.

**Stay Super-Admin-only**  
Pricing CRUD · platform Payment Accounts · feature/limit overrides · confirm bank/Payoneer · create announcements · referral reward/expire · webhook retry · demos/contacts · SA team.

---

## Remaining features

### Critical

| ID | Feature | Priority | Status | Notes |
|----|---------|----------|--------|-------|
| F-01 | Stripe / PayFast auto-renew | critical | todo | JazzCash token renew exists; Stripe/PayFast still one-shot checkout |
| F-02 | Real dunning + grace CRM access | critical | todo | Reminder/expired emails exist; no retry ladder / update-card / `past_due` CRM grace |
| F-03 | PayFast / JazzCash server webhooks | critical | todo | Browser return only; need ITN/notify → `platform_webhook_events` |
| F-04 | Unify legacy renewal approval | critical | todo | Remove/rewrite `StripeController` send/approve onto invoice + activate |
| F-05 | Invoice PDF download | critical | todo | DomPDF unused; store `tenant_invoices.pdf_path` (SaaS invoices; Client still uses browser html2pdf) |
| F-06 | Tax / VAT on invoices | critical | todo | `tax_amount` always `0` today |
| F-07 | Suspend + offboard / delete | critical | todo | Status toggle only; use suspend metadata + soft-delete/offboard |
| F-08 | Super Admin impersonation | critical | todo | Login-as-tenant + audit trail |

### Soon

| ID | Feature | Priority | Status | Notes |
|----|---------|----------|--------|-------|
| F-09 | MRR / ARR / churn dashboard | soon | todo | SA dashboard is mostly counts |
| F-10 | Self-serve plan change / proration | soon | todo | Admin has upgrade CTAs → pricing/support; need in-app upgrade/downgrade + proration |
| F-11 | Storage metering + usage alerts | soon | todo | `max_storage_mb` usage always `0`; add 80%/100% emails |
| F-12 | Credit notes / void / refund | soon | todo | Void issued; refund paid; credit note doc |
| F-13 | Force owner 2FA | soon | todo | SA + Admin 2FA optional today; enforce for owner in production |
| F-14 | Custom domain SA provisioning UX | soon | todo | Tenant/Admin self-serve domain done; SA set/verify UX still missing |
| F-16 | Tenant maintenance broadcast | soon | todo | Distinct from `artisan down` / section flags |
| F-17 | Deeper SA / billing automated tests | soon | todo | Activate E2E, webhooks, dunning, dual-org billing |
| F-18 | Orphan / dead-code cleanup | cleanup | todo | Unused Central controllers/views; seller RoughController leftovers; half-dead Sanctum client API |
| F-27 | Client ↔ Seller messaging | soon | todo | Client Messages route is “coming soon”; nav commented; no seller inbox |
| F-28 | Seller + Client portal 2FA | soon | todo | Admin/SA 2FA exist; seller and client auth are password-only |

### Later / enterprise

| ID | Feature | Priority | Status | Notes |
|----|---------|----------|--------|-------|
| F-19 | Outbound tenant webhooks | later | todo | `invoice.paid`, etc. — not CRM capture `feature_webhooks` |
| F-20 | SSO / SAML / OIDC / SCIM | later | todo | Enterprise IdP for SA and/or tenant admins |
| F-21 | GDPR export / erasure | later | todo | DSAR + purge/anonymize primary tenant data |
| F-22 | Public status / SLA page | later | todo | Incidents, uptime, subscribe |
| F-23 | Multi-currency + FX | later | todo | Beyond USD/PKR regional split |
| F-24 | Full tenant management API | later | todo | Beyond `/api/v1/company/check` + leads classify |
| F-25 | Per-tenant backup / restore | later | todo | Ops snapshot of CRM slice |
| F-26 | True Stripe Billing Subscriptions | later | todo | Stripe Subscriptions + Customer Portal |

---

## Suggested build order

1. **F-01** + **F-04** — Stripe recurring; remove legacy renewal  
2. **F-05** + **F-06** + **F-03** — Invoice PDF/tax; PayFast/JazzCash server notify  
3. **F-07** + **F-08** — Suspend/offboard; impersonation  
4. **F-09** + **F-10** + **F-11** — Analytics; plan change; storage metering  
5. **F-27** + **F-28** — Client messaging; seller/client 2FA (portal completeness)  
6. **F-19+** — Enterprise when demand appears  
7. **F-18** — Orphan cleanup anytime  
