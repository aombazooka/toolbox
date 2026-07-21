<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Not installed yet? Send to the installer.
try {
    $installed = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
} catch (Throwable $e) {
    header('Location: install.php');
    exit;
}
if (!$installed) {
    header('Location: install.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่';
    } else {
        $username = trim($_POST['username'] ?? '');
        $display  = trim($_POST['display_name'] ?? '');
        $pass     = $_POST['password'] ?? '';
        $pass2    = $_POST['password2'] ?? '';

        if ($username === '' || $display === '' || $pass === '') {
            $error = 'กรุณากรอกข้อมูลให้ครบ';
        } elseif (!preg_match('/^[A-Za-z0-9_.]{3,64}$/', $username)) {
            $error = 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, _ . และยาว 3–64 ตัว';
        } elseif (strncmp($username, '__', 2) === 0) {
            $error = 'ชื่อผู้ใช้นี้ใช้ไม่ได้ (สงวนไว้สำหรับระบบ)';
        } elseif (mb_strlen($display) > 100) {
            $error = 'ชื่อ-สกุลยาวเกินไป';
        } elseif (mb_strlen($pass) < 6) {
            $error = 'รหัสผ่านอย่างน้อย 6 ตัวอักษร';
        } elseif ($pass !== $pass2) {
            $error = 'รหัสผ่านยืนยันไม่ตรงกัน';
        } else {
            $chk = db()->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ((int)$chk->fetchColumn() > 0) {
                $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว กรุณาเลือกชื่ออื่น';
            } else {
                try {
                    $pdo = db();
                    $pdo->beginTransaction();
                    $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?,?,?,'staff')")
                        ->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $display]);
                    $newId = (int)$pdo->lastInsertId();

                    $catIns = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?,?,?)");
                    foreach (default_categories() as $catName => $catColor) {
                        $catIns->execute([$newId, $catName, $catColor]);
                    }
                    $pdo->commit();

                    login_user(['id' => $newId, 'username' => $username, 'display_name' => $display, 'role' => 'staff']);
                    log_audit('register', 'user', $newId, $username);
                    header('Location: index.php');
                    exit;
                } catch (Throwable $ex) {
                    if (db()->inTransaction()) db()->rollBack();
                    $error = 'สมัครไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
                }
            }
        }
    }
}
$csrf = csrf_token();
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>สมัครสมาชิก · <?= e(APP_NAME) ?></title>
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
      <h1>สมัครสมาชิก</h1>
      <p>สร้างบัญชีเพื่อบันทึกและจัดการ QR ของคุณเอง</p>
    </div>

    <?php if ($error): ?>
      <div class="auth-msg err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <div class="field">
        <label class="lab">ชื่อ-สกุล</label>
        <input class="input" name="display_name" value="<?= e($_POST['display_name'] ?? '') ?>" placeholder="เช่น สมชาย ใจดี" autofocus required>
      </div>
      <div class="field">
        <label class="lab">ชื่อผู้ใช้ (สำหรับเข้าสู่ระบบ)</label>
        <input class="input" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="a-z, 0-9, _ ." required>
      </div>
      <div class="field">
        <label class="lab">รหัสผ่าน</label>
        <input class="input" type="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร" required>
      </div>
      <div class="field">
        <label class="lab">ยืนยันรหัสผ่าน</label>
        <input class="input" type="password" name="password2" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:6px">สมัครสมาชิก</button>
    </form>
    <div class="auth-foot">มีบัญชีอยู่แล้ว? <a href="login.php" style="color:var(--accent-strong);font-weight:600">เข้าสู่ระบบ</a></div>
    <div class="auth-foot"><a href="index.php" style="color:var(--text-2);font-weight:500">&larr; กลับหน้าหลัก</a></div>
  </div>
</div>
</body>
</html>
