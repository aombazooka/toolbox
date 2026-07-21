<?php
// ============================================================
//  QR Studio — first-run installer
//  Creates the database + tables and the first admin account.
//  Safe to re-open: it will refuse to run again once installed.
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
date_default_timezone_set(APP_TZ);

function server_pdo(): PDO {
    return new PDO('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET, DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
function is_installed(): bool {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

$already = is_installed();
$error = '';
$done = false;

if (!$already && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $display  = trim($_POST['display_name'] ?? '');
    $display  = $display !== '' ? $display : $username;
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if ($username === '' || $pass === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } elseif (!preg_match('/^[A-Za-z0-9_.]{3,64}$/', $username)) {
        $error = 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, _ . และยาว 3–64 ตัว';
    } elseif (mb_strlen($pass) < 6) {
        $error = 'รหัสผ่านอย่างน้อย 6 ตัวอักษร';
    } elseif ($pass !== $pass2) {
        $error = 'รหัสผ่านยืนยันไม่ตรงกัน';
    } else {
        try {
            $srv = server_pdo();
            $srv->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $srv->exec("USE `" . DB_NAME . "`");

            // run schema.sql (strip comment lines, then split on ';')
            $sql   = file_get_contents(__DIR__ . '/schema.sql');
            $lines = array_filter(preg_split('/\r?\n/', $sql), fn($l) => !preg_match('/^\s*--/', $l));
            $sql   = implode("\n", $lines);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '') $srv->exec($stmt);
            }

            if ((int)$srv->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
                $ins = $srv->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?,?,?,'admin')");
                $ins->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $display]);
                $newUserId = (int)$srv->lastInsertId();

                $catIns = $srv->prepare("INSERT INTO categories (user_id, name, color) VALUES (?,?,?)");
                foreach (default_categories() as $catName => $catColor) {
                    $catIns->execute([$newUserId, $catName, $catColor]);
                }

                // system "guest" user — owns QR codes created without login (cannot log in)
                $srv->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES ('__guest__', ?, ?, 'staff')")
                    ->execute([password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), 'ผู้ใช้ทั่วไป (ไม่ล็อกอิน)']);
                $guestId = (int)$srv->lastInsertId();
                foreach (default_categories() as $catName => $catColor) {
                    $catIns->execute([$guestId, $catName, $catColor]);
                }
            }
            $done = true;
        } catch (Throwable $ex) {
            $error = 'ติดตั้งไม่สำเร็จ: ' . $ex->getMessage();
        }
    }
}
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดตั้ง · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/app-extra.css')) ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6z" stroke="#fff" stroke-width="2" stroke-linejoin="round"/><path d="M14 14h2.5v2.5H14V14zM17.5 17.5H20V20h-2.5v-2.5zM14 20h2.5M20 14v2.5" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <h1>ติดตั้ง <?= e(APP_NAME) ?></h1>
      <p>ตั้งค่าฐานข้อมูลและสร้างบัญชีผู้ดูแลระบบ</p>
    </div>

    <?php if ($already): ?>
      <div class="auth-msg ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ระบบติดตั้งเรียบร้อยแล้ว
      </div>
      <p class="hint" style="margin-bottom:16px">เพื่อความปลอดภัย แนะนำให้ลบไฟล์ <code>install.php</code> ทิ้ง</p>
      <a class="btn btn-primary btn-block" href="login.php">ไปหน้าเข้าสู่ระบบ</a>
    <?php elseif ($done): ?>
      <div class="auth-msg ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ติดตั้งสำเร็จ! สร้างบัญชีผู้ดูแลเรียบร้อยแล้ว
      </div>
      <p class="hint" style="margin-bottom:16px">เพื่อความปลอดภัย แนะนำให้ลบไฟล์ <code>install.php</code> ทิ้ง</p>
      <a class="btn btn-primary btn-block" href="login.php">เข้าสู่ระบบ</a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="auth-msg err">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
          <?= e($error) ?>
        </div>
      <?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="field">
          <label class="lab">ชื่อผู้ใช้ (สำหรับเข้าสู่ระบบ)</label>
          <input class="input" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="admin" required>
        </div>
        <div class="field">
          <label class="lab">ชื่อที่แสดง</label>
          <input class="input" name="display_name" value="<?= e($_POST['display_name'] ?? '') ?>" placeholder="ผู้ดูแลระบบ">
        </div>
        <div class="field">
          <label class="lab">รหัสผ่าน</label>
          <input class="input" type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" required>
        </div>
        <div class="field">
          <label class="lab">ยืนยันรหัสผ่าน</label>
          <input class="input" type="password" name="password2" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit" style="margin-top:6px">ติดตั้งและสร้างบัญชี</button>
      </form>
    <?php endif; ?>
    <div class="auth-foot"><?= e(APP_NAME) ?> · PHP <?= PHP_VERSION ?></div>
  </div>
</div>
</body>
</html>
