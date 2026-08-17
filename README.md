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

## Status: Phase 0 — repository &amp; database scaffold

Working now:
- Full database schema (`database/schema.sql`) — agencies, admins, clients,
  housemaids, documents, skills/languages/work-country join tables,
  employment history, bookings, reviews, incidents (with a
  Reported → Under Review → Verified status workflow), and an audit log.
- Starter master data (`database/seed_master_data.sql`) — common FDW source
  countries, skill tags, and languages, editable later from the Admin panel.
- Config scaffold and a setup-status landing page (`index.php`) that checks
  the database connection and table count.

Not built yet (next phases):
- **Phase 1** — Admin + Agency portals: agency self-registration, the
  two-queue Admin approval dashboard (agencies + housemaids, with bulk
  approve/reject), housemaid CRUD, document upload.
- **Phase 2** — Client portal: registration/verification gate, browse &amp;
  filter, booking requests, post-booking reviews.
- **Phase 3** — Reports (due-diligence PDF, roster/compliance, agency
  performance, admin overview).
- **Phase 4** — Security hardening (passport masking, consent capture,
  upload/auth pass).
- **Phase 5** — cPanel deploy.
- **Phase 6** — Android (PWA first, then a WebView-wrapped APK).

## Installation (XAMPP)

1. **Copy the project** into your XAMPP `htdocs` folder, e.g.
   `C:\xampp\htdocs\MaidTrackingApps` (or symlink it there from wherever you
   keep it).
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database.** Open phpMyAdmin (`http://localhost/phpmyadmin`),
   then either:
   - Use the "Import" tab to import `database/schema.sql`, then
     `database/seed_master_data.sql`, **or**
   - Run from a terminal:
     ```bash
     mysql -u root < database/schema.sql
     mysql -u root maidtrack < database/seed_master_data.sql
     ```
4. **Set up config.** Copy `config/database.example.php` to
   `config/database.php` (gitignored — never commit real credentials).
   Defaults match a stock XAMPP install: host `localhost`, user `root`, no
   password. Edit that file if your MySQL setup differs.
5. **Open the app**: `http://localhost/MaidTrackingApps/` — you should see a
   setup-status page confirming the database connection and table count.
   Login portals for Admin/Agency/Client land in Phase 1.

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
/database    schema.sql (full schema) and seed_master_data.sql (starter lists)
/includes    shared helpers (functions.php grows as pages are added)
/models      PDO data-access classes — added in Phase 1
/admin       Admin portal — added in Phase 1
/agency      Agency portal — added in Phase 1
/client      Client portal — added in Phase 2
/assets      css/js — added as the UI is built
/uploads     housemaid/agency documents & photos (gitignored, never committed)
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
