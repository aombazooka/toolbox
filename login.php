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
        $pass     = $_POST['password'] ?? '';
        $ipHash   = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|qrstudio');

        // Count failed attempts from this IP in the last 15 minutes — block before
        // touching credentials at all if the caller has already failed too many times.
        // (Guarded: on a system that hasn't run migrate.php yet, login_attempts may not
        // exist — never let that break login itself, just skip throttling.)
        $recentFails = 0;
        try {
            $st = db()->prepare(
                "SELECT COUNT(*) FROM login_attempts
                 WHERE ip_hash = ? AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)"
            );
            $st->execute([$ipHash]);
            $recentFails = (int)$st->fetchColumn();
        } catch (Throwable $e) { /* table not migrated yet — don't block login */ }

        if ($recentFails >= 5) {
            $error = 'พยายามเข้าสู่ระบบผิดหลายครั้งเกินไป กรุณารอสักครู่แล้วลองใหม่';
        } else {
            $st = db()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $st->execute([$username]);
            $row = $st->fetch();
            $success = ($row && password_verify($pass, $row['password_hash']));

            try {
                db()->prepare("INSERT INTO login_attempts (ip_hash, username, success) VALUES (?,?,?)")
                    ->execute([$ipHash, $username !== '' ? $username : null, $success ? 1 : 0]);
            } catch (Throwable $e) { /* table not migrated yet — ignore */ }

            if ($success) {
                login_user($row);
                log_audit('login', 'auth', uid(), $username);
                header('Location: index.php');
                exit;
            }
            log_audit('login_failed', 'auth', null, $username, null);
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
$csrf = csrf_token();
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ · <?= e(APP_NAME) ?></title>
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
      <h1>เข้าสู่ระบบ</h1>
      <p>ยินดีต้อนรับกลับสู่ <?= e(APP_NAME) ?></p>
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
        <label class="lab">ชื่อผู้ใช้</label>
        <input class="input" name="username" value="<?= e($_POST['username'] ?? '') ?>" autofocus required>
      </div>
      <div class="field">
        <label class="lab">รหัสผ่าน</label>
        <input class="input" type="password" name="password" required>
      </div>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:6px">เข้าสู่ระบบ</button>
    </form>
    <div class="auth-foot">ยังไม่มีบัญชี? <a href="register.php" style="color:var(--accent-strong);font-weight:600">สมัครสมาชิก</a></div>
    <div class="auth-foot"><a href="index.php" style="color:var(--text-2);font-weight:500">&larr; กลับหน้าหลัก</a></div>
  </div>
</div>
</body>
</html>
