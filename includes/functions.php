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
