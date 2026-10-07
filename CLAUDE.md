# CLAUDE.md — Zircon Marquee ERP

Management ERP for **Zircon Marquee**, a wedding/banquet marquee business in
Rawalpindi, Pakistan. Built by Khan Group of Technologies. The authoritative
specification lives in [`docs/zircon-spec.md`](docs/zircon-spec.md) — read it for
full detail. This file is the working digest of stack, rules and conventions.

## Stack

- Laravel 13, PHP 8.3, MySQL (db `zircon`, root / no password, local Laragon).
- Filament 5 admin panel at `/admin`; app served at `http://zircon.test`.
- Runs on Windows. Project lives at `D:\laragon\www\zircon` — **work entirely on
  the D: drive, never write to C:.**

### Packages

- `bezhansalleh/filament-shield` + `spatie/laravel-permission` — RBAC.
- `owen-it/laravel-auditing` — audit trail on financial models.
- `barryvdh/laravel-dompdf` — PDF vouchers / reports.
- `spatie/laravel-backup` — backups (added later).

### Local tooling (Laragon, not on PATH)

- PHP: `D:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe`
- Composer: `php D:\laragon\bin\composer\composer.phar`

## Git

Repo `https://github.com/chhuzaifa928/Zircon-Marquee.git`, branch `main`.
`.env` is git-ignored and must **never** be committed. Commit + push after each
module.

## Roles & Permissions

Six **spatie** roles only — there is **no `role` column** on `users`:
`super_admin`, `admin`, `sales`, `accounts`, `inventory`, `decor`.

- `Gate::before` grants `super_admin` every ability; `User::isSuperAdmin()`.
- Only `super_admin` can edit/delete entries made by `accounts` (vouchers,
  slips). `accounts` creates accounting entries but cannot alter them after.
- `super_admin` can lock the whole system (`settings.system_locked`); when
  locked only `super_admin` may log in (enforced in `canAccessPanel`).
- `admin` can change the GST rate. Each role sees only its own modules.

## Core business rules

- **Halls:** Opal + Sapphire; full marquee = both, modelled as a composite hall
  with a `hall_components` pivot. A hall booking blocks the full marquee for that
  date+slot and vice versa.
- **Availability:** no DB unique on hall+date+slot. Many `tentative` bookings may
  overlap; a new booking is blocked only when a **confirmed** (`booked`/`paid`)
  booking already holds that hall+date+slot. First confirmed booking wins.
- **Booking flow:** sales creates (`tentative`) → accounts confirms (`booked`) →
  full payment → `paid` and locked (`is_locked`, no further edits).
- **Pricing:** per-head, bill uses `discounted_rate × guests`; itemised extra
  charges; GST 16% (configurable, snapshot on booking) auto-added. Snapshot
  totals computed by a `BookingTotals` service.
- **Payments:** each slip has a gapless sequential `slip_no`, posts into a
  cash/bank account.
- **Costing:** `event_costs` are accounts-only and only after a booking is fully
  paid. Net profit = `grand_total − costs`.
- **Accounting:** double-entry via `vouchers` (debit/credit account + amount,
  morph source); account balances computed from vouchers. **Auto-posting
  debit/credit rules are NOT finalised — build structure, do not hardcode posting
  logic until defined with the owner.**

## Conventions

- Money `decimal(14,2)`; per-head rates `decimal(10,2)`. Money cast
  `decimal:2`.
- **Soft-deletes + auditing** on: `bookings`, `booking_charges`,
  `payment_slips`, `vouchers`, `accounts`, `event_costs`.
- `created_by` / `confirmed_by`: nullable FK, `nullOnDelete`.
- `phone` & `cnic`: plain searchable columns for now (encryption deferred to
  hardening).
- **Numbers:** atomic, gapless sequences via the `number_sequences` table /
  `NumberSequenceService` for `booking_no`, `slip_no`, `voucher_no` (per type
  RV/EV/PV) and customer `code`. **Never `max()+1`.**
- Enum-like columns (`status`, `slot`, `type`, …) are stored as strings; allowed
  values live in model constants.
- Match the existing Laravel 13 model idiom: `#[Fillable([...])]` /
  `#[Hidden([...])]` attributes and a `casts()` method.
- Models: declare relationships, fillable and casts. Clean, conventional
  Laravel. Migrations for all schema; seeders for roles, halls, settings and a
  starter chart of accounts.

## Build order

1. **Foundation** — spec files, git, RBAC + 6 roles, all migrations + models,
   migrate, seed. _(current)_
2. Customers + Bookings (GST, booking sheet PDF, availability, calendar).
3. Payments + slips + advance receipt.
4. Accounting (chart of accounts, vouchers, GL; posting rules defined first).
5. Reports (cash closing, supplier summary, GL, P&L, bookings master).
6. Inventory + stock movements.
7. Assets, decor, vendors.
8. Super-admin controls + system lock, dashboards, theming, deploy, testing.

Build module by module; **show a short plan and wait for approval before each
module**, then commit + push when green.
