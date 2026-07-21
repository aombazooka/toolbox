<?php
// ============================================================
//  QR Studio — scan analytics API (scoped to the logged-in user)
//  GET  api/analytics.php            → overview (last 30 days + top 5)
//  GET  api/analytics.php?id=ID      → detail for one QR (must be owned)
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

/** Thai day+month label without year, e.g. "5 ก.ค." (for chart axis). */
function thai_day_month(string $ymd): string {
    $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($ymd);
    return (int)date('j', $ts) . ' ' . $m[(int)date('n', $ts)];
}

/** Build the 30-day date skeleton (oldest → newest, today inclusive), all counts 0. */
function build_30day_skeleton(): array {
    $days = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $days[$d] = ['date' => $d, 'label' => thai_day_month($d), 'count' => 0];
    }
    return $days;
}

/** Merge a [date => count] map (from GROUP BY DATE(...)) into the skeleton. */
function merge_daily(array $skeleton, array $rows): array {
    foreach ($rows as $row) {
        $d = $row['d'];
        if (isset($skeleton[$d])) $skeleton[$d]['count'] = (int)$row['c'];
    }
    return array_values($skeleton);
}

/** Truncate a user-agent string for display. */
function short_ua(?string $ua): string {
    $ua = trim((string)$ua);
    if ($ua === '') return 'ไม่ทราบอุปกรณ์';
    return mb_substr($ua, 0, 140);
}

$pdo = db();
$uid = uid();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Admin-only "view everyone" mode for the overview. Never honor ?all= for non-admins.
$all = (($_GET['all'] ?? '') === '1') && is_admin();

if ($id > 0) {
    // ---------------- detail mode ----------------
    // Admins may view any QR's detail; staff stays restricted to their own.
    if (is_admin()) {
        $st = $pdo->prepare(
            "SELECT id, name, type, short_code, scan_count
             FROM qrcodes WHERE id = ? LIMIT 1"
        );
        $st->execute([$id]);
    } else {
        $st = $pdo->prepare(
            "SELECT id, name, type, short_code, scan_count
             FROM qrcodes WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $st->execute([$id, $uid]);
    }
    $qr = $st->fetch();
    if (!$qr) json_out(['ok' => false, 'error' => 'ไม่พบ QR นี้'], 404);

    $daily = $pdo->prepare(
        "SELECT DATE(scanned_at) AS d, COUNT(*) AS c
         FROM scans
         WHERE qrcode_id = ? AND scanned_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(scanned_at)"
    );
    $daily->execute([$id]);
    $dailyArr = merge_daily(build_30day_skeleton(), $daily->fetchAll());

    $recentSt = $pdo->prepare(
        "SELECT scanned_at, user_agent
         FROM scans WHERE qrcode_id = ?
         ORDER BY scanned_at DESC LIMIT 50"
    );
    $recentSt->execute([$id]);
    $recent = [];
    foreach ($recentSt as $r) {
        $recent[] = [
            'scanned_at' => thai_date($r['scanned_at']) . ' ' . date('H:i', strtotime($r['scanned_at'])),
            'user_agent' => short_ua($r['user_agent']),
        ];
    }

    json_out(['ok' => true, 'mode' => 'detail', 'qr' => [
        'id'         => (int)$qr['id'],
        'name'       => $qr['name'],
        'type'       => $qr['type'],
        'short_code' => $qr['short_code'],
        'scan_count' => (int)$qr['scan_count'],
    ], 'daily' => $dailyArr, 'recent' => $recent]);
}

// ---------------- overview mode ----------------
if ($all) {
    $totalSt = $pdo->query("SELECT COALESCE(SUM(scan_count), 0) AS total FROM qrcodes");
    $totalScans = (int)$totalSt->fetchColumn();

    $dynSt = $pdo->query("SELECT COUNT(*) FROM qrcodes WHERE type = 'dynamic'");
    $dynamicCount = (int)$dynSt->fetchColumn();

    $dailySt = $pdo->query(
        "SELECT DATE(s.scanned_at) AS d, COUNT(*) AS c
         FROM scans s
         JOIN qrcodes q ON q.id = s.qrcode_id
         WHERE s.scanned_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(s.scanned_at)"
    );
    $dailyArr = merge_daily(build_30day_skeleton(), $dailySt->fetchAll());

    $topSt = $pdo->query(
        "SELECT q.id, q.name, q.type, q.scan_count, u.display_name AS owner_display_name, u.username AS owner_username
         FROM qrcodes q
         LEFT JOIN users u ON u.id = q.user_id
         WHERE q.scan_count > 0
         ORDER BY q.scan_count DESC LIMIT 5"
    );
    $top = [];
    foreach ($topSt as $r) {
        $top[] = [
            'id'         => (int)$r['id'],
            'name'       => $r['name'],
            'type'       => $r['type'],
            'scan_count' => (int)$r['scan_count'],
            'owner'      => $r['owner_display_name'] ?: $r['owner_username'],
        ];
    }
} else {
    $totalSt = $pdo->prepare("SELECT COALESCE(SUM(scan_count), 0) AS total FROM qrcodes WHERE user_id = ?");
    $totalSt->execute([$uid]);
    $totalScans = (int)$totalSt->fetchColumn();

    $dynSt = $pdo->prepare("SELECT COUNT(*) FROM qrcodes WHERE user_id = ? AND type = 'dynamic'");
    $dynSt->execute([$uid]);
    $dynamicCount = (int)$dynSt->fetchColumn();

    $dailySt = $pdo->prepare(
        "SELECT DATE(s.scanned_at) AS d, COUNT(*) AS c
         FROM scans s
         JOIN qrcodes q ON q.id = s.qrcode_id
         WHERE q.user_id = ? AND s.scanned_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(s.scanned_at)"
    );
    $dailySt->execute([$uid]);
    $dailyArr = merge_daily(build_30day_skeleton(), $dailySt->fetchAll());

    $topSt = $pdo->prepare(
        "SELECT id, name, type, scan_count FROM qrcodes
         WHERE user_id = ? AND scan_count > 0
         ORDER BY scan_count DESC LIMIT 5"
    );
    $topSt->execute([$uid]);
    $top = [];
    foreach ($topSt as $r) {
        $top[] = [
            'id'         => (int)$r['id'],
            'name'       => $r['name'],
            'type'       => $r['type'],
            'scan_count' => (int)$r['scan_count'],
        ];
    }
}

json_out([
    'ok'            => true,
    'mode'          => 'overview',
    'all'           => $all,
    'total_scans'   => $totalScans,
    'dynamic_count' => $dynamicCount,
    'daily'         => $dailyArr,
    'top'           => $top,
]);
