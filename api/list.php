<?php
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

$q   = trim($_GET['q'] ?? '');
$cat = trim($_GET['category'] ?? '');

// Admin-only "view everyone" mode. Never honor ?all= for non-admins — re-check server-side.
$all = (($_GET['all'] ?? '') === '1') && is_admin();

$where = [];
$args  = [];
if (!$all) { $where[] = 'qrcodes.user_id = ?'; $args[] = uid(); }
if ($q !== '')  { $where[] = '(name LIKE ? OR destination_url LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($cat !== '') { $where[] = 'category = ?'; $args[] = $cat; }

$ownerSelect = $all ? ', u.display_name AS owner_display_name, u.username AS owner_username' : '';
$ownerJoin   = $all ? 'LEFT JOIN users u ON u.id = qrcodes.user_id' : '';
$whereSql    = $where ? implode(' AND ', $where) : '1=1';

$sql = "SELECT qrcodes.id, qrcodes.name, qrcodes.category, qrcodes.type, qrcodes.destination_url, qrcodes.short_code,
               qrcodes.scan_count, qrcodes.created_at, qrcodes.style_json, qrcodes.is_active, qrcodes.expires_at,
               c.color AS category_color$ownerSelect
        FROM qrcodes
        LEFT JOIN categories c ON c.user_id = qrcodes.user_id AND c.name = qrcodes.category
        $ownerJoin
        WHERE $whereSql
        ORDER BY qrcodes.created_at DESC, qrcodes.id DESC LIMIT 500";
$st = db()->prepare($sql);
$st->execute($args);

$items = [];
foreach ($st as $r) {
    $expired = $r['expires_at'] !== null && strtotime($r['expires_at']) < time();
    $item = [
        'id'              => (int)$r['id'],
        'name'            => $r['name'],
        'category'        => $r['category'],
        'category_color'  => $r['category_color'],
        'type'            => $r['type'],
        'destination_url' => $r['destination_url'],
        'short_code'      => $r['short_code'],
        'scan_count'      => (int)$r['scan_count'],
        'created_at'      => thai_date($r['created_at']),
        'qr_data'         => $r['type'] === 'dynamic' ? dynamic_link($r['short_code']) : $r['destination_url'],
        'style'           => $r['style_json'] ? json_decode($r['style_json'], true) : null,
        'is_active'       => (int)$r['is_active'],
        'expires_at'      => $r['expires_at'],
        'expired'         => $expired,
    ];
    if ($all) {
        $item['owner'] = $r['owner_display_name'] ?: $r['owner_username'];
    }
    $items[] = $item;
}

$statsSql = "SELECT COUNT(*) AS total,
                    COALESCE(SUM(scan_count), 0) AS scans,
                    COUNT(DISTINCT category) AS cats,
                    COALESCE(SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)), 0) AS week
             FROM qrcodes" . ($all ? '' : ' WHERE user_id = ?');
$stats = db()->prepare($statsSql);
$stats->execute($all ? [] : [uid()]);

json_out(['ok' => true, 'items' => $items, 'stats' => $stats->fetch()]);
