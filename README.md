# MaidTrack

A housemaid agency records &amp; trust platform: agencies keep verified
housemaid records, clients check history and leave feedback before hiring,
and an admin layer keeps the whole marketplace honest — self-registered
agencies and every housemaid they submit are approved by Admin before going
live.

Built with PHP + MySQL (PDO), no framework, no build step — runs on plain
XAMPP for development and any standard cPanel host for production. Android
comes later, as a wrapper around this same backend.

Full scope, role breakdown, and design decisions live in the platform
proposal shared with the project owner — this README covers what's
actually built and how to run it.

## Status: Phase 3 — Reporting

Working now:
- Full database schema (`database/schema.sql`) — agencies, admins, clients,
  housemaids, documents, skills/languages/work-country join tables,
  employment history, bookings, reviews, incidents (with a
  Reported → Under Review → Verified status workflow), and an audit log.
- Starter master data (`database/seed_master_data.sql`) — common FDW source
  countries, skill tags, and languages, editable from the Admin panel.
- One login (`login.php`), role-based redirect. Agencies self-register
  (`agency/register.php`, with a license upload) and sit **Pending** until
  Admin approves them.
- **Admin approval dashboard** — two queues: Agency Registrations, and
  Housemaid Submissions grouped by agency with drill-down into full
  profile/document review. Both support checkbox multi-select with bulk
  approve/reject; every rejection requires a reason the agency can see.
- **Agency roster management** — housemaids submitted through a five-step
  intake wizard (identity, documents with per-type file uploads, address,
  skills/languages/consent, review) and sit Pending until Admin approves.
  Public agency profile editor.
- Admin master-data CRUD (skills/languages/countries), an audit log of
  every approval/rejection/edit, and passport/ID masking outside the
  owning agency/Admin view.
- Custom pastel-toned CSS design system (`assets/css/app.css`) — every
  text-role colour checked against WCAG AA 4.5:1 contrast. See the
  "clean pastel" note in the proposal's interface-direction section.
- **Dark mode** — a toggle button in the nav (every page, including
  login/register) flips between light and dark; the choice persists
  across visits and applies before first paint (no flash of the wrong
  theme). Defaults to the OS setting until you pick one explicitly.
- **PWA groundwork**, done early to make Phase 6 (Android) cheap:
  `manifest.json`, a conservative service worker (`sw.js` — caches only
  the static shell, never touches session pages or POSTs), a static
  offline fallback page, and real app icons. See "Installing as an app"
  below.
- **Client portal** — self-register at `client/register.php`, then verify
  with a 6-digit code (both channels at once — see "OTP in dev mode"
  below) before browsing unlocks at all. Browse with filters (skill,
  nationality, min. experience, availability), request a booking, and
  leave a four-category review once the agency marks it completed.
  Passport is masked to last-4 until a client has an accepted/completed
  booking with that specific housemaid, same for the agency's aggregate
  rating/roster shown on its public profile.
- **Booking lifecycle** (`agency/bookings.php`) — client requests →
  agency accepts/declines → agency marks completed, or either side
  cancels. Accepting flips the housemaid to Placed; completing or
  cancelling flips her back to Available automatically. One review per
  completed booking; the housemaid's aggregate rating recalculates the
  moment a review lands, and the agency can post one response per review
  from her profile page.
- **Reports**, all print-styled HTML ("Print / Save as PDF" button) plus
  CSV where it's a list — no PDF library, same approach as Car
  Maintenance Tracker. Client: due-diligence report per candidate
  (rating breakdown, always-redacted passport/address), booking history.
  Agency (`agency/reports.php`): roster & compliance (passport/work-permit
  expiry, colour-coded), performance (placements chart, rating, repeat-
  client rate). Admin: platform overview (counts by status, 6-month
  growth chart). Charts use Chart.js from a CDN — the only external
  script this project loads; everything else is self-contained.

Not built yet (next phases):
- **Phase 4** — Security hardening (passport masking, consent capture,
  upload/auth pass).
- **Phase 5** — cPanel deploy.
- **Phase 6** — Android: the PWA is already installable (see below); a
  WebView-wrapped APK is the next step once the site is live.

## Installation (XAMPP)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g.
   `C:\xampp\htdocs\MaidTrackingApps` (or symlink it there from wherever you
   keep it).
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`),
   then either:
   - Use the "Import" tab to import, **in this order**: `database/schema.sql`,
     `database/seed_master_data.sql`, `database/seed_admin.sql`, then
     `database/migrate_phase2.sql` (adds `bookings.notes`, added after
     the original schema was scoped — every phase that needs a schema
     change ships its own `migrate_phaseN.sql`, run in phase order), **or**
   - Run from a terminal:
     ```bash
     mysql -u root < database/schema.sql
     mysql -u root maidtrack < database/seed_master_data.sql
     mysql -u root maidtrack < database/seed_admin.sql
     mysql -u root maidtrack < database/migrate_phase2.sql
     ```
4. **Set up config.** Copy `config/database.example.php` to
   `config/database.php` (gitignored — never commit real credentials).
   Defaults match a stock XAMPP install: host `localhost`, user `root`, no
   password. Edit that file if your MySQL setup differs.
5. **Open the app**: `http://localhost/MaidTrackingApps/` — you'll land on
   the login page. Admin login credentials are in `CREDENTIALS.md`
   (gitignored, local only — not in this repo). Register a test agency at
   `/agency/register.php` and approve it as Admin, then a test client at
   `/client/register.php` and verify it (see "OTP in dev mode" below)
   before either can do anything else.

## OTP in dev mode

There's no real email/SMS gateway wired up yet, so `client/verify.php`
displays the 6-digit code directly on the page, clearly labeled "Dev
mode." A real sender (SES, Twilio, whatever gets chosen) only has to
replace what happens with the code `ClientOtp::issue()` returns — the
verification logic itself doesn't change.

## Installing as an app (PWA)

Once served over `http://localhost/...` (or HTTPS in production), the
site is installable:
- **Android Chrome**: menu → "Install app" / "Add to Home screen".
- **Desktop Chrome/Edge**: an install icon appears in the address bar.
- **iOS Safari**: Share → "Add to Home Screen" (uses the Apple
  touch-icon meta tag rather than the manifest, since iOS doesn't
  support web app manifests the same way).

If icons ever need regenerating (e.g. the primary colour changes),
`scripts/generate_icons.php` redraws them with PHP's GD extension,
which isn't enabled by default in a stock XAMPP install — run it with
`-d extension=php_gd.dll` rather than editing `php.ini`:
```bash
php -d extension=php_gd.dll scripts/generate_icons.php
```

## Deploying to production (cPanel)

Covered in full once Phase 5 is reached, but in short: same schema import
via phpMyAdmin on the host, same `config/database.php` pattern with the
host's DB credentials, uploads folder kept outside direct URL access, and
HTTPS via the host's free Let's Encrypt option. Hosting should stay
Malaysia-based per the PDPA cross-border data consideration in the
proposal.

## Project structure

```
/config      app + database configuration (config/database.php is gitignored)
/database    schema.sql, seed_master_data.sql, seed_admin.sql, migrate_phaseN.sql
/includes    bootstrap.php (require chain), auth.php, functions.php, header/footer.php
/models      PDO data-access classes — Admin, Agency, Housemaid, HousemaidDocument, MasterData,
             AuditLog, Client, ClientOtp, Booking, Review
/admin       Admin portal — dashboard, approval queues, master data, audit log, platform report
/agency      Agency portal — register, dashboard, roster, housemaid intake wizard, profile, bookings,
             reports (roster & compliance, performance)
/client      Client portal — register, OTP verify, dashboard, browse, candidate/agency profiles,
             booking request, booking history, reviews, due-diligence & booking reports
/assets      css/app.css (pastel design system + dark mode), js/app.js (bulk-select, theme toggle, SW registration), icons/ (PWA icons)
/uploads     housemaid/agency documents & photos (gitignored, never committed)
/scripts     one-off dev tools (generate_icons.php)
manifest.json, sw.js, offline.html   PWA install + offline shell (see "Installing as an app")
```

## Data handling

Passport numbers and other sensitive documents are masked to the last 4
characters everywhere except the owning agency, Admin, or a client with a
confirmed booking (`mask_document_number()` in `includes/functions.php`).
Incident reports (e.g. "ran away") follow a
Reported → Under Review → Verified workflow before ever appearing on a
public profile — see the `incidents.status` column and the "Handle with
care" note in the proposal. This matters both for basic fairness to the
housemaids on the platform and because Malaysia's PDPA 2010 applies.
