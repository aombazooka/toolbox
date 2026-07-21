<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$active     = 'analytics';
$page_title = 'สถิติการสแกน';
$page_js    = ['analytics.js'];
$csrf       = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>สถิติการสแกน</h1>
  <p>ดูยอดสแกน QR ไดนามิกย้อนหลัง 30 วัน และรายละเอียดรายตัว</p>
</div>

<?php if (is_admin()): ?>
<div class="toggle-row" id="admin-all-toggle" style="max-width:220px;margin-bottom:16px;cursor:pointer;user-select:none">
  <span class="tr-txt">ดูของทุกคน</span>
  <span class="sw-toggle" id="admin-all-sw"></span>
</div>
<?php endif; ?>

<div id="an-root"><div class="hist-loading">กำลังโหลด...</div></div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?>, isAdmin: <?= json_encode(is_admin()) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
