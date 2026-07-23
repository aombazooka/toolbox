<?php
// ============================================================
//  Create a short link. Public endpoint — works for guests too
//  (user_id is stored NULL when not logged in, see TOOLBOX-PLAN T1).
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า'], 400);

/** Slugs/paths that must never be claimable as a short-link alias
 *  (matches on-disk pages + everything in the tool registry, so a future
 *  tool never gets shadowed by a previously-created short link). */
function reserved_short_codes(): array {
    $reserved = [
        's', 'r', 'api', 'qr', 'login', 'register', 'logout', 'install', 'index',
        'migrate', 'history', 'analytics', 'settings', 'bulk', 'print', 'audit',
        'users_admin', 'shorten', 'config', 'schema', 'assets', 'includes', 'admin', 'guest',
    ];
    try {
        $tools = require __DIR__ . '/../../includes/tools.php';
        foreach ($tools as $t) {
            if (!empty($t['slug'])) $reserved[] = strtolower($t['slug']);
            if (!empty($t['href'])) $reserved[] = strtolower(preg_replace('/\.php$/i', '', $t['href']));
        }
    } catch (Throwable $e) { /* fall back to the hardcoded list above */ }
    return array_unique($reserved);
}

/** http/https only, must have a host. */
function is_valid_target_url(string $url): bool {
    $p = parse_url($url);
    return $p !== false
        && !empty($p['scheme']) && in_array(strtolower($p['scheme']), ['http', 'https'], true)
        && !empty($p['host']);
}

$target = trim(str_replace(["\r", "\n"], '', $in['target_url'] ?? ''));
if ($target === '') json_out(['ok' => false, 'error' => 'กรุณากรอกลิงก์ปลายทาง'], 422);
if (mb_strlen($target) > 2000) json_out(['ok' => false, 'error' => 'ลิงก์ยาวเกินไป'], 422);
if (!is_valid_target_url($target)) {
    json_out(['ok' => false, 'error' => 'กรุณากรอกลิงก์ที่ขึ้นต้นด้วย http:// หรือ https://'], 422);
}

$alias = trim((string)($in['alias'] ?? ''));
$code  = null;
if ($alias !== '') {
    if (!preg_match('/^[A-Za-z0-9_-]{3,32}$/', $alias)) {
        json_out(['ok' => false, 'error' => 'ชื่อย่อใช้ได้เฉพาะ a-z, 0-9, - และ _ ยาว 3–32 ตัวอักษร'], 422);
    }
    if (in_array(strtolower($alias), reserved_short_codes(), true)) {
        json_out(['ok' => false, 'error' => 'ชื่อย่อนี้สงวนไว้ใช้งานในระบบ กรุณาตั้งชื่ออื่น'], 422);
    }
    $chk = db()->prepare("SELECT COUNT(*) FROM short_links WHERE code = ?");
    $chk->execute([$alias]);
    if ((int)$chk->fetchColumn() > 0) {
        json_out(['ok' => false, 'error' => 'ชื่อย่อนี้มีคนใช้แล้ว กรุณาตั้งชื่ออื่น'], 422);
    }
    $code = $alias;
}

$expiresAt = null;
if (array_key_exists('expires_at', $in) && trim((string)$in['expires_at']) !== '') {
    $ts = strtotime((string)$in['expires_at']);
    if ($ts === false) json_out(['ok' => false, 'error' => 'วันหมดอายุไม่ถูกต้อง'], 422);
    $expiresAt = date('Y-m-d H:i:s', $ts);
}

$userId = is_logged_in() ? uid() : null;
if ($code === null) $code = generate_short_code();

$sql = "INSERT INTO short_links (code, target_url, user_id, expires_at) VALUES (?,?,?,?)";
$attempts = 0;
while (true) {
    try {
        db()->prepare($sql)->execute([$code, $target, $userId, $expiresAt]);
        break;
    } catch (PDOException $ex) {
        // Only auto-retry with a fresh random code when the caller didn't ask for a specific alias.
        if ($alias === '' && $ex->getCode() === '23000' && $attempts < 5) {
            $code = generate_short_code();
            $attempts++;
            continue;
        }
        json_out(['ok' => false, 'error' => 'สร้างลิงก์ไม่สำเร็จ กรุณาลองใหม่'], 500);
    }
}

$id = (int)db()->lastInsertId();
log_audit('short_create', 'short_link', $id, $code);

json_out([
    'ok'         => true,
    'id'         => $id,
    'code'       => $code,
    'short_url'  => short_link($code),
    'target_url' => $target,
    'expires_at' => $expiresAt,
]);
