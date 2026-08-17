<?php
// Application-wide constants. Safe to tweak per environment.

// Derived from the request so this works unchanged whether it's served
// from XAMPP's htdocs (e.g. /MaidTrackingApps), a bare vhost, or a
// production domain — no hardcoded path to break when the environment
// changes.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// String-based, not dirname() — PHP's dirname() on Windows returns a bare
// "\" for a single-segment path like "/admin", which would otherwise leak
// a literal backslash into every generated URL.
$scriptDir = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = substr($scriptDir, 0, strrpos($scriptDir, '/'));
// Pages under /admin, /agency, or /client need the app root, not their own folder.
$lastSegment = substr($scriptDir, strrpos($scriptDir, '/') + 1);
if (in_array($lastSegment, ['admin', 'agency', 'client'], true)) {
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
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);

// Client account must verify both before any candidate profile is visible.
define('CLIENT_VERIFICATION_REQUIRED', true);
define('OTP_TTL_MINUTES', 10);

// Passport numbers are masked to the last 4 digits everywhere except the
// owning agency, Admin, and a client with a confirmed booking. See the
// "Trust & data risk" section of the platform proposal.
define('PASSPORT_MASK_VISIBLE_CHARS', 4);

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
