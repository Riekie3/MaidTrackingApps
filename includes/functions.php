<?php
// Small shared helpers. Grows as each phase adds real pages.

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

// Shows only the last N digits of a passport/ID number — the default
// view everywhere except the owning agency, Admin, or a client with a
// confirmed booking. See config.php's PASSPORT_MASK_VISIBLE_CHARS.
function mask_document_number(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $visible = PASSPORT_MASK_VISIBLE_CHARS;
    $len = strlen($value);
    if ($len <= $visible) {
        return str_repeat('•', $len);
    }
    return str_repeat('•', $len - $visible) . substr($value, -$visible);
}

// --- Flash messages ----------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// --- Formatting ----------------------------------------------------------

function fmt_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date(DATE_FORMAT_DISPLAY, $ts) : '—';
}

function days_until(?string $value): ?int
{
    if (!$value) {
        return null;
    }
    $ts = strtotime($value);
    if (!$ts) {
        return null;
    }
    return (int) floor((strtotime(date('Y-m-d', $ts)) - strtotime(date('Y-m-d'))) / 86400);
}

// --- File uploads --------------------------------------------------------
// Validates against config.php's UPLOAD_MAX_SIZE / UPLOAD_ALLOWED_TYPES,
// renames to a random filename (never trust the original), and returns
// the stored filename on success or null on failure/no-file.

function handle_upload(string $fieldName, string $destDir): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_TYPES, true)) {
        return null;
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $storedName)) {
        return null;
    }
    return $storedName;
}

// Moves a file staged in UPLOAD_TMP_DIR (during a multi-step form) into
// its permanent destination once the record is actually saved.
function promote_upload(string $tmpFilename, string $destDir): ?string
{
    $from = rtrim(UPLOAD_TMP_DIR, '/') . '/' . $tmpFilename;
    if (!is_file($from)) {
        return null;
    }
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $to = rtrim($destDir, '/') . '/' . $tmpFilename;
    return rename($from, $to) ? $tmpFilename : null;
}

// --- Reports (Phase 3) ----------------------------------------------------
// No PDF library — reports are print-styled HTML (see the @media print
// rules in app.css) with a browser "Print / Save as PDF" button, same
// approach as Car Maintenance Tracker. Keeps the stack dependency-free.

// Guards against CSV/formula injection: a cell starting with =, +, -, or
// @ gets a leading single quote so Excel/Sheets never executes it as a
// formula when the export is opened.
function csv_safe(?string $value): string
{
    $value = $value ?? '';
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
        return "'" . $value;
    }
    return $value;
}

// Streams $rows (array of associative arrays) as a CSV download and
// exits. $headers maps column keys to display labels, in output order.
function csv_download(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_values($headers));
    foreach ($rows as $row) {
        $line = [];
        foreach (array_keys($headers) as $key) {
            $line[] = csv_safe((string) ($row[$key] ?? ''));
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

// --- Field-level encryption (Phase 4) -------------------------------------
// AES-256-GCM for passport_number / national_id_number at rest — see
// ENCRYPTION_KEY in config/secrets.php. Storage format is a single
// base64 string of iv . tag . ciphertext, so it's one column, no schema
// change beyond what was already there.

function encrypt_field(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }
    $key = hex2bin(ENCRYPTION_KEY);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $cipher);
}

function decrypt_field(?string $stored): ?string
{
    if ($stored === null || $stored === '') {
        return null;
    }
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) < 28) {
        return null; // not valid ciphertext — e.g. pre-encryption legacy data
    }
    $key = hex2bin(ENCRYPTION_KEY);
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? null : $plain;
}

// --- Protected file serving (Phase 4) -------------------------------------
// Everything under /uploads is blocked from direct web access (see
// uploads/.htaccess) on real Apache hosting; this is what actually
// enforces access control either way, since it runs in PHP regardless
// of web server. Never trust the extension a file was uploaded with —
// re-derive the MIME type from the fixed allow-list.

function stream_protected_file(string $absolutePath): never
{
    if (!is_file($absolutePath)) {
        http_response_code(404);
        die('File not found.');
    }
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $mimeMap = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $mime = $mimeMap[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($absolutePath) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($absolutePath);
    exit;
}

function calculate_age(?string $dob): ?int
{
    if (!$dob) {
        return null;
    }
    $ts = strtotime($dob);
    if (!$ts) {
        return null;
    }
    return (int) date_diff(date_create($dob), date_create('now'))->y;
}
