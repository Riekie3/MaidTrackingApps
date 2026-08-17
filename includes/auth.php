<?php
// Session-based auth shared by all three portals. One session, one
// `role` + `id`, redirect target decided by role — matches the "one
// login form, role-based redirect" call in the tech stack.

function login_as(string $role, int $id, string $name): void
{
    session_regenerate_id(true);
    $_SESSION['auth_role'] = $role; // 'admin' | 'agency' | 'client'
    $_SESSION['auth_id']   = $id;
    $_SESSION['auth_name'] = $name;
}

function current_role(): ?string
{
    return $_SESSION['auth_role'] ?? null;
}

function current_id(): ?int
{
    return isset($_SESSION['auth_id']) ? (int) $_SESSION['auth_id'] : null;
}

function current_name(): string
{
    return $_SESSION['auth_name'] ?? '';
}

function is_logged_in(): bool
{
    return current_role() !== null;
}

function require_role(string $role): void
{
    if (current_role() !== $role) {
        redirect(rtrim(APP_URL, '/') . '/login.php');
    }
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function dashboard_url_for(string $role): string
{
    return match ($role) {
        'admin'  => rtrim(APP_URL, '/') . '/admin/index.php',
        'agency' => rtrim(APP_URL, '/') . '/agency/index.php',
        'client' => rtrim(APP_URL, '/') . '/client/index.php',
        default  => rtrim(APP_URL, '/') . '/login.php',
    };
}

// --- CSRF -------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Session expired — please go back and try again.');
    }
}
