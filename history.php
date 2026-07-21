<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$active = 'history';
$page_title = 'ประวัติ';
$page_js = ['history.js'];
$csrf = csrf_token();

$catSt = db()->prepare("SELECT name FROM categories WHERE user_id = ? ORDER BY id");
$catSt->execute([uid()]);
$categories = $catSt->fetchAll(PDO::FETCH_COLUMN);
if (!$categories) $categories = array_keys(default_categories());

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>ประวัติการใช้งาน</h1>
  <p>ค้นหา QR ที่เคยสร้าง ดาวน์โหลดซ้ำ เปลี่ยนปลายทาง หรือลบได้ทุกเมื่อ</p>
</div>

<div class="stats">
  <div class="stat"><div class="sv" id="st-total">–</div><div class="sl">QR ทั้งหมด</div></div>
  <div class="stat"><div class="sv" id="st-week">–</div><div class="sl">สร้างใน 7 วัน</div></div>
  <div class="stat"><div class="sv" id="st-cats">–</div><div class="sl">หมวดหมู่</div></div>
  <div class="stat"><div class="sv" id="st-scans">–</div><div class="sl">ยอดสแกนรวม</div></div>
</div>

<div class="hist-toolbar">
  <div class="search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
    <input id="hist-search" placeholder="ค้นหาชื่อเอกสารหรือลิงก์...">
  </div>
  <select class="filter-sel" id="hist-filter">
    <option value="">ทุกหมวดหมู่</option>
    <?php foreach ($categories as $catName): ?>
      <option><?= e($catName) ?></option>
    <?php endforeach; ?>
  </select>
  <a class="btn btn-ghost" id="print-link" href="print.php" style="white-space:nowrap"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>พิมพ์ A4</a>
  <?php if (is_admin()): ?>
  <div class="toggle-row" id="admin-all-toggle" style="white-space:nowrap;cursor:pointer;user-select:none">
    <span class="tr-txt">ดูของทุกคน</span>
    <span class="sw-toggle" id="admin-all-sw"></span>
  </div>
  <?php endif; ?>
</div>

<div class="hist-table">
  <div class="hist-row head">
    <div>QR</div><div>ชื่อ / ลิงก์</div><div>ชนิด / หมวด</div><div class="col-date">วันที่</div><div class="col-acts" style="text-align:right">จัดการ</div>
  </div>
  <div id="hist-body"><div class="hist-loading">กำลังโหลด...</div></div>
</div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?>, isAdmin: <?= json_encode(is_admin()) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
