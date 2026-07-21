<?php
// ============================================================
//  Dynamic QR redirect — public endpoint (no login).
//  Looks up a short code, logs the scan, and redirects.
//  URL: r.php?c=CODE
// ============================================================
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

function not_found(string $msg): void {
    http_response_code(404);
    ?><!doctype html>
    <html lang="th"><head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ไม่พบลิงก์ · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app-extra.css')) ?>">
    </head><body>
    <div class="auth-wrap"><div class="auth-card" style="text-align:center">
      <div class="auth-brand" style="margin-bottom:14px">
        <div class="logo-mark" style="background:var(--surface-3)">
          <svg viewBox="0 0 24 24" fill="none" stroke="var(--text-3)" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
        </div>
        <h1>ไม่พบลิงก์นี้</h1>
      </div>
      <p class="hint"><?= e($msg) ?></p>
    </div></div>
    </body></html><?php
    exit;
}

$code = preg_replace('/[^A-Za-z0-9]/', '', $_GET['c'] ?? '');
if ($code === '') not_found('ลิงก์ไม่ถูกต้อง');

$st = db()->prepare("SELECT id, destination_url, is_active, expires_at
                     FROM qrcodes WHERE short_code = ? AND type = 'dynamic' LIMIT 1");
$st->execute([$code]);
$r = $st->fetch();

if (!$r) not_found('ลิงก์นี้อาจถูกลบไปแล้ว');
if (!$r['is_active']) not_found('ลิงก์นี้ถูกปิดการใช้งาน');
if ($r['expires_at'] && strtotime($r['expires_at']) < time()) not_found('ลิงก์นี้หมดอายุแล้ว');

// log the scan (best effort — never block the redirect)
try {
    db()->prepare("UPDATE qrcodes SET scan_count = scan_count + 1 WHERE id = ?")->execute([$r['id']]);
    $ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|qrstudio');
    $ua     = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    db()->prepare("INSERT INTO scans (qrcode_id, ip_hash, user_agent) VALUES (?,?,?)")
        ->execute([$r['id'], $ipHash, $ua]);
} catch (Throwable $e) { /* ignore logging failures */ }

$dest = str_replace(["\r", "\n"], '', $r['destination_url']);
header('Location: ' . $dest, true, 302);
exit;
