<?php
// Application-wide constants. Safe to tweak per environment.

// Derived from the request so this works unchanged whether it's served
// from XAMPP's htdocs (e.g. /MaidTrackingApps), a bare vhost, or a
// production domain — no hardcoded path to break when the environment
// changes.
//
// X-Forwarded-Proto matters here: behind a Cloudflare Tunnel (or any
// reverse proxy/CDN), the browser's connection to Cloudflare is HTTPS
// but Cloudflare's connection to this Apache is plain HTTP — Apache
// never sees $_SERVER['HTTPS'] set, so without this every generated
// link would say http:// on a page the browser loaded over https://.
$forwardedProto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// String-based, not dirname() — PHP's dirname() on Windows returns a bare
// "\" for a single-segment path like "/admin", which would otherwise leak
// a literal backslash into every generated URL.
$scriptDir = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = substr($scriptDir, 0, strrpos($scriptDir, '/'));
// Pages under /admin, /agency, /client, or /freelancer need the app root, not their own folder.
$lastSegment = substr($scriptDir, strrpos($scriptDir, '/') + 1);
if (in_array($lastSegment, ['admin', 'agency', 'client', 'freelancer'], true)) {
    $scriptDir = substr($scriptDir, 0, strrpos($scriptDir, '/'));
}
define('APP_URL', $scheme . '://' . $host . $scriptDir);

define('APP_NAME', 'MaidTrack');
define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');
define('DATE_FORMAT_DISPLAY', 'd/m/Y');
define('DATE_FORMAT_DB', 'Y-m-d');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_TMP_DIR', __DIR__ . '/../uploads/tmp/');
define('UPLOAD_HOUSEMAID_DIR', __DIR__ . '/../uploads/housemaids/');
define('UPLOAD_AGENCY_DIR', __DIR__ . '/../uploads/agencies/');
define('UPLOAD_FREELANCER_DIR', __DIR__ . '/../uploads/freelancers/');
define('UPLOAD_INCIDENT_DIR', __DIR__ . '/../uploads/incidents/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Client account must verify both before any candidate profile is visible.
define('CLIENT_VERIFICATION_REQUIRED', true);
define('OTP_TTL_MINUTES', 10);

// Passport numbers are masked to the last 4 digits everywhere except the
// owning agency, Admin, and a client with a confirmed booking. See the
// "Trust & data risk" section of the platform proposal.
define('PASSPORT_MASK_VISIBLE_CHARS', 4);

// Login rate-limiting — keyed by the email that was tried, not by IP,
// so it directly protects a specific account from a password-guessing
// script regardless of how many addresses it's spread across.
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
