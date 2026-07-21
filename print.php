<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$active = '';
$page_title = 'พิมพ์แผ่น A4';
$page_js = ['print.js'];
$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head no-print">
  <h1>พิมพ์แผ่น A4</h1>
  <p>จัด QR หลายอันลงกระดาษ A4 พร้อมป้ายกำกับ ตัดแปะติดเอกสารได้เลย</p>
</div>

<div class="print-bar no-print">
  <div class="pb-group">
    <span class="pb-lab">คอลัมน์</span>
    <div class="seg" id="cols">
      <button type="button" data-v="2">2</button>
      <button type="button" data-v="3" class="on">3</button>
      <button type="button" data-v="4">4</button>
      <button type="button" data-v="5">5</button>
    </div>
  </div>
  <label class="pb-check"><input type="checkbox" id="opt-labels" checked> แสดงชื่อ</label>
  <label class="pb-check"><input type="checkbox" id="opt-lines" checked> เส้นตัด</label>
  <label class="pb-check"><input type="checkbox" id="opt-link"> แสดงลิงก์</label>
  <div class="pb-spacer"></div>
  <a class="btn btn-ghost" href="history.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>กลับ</a>
  <button type="button" class="btn btn-primary" id="do-print"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>พิมพ์</button>
</div>

<div class="print-sheet">
  <div class="p-grid" id="print-grid" style="--cols:3"><div class="hist-loading">กำลังโหลด...</div></div>
</div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
