<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$active     = '';
$page_title = 'ตั้งค่าบัญชี';
$page_js    = ['settings.js'];

$profile_msg = '';  $profile_ok = false;
$password_msg = ''; $password_ok = false;

$pdo = db();
$st = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$st->execute([uid()]);
$row = $st->fetch();

if (!$row) {
    // Shouldn't happen, but guard against a stale session pointing at a deleted user.
    logout_user();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        if (($_POST['action'] ?? '') === 'password') {
            $password_msg = 'เซสชันหมดอายุ กรุณาลองใหม่';
        } else {
            $profile_msg = 'เซสชันหมดอายุ กรุณาลองใหม่';
        }
    } elseif (($_POST['action'] ?? '') === 'profile') {
        $display_name = trim($_POST['display_name'] ?? '');
        if ($display_name === '') $display_name = $row['username'];
        $up = $pdo->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $up->execute([$display_name, uid()]);
        $row['display_name'] = $display_name;
        $_SESSION['user']['display_name'] = $display_name;
        $profile_ok  = true;
        $profile_msg = 'บันทึกข้อมูลบัญชีแล้ว';
    } elseif (($_POST['action'] ?? '') === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $row['password_hash'])) {
            $password_msg = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (mb_strlen($new) < 6) {
            $password_msg = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        } elseif ($new !== $confirm) {
            $password_msg = 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $up->execute([$hash, uid()]);
            $row['password_hash'] = $hash;
            $password_ok  = true;
            $password_msg = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        }
    }
}

$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>ตั้งค่าบัญชี</h1>
  <p>แก้ไขชื่อที่แสดงและเปลี่ยนรหัสผ่านของคุณ</p>
</div>

<?php if (($row['role'] ?? '') === 'admin' && file_exists(__DIR__ . '/install.php')): ?>
  <div class="auth-msg err" style="max-width:520px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
    พบไฟล์ install.php ในระบบ — แนะนำให้ลบเพื่อความปลอดภัย
  </div>
<?php endif; ?>

<div style="max-width:520px;display:flex;flex-direction:column;gap:20px">

  <div class="card">
    <div class="card-pad">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        ข้อมูลบัญชี
      </div>

      <?php if ($profile_msg): ?>
        <div class="auth-msg <?= $profile_ok ? 'ok' : 'err' ?>">
          <?php if ($profile_ok): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
          <?php endif; ?>
          <?= e($profile_msg) ?>
        </div>
      <?php endif; ?>

      <div class="field">
        <label class="lab">ชื่อผู้ใช้</label>
        <input class="input" value="<?= e($row['username']) ?>" disabled>
      </div>
      <div class="field">
        <label class="lab">สิทธิ์การใช้งาน</label>
        <input class="input" value="<?= e($row['role'] === 'admin' ? 'ผู้ดูแลระบบ' : 'พนักงาน') ?>" disabled>
      </div>
      <div class="field">
        <label class="lab">สร้างบัญชีเมื่อ</label>
        <input class="input" value="<?= e(thai_date($row['created_at'])) ?>" disabled>
      </div>

      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="profile">
        <div class="field">
          <label class="lab">ชื่อที่แสดง</label>
          <input class="input" name="display_name" value="<?= e($row['display_name']) ?>" maxlength="100">
        </div>
        <button class="btn btn-primary" type="submit">บันทึกข้อมูลบัญชี</button>
      </form>

      <?php if ($row['role'] === 'admin'): ?>
        <p class="hint" style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
          <a class="btn btn-ghost" href="migrate.php">อัปเดตฐานข้อมูล</a>
          <a class="btn btn-ghost" href="users_admin.php">จัดการผู้ใช้</a>
          <a class="btn btn-ghost" href="audit.php">บันทึกการใช้งาน (Audit log)</a>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-pad">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.6 13.4L12 22l-9-9V4a1 1 0 0 1 1-1h9l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.2"/></svg>
        จัดการหมวดหมู่
      </div>
      <div class="hint" style="margin-bottom:14px">หมวดหมู่ที่นี่จะแสดงเป็นตัวเลือกในหน้าสร้าง QR และตัวกรองในหน้าประวัติ ลบหมวดหมู่ไม่กระทบ QR ที่บันทึกไว้แล้ว (ยังคงชื่อหมวดเดิมไว้)</div>

      <div id="cat-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">
        <div class="hint">กำลังโหลด...</div>
      </div>

      <form id="cat-add-form" autocomplete="off" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div class="field" style="margin:0;flex:1;min-width:160px">
          <label class="lab">ชื่อหมวดหมู่ใหม่</label>
          <input class="input" id="cat-name" placeholder="เช่น ใบเสร็จ" maxlength="100">
        </div>
        <div class="field" style="margin:0">
          <label class="lab">สี</label>
          <span class="color-chip"><input type="color" id="cat-color" value="#9098a6"></span>
        </div>
        <button class="btn btn-primary" type="submit">เพิ่มหมวดหมู่</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-pad">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        เปลี่ยนรหัสผ่าน
      </div>

      <?php if ($password_msg): ?>
        <div class="auth-msg <?= $password_ok ? 'ok' : 'err' ?>">
          <?php if ($password_ok): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
          <?php endif; ?>
          <?= e($password_msg) ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="password">
        <div class="field">
          <label class="lab">รหัสผ่านปัจจุบัน</label>
          <input class="input" type="password" name="current_password" required>
        </div>
        <div class="field">
          <label class="lab">รหัสผ่านใหม่</label>
          <input class="input" type="password" name="new_password" minlength="6" required>
        </div>
        <div class="field">
          <label class="lab">ยืนยันรหัสผ่านใหม่</label>
          <input class="input" type="password" name="confirm_password" minlength="6" required>
        </div>
        <button class="btn btn-primary" type="submit">เปลี่ยนรหัสผ่าน</button>
      </form>
    </div>
  </div>

</div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
