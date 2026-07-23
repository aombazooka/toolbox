<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // app runs on http://localhost — do not force secure or logins break
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array {
    $u = $_SESSION['user'] ?? null;
    if (!$u) return null;
    // Self-heal a session written by an older/partial shape so display code
    // never hits an undefined-array-key warning on a stale session.
    if (empty($u['display_name'])) $u['display_name'] = $u['username'] ?? '';
    return $u;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

/** Redirect to login if not authenticated. Call at the top of protected pages.
 *  Uses a relative target so the app works on any host (localhost, 127.0.0.1, a server). */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** For public (guest-accessible) pages: bounce to the installer if the app
 *  isn't set up yet, so a fresh install still reaches install.php. */
function require_installed(): void {
    try {
        db()->query("SELECT 1 FROM users LIMIT 1");
    } catch (Throwable $e) {
        header('Location: install.php');
        exit;
    }
}

/** Guard for JSON API endpoints. */
function require_login_api(): void {
    if (!is_logged_in()) {
        json_out(['ok' => false, 'error' => 'ยังไม่ได้เข้าสู่ระบบ'], 401);
    }
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'           => (int)$user['id'],
        'username'     => $user['username'],
        'display_name' => $user['display_name'] ?: $user['username'],
        'role'         => $user['role'] ?? 'admin',
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Current user's id (0 if none). */
function uid(): int {
    return (int)($_SESSION['user']['id'] ?? 0);
}

/** Is the current user an admin? */
function is_admin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/** Guard for admin-only pages. Requires login first, then bounces non-admins home. */
function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: index.php');
        exit;
    }
}

/** Guard for admin-only JSON API endpoints. */
function require_admin_api(): void {
    require_login_api();
    if (!is_admin()) {
        json_out(['ok' => false, 'error' => 'ต้องเป็นผู้ดูแลระบบ'], 403);
    }
}

/**
 * Record an audit trail entry (login, create/update/delete, user management, ...).
 * Best-effort only: wrapped in try/catch so a missing/unmigrated audit_log table,
 * or any other DB hiccup, can NEVER break the request that called it.
 */
function log_audit(string $action, ?string $entity = null, ?int $entityId = null, ?string $detail = null, ?int $userId = null): void {
    try {
        $who = $userId ?? uid();
        if ($who <= 0) $who = null;
        if ($detail !== null && mb_strlen($detail) > 255) {
            $detail = mb_substr($detail, 0, 255);
        }
        $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|qrstudio');
        db()->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, detail, ip_hash) VALUES (?,?,?,?,?,?)'
        )->execute([$who, $action, $entity, $entityId, $detail, $ipHash]);
    } catch (Throwable $e) {
        // table not migrated yet, or DB hiccup — logging must never break the caller.
    }
}
