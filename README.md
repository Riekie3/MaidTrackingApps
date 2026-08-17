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

## Status: Phase 5 — Live on Cloudflare Tunnel, cPanel deploy on hold

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
- **Incident workflow** — agencies (own housemaids) and clients (only
  for a housemaid they've actually had a booking with) can file a
  report; Admin (`admin/incidents.php`) moves it Reported → Under
  Review → Verified/Dismissed. Only Verified ever shows on a public
  profile or the due-diligence report.
- **Protected document access** — every uploaded file (license,
  housemaid documents, incident evidence) is served through
  `download.php`, which checks role + ownership first; `uploads/`
  itself is also locked down via `.htaccess` for defense-in-depth on
  real Apache hosting (see "Data handling" below for why the real
  enforcement is in PHP, not `.htaccess`).
- **Encryption at rest** — passport and national ID numbers are
  AES-256-GCM encrypted before every write, decrypted on every read.
  Key lives in `config/secrets.php` (gitignored, same pattern as
  `database.php`).
- **PDPA consent** at client registration (housemaid intake already had
  this from Phase 1) and **login rate-limiting** — 5 failed attempts
  locks an account for 15 minutes, even against the correct password.

- **Live via Cloudflare Tunnel** — the XAMPP install below now runs
  under real Apache (not `php -S`), reachable publicly through a
  `cloudflared` Quick Tunnel. See "Going live (Cloudflare Tunnel)"
  below. **cPanel hosting is intentionally on hold** — Phase 5 was
  redirected to a Cloudflare-fronted local server on the project
  owner's instruction, until they say otherwise.
- Fixed along the way: link generation now trusts `X-Forwarded-Proto`
  (Apache can't see `$_SERVER['HTTPS']` when Cloudflare terminates TLS
  at its edge and forwards plain HTTP) — every generated link was
  coming out `http://` on an `https://` page before this.
- **Full demo dataset** (`scripts/seed_demo_data.php`) — see step 5
  below. Housemaid photos now render on Browse and the candidate
  profile — AI-generated faces of people who don't exist, not real
  photos (this demo attaches invented incident reports to these
  profiles, so no real person's likeness is used) — with computed age
  shown alongside. On Browse, each photo is now a small 64px circular
  thumbnail in a compact list-style card rather than a full-width
  square.
- **Dark mode is opt-in, not automatic** — no longer follows the OS/
  browser preference; light is always the default until the header
  toggle is clicked, on the project owner's instruction.

Not built yet (next phases):
- **Phase 5, remainder** — actual cPanel hosting, on hold until the
  project owner says go.
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
     `database/seed_master_data.sql`, `database/seed_admin.sql`,
     `database/migrate_phase2.sql`, `database/migrate_phase4.sql`
     (every phase that needs a schema change ships its own
     `migrate_phaseN.sql`, run in phase order — there's no `migrate_phase3.sql`,
     Phase 3 didn't need one), **or**
   - Run from a terminal:
     ```bash
     mysql -u root < database/schema.sql
     mysql -u root maidtrack < database/seed_master_data.sql
     mysql -u root maidtrack < database/seed_admin.sql
     mysql -u root maidtrack < database/migrate_phase2.sql
     mysql -u root maidtrack < database/migrate_phase4.sql
     ```
4. **Set up config.** Copy both example files:
   - `config/database.example.php` → `config/database.php`. Defaults match
     a stock XAMPP install: host `localhost`, user `root`, no password.
     Edit if your MySQL setup differs.
   - `config/secrets.example.php` → `config/secrets.php`, then generate a
     real encryption key and paste it in:
     ```bash
     php -r "echo bin2hex(random_bytes(32));"
     ```
   Both files are gitignored — never commit real credentials or keys.
5. **Seed a full demo dataset** (optional but recommended for exploring
   the app — otherwise you're starting from an empty roster):
   ```bash
   php scripts/seed_demo_data.php
   ```
   Creates 4 agencies, 8 housemaids (every approval/availability state),
   4 clients, 6 bookings, 2 reviews, and all 4 incident-workflow states —
   run through the real models (not raw SQL), so passport encryption and
   password hashing both work regardless of your local
   `config/secrets.php` key. Full login list in `CREDENTIALS.md` and the
   Phase Plan document's "Live Demo Credentials" section.
6. **Open the app**: `http://localhost/MaidTrackingApps/` — you'll land
   on the login page. Admin credentials are in `CREDENTIALS.md`
   (gitignored, local only — not in this repo). Without the demo seed,
   register a test agency at `/agency/register.php` and approve it as
   Admin, then a test client at `/client/register.php` and verify it
   (see "OTP in dev mode" below) before either can do anything else.

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

## Going live (Cloudflare Tunnel)

Instead of cPanel hosting (on hold — see Status above), the local XAMPP
install is exposed publicly through a Cloudflare Tunnel: Cloudflare
terminates HTTPS at its edge and proxies to Apache on this machine, no
port-forwarding or hosting account needed.

1. **Run under real Apache, not `php -S`.** `.htaccess` (the uploads
   lockdown) only takes effect under Apache — confirmed by testing: a
   direct request to `/uploads/agencies/` returns `403` under Apache,
   but PHP's built-in dev server ignores `.htaccess` entirely and would
   serve it. The project is linked into XAMPP's `htdocs` via a directory
   junction (`C:\xampp\htdocs\MaidTrackingApps` → the repo folder), so
   there's one source of truth rather than a copy that can drift.
2. **Start Apache and MySQL** from the XAMPP Control Panel (or
   `httpd.exe` / `mysqld.exe` directly).
3. **Start a tunnel.** [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/)
   must be installed. Two options:
   - **Quick Tunnel** (what's running now) — no Cloudflare account
     needed, gives an instant `*.trycloudflare.com` HTTPS URL:
     ```bash
     cloudflared tunnel --url http://localhost/MaidTrackingApps
     ```
     The URL is random and changes every time the tunnel restarts —
     fine for a quick look, not for anything you'd want to bookmark or
     share long-term.
   - **Named Tunnel** (persistent, your own domain) — needs your own
     Cloudflare account and a domain added to it. Run
     `cloudflared tunnel login` (opens a browser for *you* to
     authenticate with *your* Cloudflare account — nothing about your
     login is ever seen by anyone else), then `cloudflared tunnel create
     maidtrack` and a DNS route. Ask if you want this set up once you've
     got a domain ready.
4. **Heads up on exposing test data publicly.** The seeded admin
   account (`CREDENTIALS.md`) is reachable from anywhere while a tunnel
   is up. Fine for a private demo link you're not sharing widely; worth
   rotating that password before leaving a tunnel running long-term or
   handing the URL to anyone outside this conversation.

## Deploying to production (cPanel) — on hold

Held per instruction until you say go. When it's time: same schema
import via phpMyAdmin on the host, same `config/database.php` /
`config/secrets.php` pattern with the host's real values, uploads folder
kept outside direct URL access (same `.htaccess` approach, already
tested and working), HTTPS via the host's free Let's Encrypt option.
Hosting should stay Malaysia-based per the PDPA cross-border data
consideration in the proposal.

## Backups

```bash
scripts/backup_db.sh
```
Dumps to `database/backups/maidtrack_<timestamp>.sql` (gitignored — real
data never gets committed). Restore with:
```bash
mysql -u root maidtrack < database/backups/<file>.sql
```
Tested both directions: backed up the live dev database, restored it
into a throwaway database, and confirmed row counts matched.

## Project structure

```
/config      app config (database.php and secrets.php are both gitignored)
/database    schema.sql, seed_master_data.sql, seed_admin.sql, migrate_phaseN.sql,
             demo_assets/avatars/ (versioned demo photos), backups/ (gitignored)
/includes    bootstrap.php (require chain), auth.php (also login rate-limiting), functions.php
             (also encryption + protected-file streaming), header/footer.php
/models      PDO data-access classes — Admin, Agency, Housemaid, HousemaidDocument, MasterData,
             AuditLog, Client, ClientOtp, Booking, Review, Incident
/admin       Admin portal — dashboard, approval queues, master data, audit log, incidents, platform report
/agency      Agency portal — register, dashboard, roster, housemaid intake wizard, profile, bookings,
             incident reports, reports (roster & compliance, performance)
/client      Client portal — register, OTP verify, dashboard, browse, candidate/agency profiles,
             booking request, booking history, reviews, incident reports, due-diligence & booking reports
/assets      css/app.css (pastel design system + dark mode), js/app.js (bulk-select, theme toggle, SW registration), icons/ (PWA icons)
/uploads     housemaid/agency/incident files (gitignored; .htaccess denies direct access) — never
             linked directly, always served through download.php
/scripts     dev/ops tools (generate_icons.php, backup_db.sh, seed_demo_data.php)
download.php, manifest.json, sw.js, offline.html   protected file gateway + PWA shell
```

## Data handling

- **Masking.** Passport/national ID numbers are masked to the last 4
  characters everywhere except the owning agency, Admin, or a client with
  a confirmed booking (`mask_document_number()` in `functions.php`).
- **Encryption.** Those same two fields are also encrypted at rest
  (AES-256-GCM, `encrypt_field()`/`decrypt_field()`, key in
  `config/secrets.php`) — masking controls what's *shown*, encryption
  protects what's *stored*, and both apply independently.
- **File access.** Every uploaded document goes through `download.php`,
  which checks the requester's role and ownership before streaming
  anything; `uploads/.htaccess` denies direct access as a second layer
  on real Apache hosting. Note: PHP's built-in dev server (`php -S`,
  used for local testing) doesn't read `.htaccess` at all — the actual
  access control is `download.php`'s own PHP-level check, which works
  identically on every server, `.htaccess` or not.
- **Incidents.** Reports (e.g. "ran away") follow a
  Reported → Under Review → Verified/Dismissed workflow
  (`admin/incidents.php`) before ever appearing on a public profile or
  the due-diligence report — see the `incidents.status` column and the
  "Handle with care" note in the proposal. Only Admin can verify one;
  only Verified ones are ever shown to a client.
- **Consent.** Captured (timestamped) at both housemaid submission and
  client registration.
- **Login protection.** 5 failed attempts against one email locks it out
  for 15 minutes (`login_attempts` table), regardless of which device or
  IP the attempts came from.

All of this matters both for basic fairness to the housemaids on the
platform and because Malaysia's PDPA 2010 applies.
