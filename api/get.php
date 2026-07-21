<?php
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

$id = (int)($_GET['id'] ?? 0);
// Admins may open any user's QR; staff stays restricted to their own.
if (is_admin()) {
    $st = db()->prepare("SELECT * FROM qrcodes WHERE id = ? LIMIT 1");
    $st->execute([$id]);
} else {
    $st = db()->prepare("SELECT * FROM qrcodes WHERE id = ? AND user_id = ? LIMIT 1");
    $st->execute([$id, uid()]);
}
$r = $st->fetch();
if (!$r) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 404);

json_out(['ok' => true, 'item' => [
    'id'              => (int)$r['id'],
    'name'            => $r['name'],
    'category'        => $r['category'],
    'type'            => $r['type'],
    'destination_url' => $r['destination_url'],
    'short_code'      => $r['short_code'],
    'scan_count'      => (int)$r['scan_count'],
    'qr_data'         => $r['type'] === 'dynamic' ? dynamic_link($r['short_code']) : $r['destination_url'],
    'style'           => $r['style_json'] ? json_decode($r['style_json'], true) : null,
    'logo_data'       => $r['logo_data'],
    'expires_at'      => $r['expires_at'],
]]);
