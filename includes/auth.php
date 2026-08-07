<?php
// ============================================================
//  Session shell — no database.
//  This build is a fully client-side toolbox; there are no user
//  accounts or persistence, so nothing here touches a DB.
// ============================================================
require_once __DIR__ . '/../config.php';
date_default_timezone_set(APP_TZ);
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // runs on http://localhost — do not force secure
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Kept for the shared header (which shows a user chip if one ever exists).
 *  With no login flow, this is always null. */
function current_user(): ?array {
    $u = $_SESSION['user'] ?? null;
    if (!$u) return null;
    if (empty($u['display_name'])) $u['display_name'] = $u['username'] ?? '';
    return $u;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

/** No-op guard for public tool pages. There is no database or installer
 *  to check anymore, so this simply lets the page render. */
function require_installed(): void { /* no database — nothing to check */ }

/** Current user's id (0 — there is no login). */
function uid(): int {
    return (int)($_SESSION['user']['id'] ?? 0);
}
