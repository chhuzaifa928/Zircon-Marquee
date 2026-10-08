# Zircon Marquee ERP — Master Project Spec

> This is the authoritative specification for the Zircon Marquee ERP.
> `CLAUDE.md` distils the stack, rules and conventions from this document.

## Context

Building a complete management ERP for **Zircon Marquee**, a wedding/banquet
marquee business in **Rawalpindi, Pakistan**. Built by **Khan Group of
Technologies**. Build module by module; before each module show a short plan and
wait for approval; commit + push after each module.

## Current State (done — do not redo)

Laravel 13 + Filament 5 + MySQL at `D:\laragon\www\zircon`, running at
`http://zircon.test`, Filament panel at `/admin`, an admin user exists, MySQL db
`zircon` in `.env` (root, no password, Laragon), PHP 8.3 on Windows.

## Environment

Work entirely on the D: drive inside this project. Never write to C:. Git repo:
`https://github.com/chhuzaifa928/Zircon-Marquee.git` (`main`). Keep `.env`
uncommitted.

## Stack & Packages

Laravel 13, Filament 5, MySQL.

- `bezhansalleh/filament-shield` + `spatie/laravel-permission` (RBAC)
- `owen-it/laravel-auditing` (financial models)
- `barryvdh/laravel-dompdf` (PDF vouchers/reports)
- `spatie/laravel-backup` (later)

UI themed gold/charcoal (Zircon branding) — functionality first, polish later.

## Roles (6) — spatie roles only, no role column

`Gate::before` lets `super_admin` bypass all checks; add `User::isSuperAdmin()`.

- `super_admin` (you / Khan Group)
- `admin` (owner)
- `sales` (×4)
- `accounts` (×2)
- `inventory` (×2)
- `decor` (×2)

### Permission rules

- Only `super_admin` can edit/delete entries made by accounts (vouchers, slips).
- `accounts` can create accounting entries but not alter them afterwards.
- `super_admin` can lock the whole system (`settings.system_locked`); when
  locked, only `super_admin` can log in (enforce in `canAccessPanel`).
- `admin` can change the GST rate. Each role sees only its own modules.

## Halls & Availability

Opal and Sapphire only, booked fully independently. There is **no** composite
"Full Marquee" — a booking in one hall never blocks the other. _(Owner change
2026-10-09: the composite hall, `hall_components` pivot and `is_composite` flag
were removed.)_

**NO DB unique on hall+date+slot:** multiple TENTATIVE bookings may overlap a
slot; block a new booking only when a CONFIRMED (`booked`/`paid`) booking already
holds that same hall+date+slot (no cross-hall blocking). First confirmed booking
wins.

## Bookings & Billing

Fields: `booking_no` (seq), customer, hall, `event_date`, `booking_date`, `slot`
enum (lunch/dinner) + `start_time`/`end_time`, `event_type`, `guests`,
`rack_rate`, `discounted_rate`, `status` (tentative/booked/paid/cancelled),
`is_locked`, `gst_rate` (snapshot), `remarks`, `created_by` (salesperson),
`confirmed_by`, `confirmed_at`.

Itemised extra charges (`booking_charges`): cold_drinks, mineral_water,
ac_heating, hall_charges, decor, dj, other_charges.

**Pricing:** per-head; bill uses `discounted_rate × guests`. GST 16%
(configurable) auto-added. Store snapshot totals (`headcharge_total`,
`charges_total`, `subtotal`, `gst_amount`, `grand_total`, `advance_total`,
`due`) via a `BookingTotals` service.

**Flow:** sales creates (tentative) → accounts confirms (booked) → full payment
→ paid and locked (no further edits).

## Payments

`payment_slips`: `slip_no` (unique sequential, gapless), booking, account
received into, date, amount, method, reference, remark, `created_by`,
`voucher_id` (nullable). Advance receipt lists all a host's slips with total.

## Costing

`event_costs`: accounts-only, and only after the booking is fully paid. vendor +
inventory + misc. Net profit = `grand_total − costs`.

## Accounting (double-entry)

`accounts` (chart of accounts): code, name, type
(cash/bank/staff/income/expense/supplier), opening_balance. Balance computed
from vouchers.

`vouchers`: `voucher_no` (seq, prefixed by type), type (RV receipt / EV expense /
PV payment), date, category, remark, `debit_account_id`, `credit_account_id`,
amount, morph source (booking/slip/event_cost), `created_by`.

General Ledger per account with running balance. Booking charges → income;
payment slips → cash/bank; expenses → expense/supplier.

**POSTING RULES ARE NOT FINAL** — build the structure, but do NOT hardcode
auto-posting debit/credit logic yet; we define it together before wiring.

## Other Modules

- `inventory_items` (code, name, category, unit of measure, qty_on_hand,
  reorder_level, unit_cost)
- `stock_movements` (in/out/adjustment, qty, unit_cost, booking, date)
- `assets` (code, name, category, qty, purchase info, condition, status)
- `decor_items` (code, name, qty, rate, status)
- `vendors` (name, company, phone, email, address, linked supplier account,
  opening_balance)

## Reports

All ask From–To date range, print to PDF; + month-closing, + year-closing.

Event Booking Sheet, Kitchen Voucher, Final Invoice, Advance Receipt, Cash
Closing Sheet (daily/monthly/yearly), General Ledger (per account), Supplier
Summary, Bookings Master Report (host, hall, event, guests, total, payments,
balance, expense, net profit + grand totals), Profit & Loss Statement. Enrich
beyond the paper samples with useful columns/subtotals.

## Numbers

Atomic, gapless sequence generator (`number_sequences` table or DB-locked
counter) for `booking_no`, `slip_no`, `voucher_no` (per-type RV/EV/PV), customer
code. **Not `max()+1`.**

## Conventions

- Money `decimal(14,2)`; per-head `decimal(10,2)`.
- Soft-deletes + auditing on `bookings`, `booking_charges`, `payment_slips`,
  `vouchers`, `accounts`, `event_costs`.
- `created_by`/`confirmed_by` nullable FK `nullOnDelete`.
- `phone` & `cnic` are plain searchable columns for now (encryption deferred to
  hardening).
- Clean, conventional Laravel; migrations for all schema; seeders for roles,
  halls, settings, a starter chart of accounts.

## Build Order

1. **Foundation:** spec files, git, filament-shield + 6 roles, ALL migrations +
   models, migrate, seed roles/halls/settings. _(current task)_
2. Customers + Bookings (GST, booking sheet PDF, availability, calendar).
3. Payments + slips + advance receipt.
4. Accounting: chart of accounts, vouchers, general ledgers (+ posting rules
   defined with the owner first).
5. Reports: cash closing (daily/monthly/yearly), supplier summary, GL, P&L,
   bookings master.
6. Inventory + stock movements.
7. Assets, decor, vendors.
8. Super-admin controls + system lock, dashboards, theming, Hostinger deploy,
   testing, training.
