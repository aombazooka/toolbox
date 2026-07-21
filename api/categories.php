<?php
// ============================================================
//  QR Studio — categories API (list / add / delete)
//  qrcodes.category stays a plain string; this table only supplies
//  the option list + colors shown in the dropdowns and history tags.
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $st = db()->prepare("SELECT id, name, color FROM categories WHERE user_id = ? ORDER BY id");
    $st->execute([uid()]);
    $items = [];
    foreach ($st as $r) {
        $items[] = ['id' => (int)$r['id'], 'name' => $r['name'], 'color' => $r['color']];
    }
    json_out(['ok' => true, 'items' => $items]);
}

if ($method !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า'], 400);

$action = $in['action'] ?? '';

if ($action === 'add') {
    $name = trim($in['name'] ?? '');
    if ($name === '') json_out(['ok' => false, 'error' => 'กรุณากรอกชื่อหมวดหมู่'], 422);
    if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);

    $color = trim($in['color'] ?? '');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#9098a6';

    $dup = db()->prepare("SELECT COUNT(*) FROM categories WHERE user_id = ? AND name = ?");
    $dup->execute([uid(), $name]);
    if ((int)$dup->fetchColumn() > 0) json_out(['ok' => false, 'error' => 'มีหมวดหมู่นี้อยู่แล้ว'], 422);

    $ins = db()->prepare("INSERT INTO categories (user_id, name, color) VALUES (?,?,?)");
    $ins->execute([uid(), $name, $color]);

    json_out(['ok' => true, 'item' => ['id' => (int)db()->lastInsertId(), 'name' => $name, 'color' => $color]]);
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 422);

    $del = db()->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $del->execute([$id, uid()]);
    if ($del->rowCount() === 0) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 404);

    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'ไม่รู้จักคำสั่ง'], 400);
