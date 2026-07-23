<?php
// ============================================================
//  "My links" — list the logged-in user's own short links (+ click
//  counts), and handle their mutations (edit destination / disable /
//  enable / delete). Guests never reach this endpoint (login required).
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
require_login_api();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $st = db()->prepare(
        "SELECT id, code, target_url, clicks, is_active, expires_at, created_at
         FROM short_links WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 500"
    );
    $st->execute([uid()]);

    $items = [];
    foreach ($st as $r) {
        $expired = $r['expires_at'] !== null && strtotime($r['expires_at']) < time();
        $items[] = [
            'id'         => (int)$r['id'],
            'code'       => $r['code'],
            'short_url'  => short_link($r['code']),
            'target_url' => $r['target_url'],
            'clicks'     => (int)$r['clicks'],
            'is_active'  => (int)$r['is_active'],
            'expires_at' => $r['expires_at'],
            'expired'    => $expired,
            'created_at' => thai_date($r['created_at']),
        ];
    }
    json_out(['ok' => true, 'items' => $items]);
}

if ($method !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ'], 400);

$action = $in['action'] ?? '';
$id     = (int)($in['id'] ?? 0);
if ($id <= 0) json_out(['ok' => false, 'error' => 'id ไม่ถูกต้อง'], 422);

// Ownership check shared by every mutation below — a user may only manage their own links.
$own = db()->prepare("SELECT id FROM short_links WHERE id = ? AND user_id = ? LIMIT 1");
$own->execute([$id, uid()]);
if (!$own->fetch()) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 404);

if ($action === 'delete') {
    db()->prepare("DELETE FROM short_links WHERE id = ? AND user_id = ?")->execute([$id, uid()]);
    log_audit('short_delete', 'short_link', $id);
    json_out(['ok' => true]);
}

if ($action === 'update') {
    $fields = [];
    $args   = [];

    if (array_key_exists('is_active', $in)) {
        $fields[] = 'is_active = ?';
        $args[]   = $in['is_active'] ? 1 : 0;
    }
    if (array_key_exists('target_url', $in)) {
        $d = trim(str_replace(["\r", "\n"], '', (string)$in['target_url']));
        if ($d === '') json_out(['ok' => false, 'error' => 'ลิงก์ปลายทางว่าง'], 422);
        if (mb_strlen($d) > 2000) json_out(['ok' => false, 'error' => 'ลิงก์ยาวเกินไป'], 422);
        $p = parse_url($d);
        if (!$p || empty($p['scheme']) || !in_array(strtolower($p['scheme']), ['http', 'https'], true) || empty($p['host'])) {
            json_out(['ok' => false, 'error' => 'กรุณากรอกลิงก์ที่ขึ้นต้นด้วย http:// หรือ https://'], 422);
        }
        $fields[] = 'target_url = ?';
        $args[]   = $d;
    }
    if (!$fields) json_out(['ok' => false, 'error' => 'ไม่มีข้อมูลให้แก้ไข'], 422);

    $args[] = $id;
    $args[] = uid();
    db()->prepare("UPDATE short_links SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?")->execute($args);
    log_audit('short_update', 'short_link', $id);
    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'action ไม่ถูกต้อง'], 422);
