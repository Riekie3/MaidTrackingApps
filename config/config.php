<?php
// Application-wide constants. Safe to tweak per environment.

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
