<?php
defined("APP_ROOT") || exit;

function current_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? 0;
    $u = $id ? row("SELECT * FROM users WHERE id = ? AND status <> 'blocked'", [$id]) : null;
    if (!$u) unset($_SESSION['uid']);
    return $u;
}
function is_role(string ...$roles): bool { $u = current_user(); return $u && in_array($u['role'], $roles, true); }

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    unset($_SESSION['csrf']);
}
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Require a logged-in user (optionally with one of the given roles). */
function require_login(string ...$roles): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please sign in to continue.');
        redirect('login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        render_error(403, 'You do not have permission to view this page.');
    }
    return $u;
}

/** Brute-force protection: max 8 failed sign-ins per IP in 10 minutes. */
function login_throttled(): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    q('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    return (int) val('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 10 MINUTE)', [$ip]) >= 8;
}
function login_failed(string $email): void
{
    q('INSERT INTO login_attempts (ip, email) VALUES (?, ?)', [$_SERVER['REMOTE_ADDR'] ?? '0', mb_substr($email, 0, 190)]);
}

function home_for(array $u): string
{
    return match ($u['role']) {
        'admin', 'instructor' => 'admin/',
        default => 'my-courses.php',
    };
}
