<?php
require_once __DIR__ . '/../includes/auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ'], 400);

$id = (int)($in['id'] ?? 0);
if ($id <= 0) json_out(['ok' => false, 'error' => 'id ไม่ถูกต้อง'], 422);

$fields = [];
$args   = [];
if (array_key_exists('destination_url', $in)) {
    $d = trim((string)$in['destination_url']);
    if ($d === '') json_out(['ok' => false, 'error' => 'ลิงก์ปลายทางว่าง'], 422);
    if (mb_strlen($d) > 2000) json_out(['ok' => false, 'error' => 'ลิงก์ยาวเกินไป'], 422);
    $fields[] = 'destination_url = ?';
    $args[]   = $d;
}
if (array_key_exists('name', $in)) {
    $n = trim((string)$in['name']);
    $fields[] = 'name = ?';
    $args[]   = $n !== '' ? mb_substr($n, 0, 200) : 'ไม่มีชื่อ';
}
if (array_key_exists('is_active', $in)) {
    $fields[] = 'is_active = ?';
    $args[]   = $in['is_active'] ? 1 : 0;
}
if (array_key_exists('expires_at', $in)) {
    if ($in['expires_at'] === null || trim((string)$in['expires_at']) === '') {
        $fields[] = 'expires_at = NULL';
    } else {
        $ts = strtotime((string)$in['expires_at']);
        if ($ts === false) json_out(['ok' => false, 'error' => 'วันหมดอายุไม่ถูกต้อง'], 422);
        $fields[] = 'expires_at = ?';
        $args[]   = date('Y-m-d H:i:s', $ts);
    }
}
if (!$fields) json_out(['ok' => false, 'error' => 'ไม่มีข้อมูลให้แก้ไข'], 422);

// Admins may update any user's QR; staff stays restricted to their own.
$args[] = $id;
if (is_admin()) {
    $st = db()->prepare("UPDATE qrcodes SET " . implode(', ', $fields) . " WHERE id = ?");
} else {
    $args[] = uid();
    $st = db()->prepare("UPDATE qrcodes SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
}
$st->execute($args);

if ($st->rowCount() > 0) {
    $changed = array_intersect(
        array_keys($in),
        ['destination_url', 'name', 'is_active', 'expires_at']
    );
    log_audit('qr_update', 'qrcode', $id, implode(',', $changed));
}

json_out(['ok' => true, 'updated' => $st->rowCount()]);
