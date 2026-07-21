<?php
require_once __DIR__ . '/../includes/auth.php';
require_login_api();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ'], 400);

$id = (int)($in['id'] ?? 0);
// Admins may delete any user's QR; staff stays restricted to their own.
if (is_admin()) {
    $st = db()->prepare("DELETE FROM qrcodes WHERE id = ?");
    $st->execute([$id]);
} else {
    $st = db()->prepare("DELETE FROM qrcodes WHERE id = ? AND user_id = ?");
    $st->execute([$id, uid()]);
}

if ($st->rowCount() > 0) {
    log_audit('qr_delete', 'qrcode', $id);
}

json_out(['ok' => true, 'deleted' => $st->rowCount()]);
