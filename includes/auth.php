<?php
// Session-based auth shared by all three portals. One session, one
// `role` + `id`, redirect target decided by role — matches the "one
// login form, role-based redirect" call in the tech stack.

function login_as(string $role, int $id, string $name): void
{
    session_regenerate_id(true);
    $_SESSION['auth_role'] = $role; // 'admin' | 'agency' | 'client' | 'freelancer'
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
        'admin'      => rtrim(APP_URL, '/') . '/admin/index.php',
        'agency'     => rtrim(APP_URL, '/') . '/agency/index.php',
        'client'     => rtrim(APP_URL, '/') . '/client/index.php',
        'freelancer' => rtrim(APP_URL, '/') . '/freelancer/index.php',
        default      => rtrim(APP_URL, '/') . '/login.php',
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

// --- Login rate-limiting ------------------------------------------------
// Keyed by email (LOGIN_MAX_ATTEMPTS/LOGIN_LOCKOUT_MINUTES in config.php),
// not by session, so it survives a cleared cookie and applies regardless
// of which browser/device the attempts come from.

function is_login_locked_out(string $email): bool
{
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([strtolower($email), LOGIN_LOCKOUT_MINUTES]);
    return ((int) $stmt->fetchColumn()) >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(string $email): void
{
    $stmt = getDB()->prepare('INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)');
    $stmt->execute([strtolower($email), $_SERVER['REMOTE_ADDR'] ?? null]);
}
