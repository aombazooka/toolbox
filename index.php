<?php
require_once __DIR__ . '/includes/auth.php';
require_installed();
$active     = 'hub';
$page_title = 'เครื่องมือทั้งหมด';
$page_js    = ['hub.js'];
include __DIR__ . '/includes/header.php';

$tools = require __DIR__ . '/includes/tools.php';

// Fixed category order (per TOOLBOX-PLAN §2); any future category not listed
// here still renders, just after these four.
$catOrder = ['QR & ลิงก์', 'รูปภาพ & PDF', 'เครื่องคำนวณ', 'ตัวช่วย & สุ่ม'];
$groups = [];
foreach ($tools as $t) {
    $groups[$t['cat']][] = $t;
}
$orderedCats = array_values(array_unique(array_merge(
    array_intersect($catOrder, array_keys($groups)),
    array_keys($groups)
)));
?>
<section class="hub-hero">
  <h1><?= e(APP_NAME) ?></h1>
  <p>เครื่องมือออนไลน์ ฟรี ใช้ง่าย ไม่ต้องล็อกอิน</p>
  <div class="hub-search">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/></svg>
    <input type="text" id="hub-q" placeholder="ค้นหาเครื่องมือ... เช่น QR, ย่อลิงก์, แปลงไฟล์" autocomplete="off">
  </div>
</section>

<div id="hub-empty" class="no-result hidden">ไม่พบเครื่องมือที่ตรงกับการค้นหา</div>

<?php foreach ($orderedCats as $cat): ?>
<section class="hub-cat" data-cat="<?= e($cat) ?>">
  <h2 class="hub-cat-title"><?= e($cat) ?></h2>
  <div class="tool-grid">
    <?php foreach ($groups[$cat] as $t): ?>
    <a class="tool-card" href="<?= e($t['href']) ?>" data-search="<?= e(mb_strtolower($t['title'] . ' ' . $t['desc'])) ?>">
      <div class="tool-icon"><?= $t['icon'] ?></div>
      <div class="tool-body">
        <div class="tool-title">
          <?= e($t['title']) ?>
          <?php if (!empty($t['login'])): ?><span class="tool-login-badge" title="ต้องเข้าสู่ระบบสำหรับฟีเจอร์เต็มรูปแบบ">ล็อกอิน</span><?php endif; ?>
        </div>
        <div class="tool-desc"><?= e($t['desc']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
