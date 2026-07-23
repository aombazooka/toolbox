<?php
// ---------- small shared helpers ----------

/** HTML-escape. */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Random short code for dynamic QR (no easily-confused characters). */
function generate_short_code(int $len = 7): string {
    $alphabet = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    $max = strlen($alphabet) - 1;
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $alphabet[random_int(0, $max)];
    return $s;
}

/** Emit JSON and stop. */
function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Decode a JSON request body into an array. */
function json_input(): array {
    $d = json_decode(file_get_contents('php://input'), true);
    return is_array($d) ? $d : [];
}

/** CSRF token for the current session. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(?string $t): bool {
    return $t !== null && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/** Format a DATETIME as Thai date, e.g. "13 ก.ค. 2568". */
function thai_date(?string $dt): string {
    if (!$dt) return '';
    $ts = strtotime($dt);
    $m  = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int)date('j', $ts) . ' ' . $m[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
}

/** Append a cache-busting version (the file's mtime) to a relative asset path,
 *  so browsers auto-fetch a fresh copy whenever the CSS/JS file changes. */
function asset_url(string $rel): string {
    $full = __DIR__ . '/../' . ltrim($rel, '/');
    $v = @filemtime($full) ?: 0;
    return $rel . '?v=' . $v;
}

/** Build the redirect URL a dynamic QR should encode. */
function dynamic_link(string $code): string {
    return rtrim(BASE_URL, '/') . '/r.php?c=' . $code;
}

/** Build the redirect URL a short link (shorten.php / s.php) should encode. */
function short_link(string $code): string {
    return rtrim(BASE_URL, '/') . '/s.php?c=' . $code;
}

/** id of the system "guest" user that owns QR codes created without login (0 if none). */
function guest_user_id(): int {
    static $id = null;
    if ($id === null) {
        try {
            $id = (int)(db()->query("SELECT id FROM users WHERE username = '__guest__' LIMIT 1")->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $id = 0;
        }
    }
    return $id;
}

/** Default category set (name => color) seeded for every new user. */
function default_categories(): array {
    return [
        'เอกสารทั่วไป'      => '#8b93a7',
        'ใบส่งตัว'          => '#f4a15c',
        'ผลตรวจ'            => '#30a46c',
        'คู่มือ / วิธีใช้'  => '#e8830c',
        'ส่วนตัว'           => '#7c7cf0',
        'อื่นๆ'             => '#9098a6',
    ];
}
