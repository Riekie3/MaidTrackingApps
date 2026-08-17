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
