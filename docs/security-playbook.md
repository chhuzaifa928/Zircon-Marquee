# Zircon Marquee ERP — Security Playbook

Security posture for the Zircon Marquee ERP (SRS §17). Covers what is enforced
in code today and the hardening steps for deployment.

## Implemented in the application

### Authentication & access
- **Role-based access** (spatie roles, 6 roles). Each role sees only its modules;
  the matrix lives in `User::` role-group constants and is covered by
  `RbacMatrixTest`. `super_admin` bypasses all checks via `Gate::before`.
- **Accounts entries immutable to authors** — vouchers, slips, accounts, event
  costs are editable/deletable only by `super_admin` (SRS §9.5).
- **System lock** (`settings.system_locked`) enforced in `User::canAccessPanel`:
  while locked only `super_admin` can log in; inactive users are always denied.
- **User management** (`UserResource`, admin/super): a user cannot delete or
  deactivate their own account, only a Super Admin may edit/delete another Super
  Admin, and the last Super Admin can never be removed. Only a Super Admin can
  grant the `super_admin` role.
- **Login throttling** — Filament's built-in rate limiting on the login form.
- **Strong passwords** — `Password::defaults()` requires ≥12 chars with mixed
  case, numbers and symbols; in production also checks Have-I-Been-Pwned
  (`uncompromised()`). Passwords are hashed (`hashed` cast) and never mass-assigned.

### Auditing & integrity
- **Audit trail** (owen-it/laravel-auditing) on all financial models and on
  `User` (password/remember_token excluded from audits).
- **Soft deletes** on financial records — history is never hard-erased.
- **Authentication events** (login, logout, failed, lockout) are logged with IP.
- **Snapshot totals & gapless numbering** prevent retroactive tampering.

### Transport & headers
- **HTTPS forced** in production (`URL::forceScheme('https')`).
- **Security headers** (`SecurityHeaders` middleware): `X-Content-Type-Options`,
  `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`,
  `X-Permitted-Cross-Domain-Policies`, and `Strict-Transport-Security` over HTTPS.
- **CSRF** on all web forms, **AuthenticateSession** on the panel.

## Deployment hardening (Hostinger)

1. **TLS/SSL** — enable the certificate; confirm HSTS is served (header above).
2. **`.env` secrecy** — never commit it (git-ignored); set `APP_DEBUG=false`,
   `APP_ENV=production`; rotate `APP_KEY` only with a re-encryption plan.
3. **Least-privilege DB user** — a dedicated MySQL user limited to the `zircon`
   schema with only DML/DDL it needs; not `root`.
4. **File permissions** — web user owns `storage/` and `bootstrap/cache/` only;
   code read-only.
5. **Backups** — `spatie/laravel-backup` scheduled daily, off-site copy, with a
   documented restore procedure (set up in the deploy pass).
6. **Dependencies** — `composer audit` in CI; keep Laravel/Filament patched.
7. **Caches** — `config:cache`, `route:cache`, `view:cache` on deploy.

## Deferred / optional (next hardening pass)

- **CNIC & phone encryption** — currently plain, searchable columns (SRS/CLAUDE
  decision). Encrypting breaks `LIKE` search; options: encrypt at rest with a
  separate blind-index column for search, or DB-level encryption. Plan before
  enabling.
- **Two-factor authentication** — Filament supports app/email MFA; enable with
  the required user columns once the owner opts in.
- **Content-Security-Policy** — a strict CSP needs Filament's inline
  scripts/styles allow-listed (nonces); do in a dedicated pass to avoid breakage.
- **Per-permission RBAC via filament-shield** — a granular permissions UI; the
  current role-group guards already satisfy the requirement.
