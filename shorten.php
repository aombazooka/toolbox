<?php
require_once __DIR__ . '/includes/auth.php';
require_installed();
$isGuest = !is_logged_in();   // guests: shorten + copy + QR only, no "my links" (see api/shorten/create.php)
$active = 'shorten';
$page_title = 'ย่อลิงก์';
$page_js = ['shorten.js'];
$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.07 0l1.93-1.93a5 5 0 00-7.07-7.07L10.5 5.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 00-7.07 0L5 12.93a5 5 0 007.07 7.07l1.49-1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div>
      <h1>ย่อลิงก์ (URL Shortener)</h1>
      <p>แปลงลิงก์ยาวให้สั้นลง ตั้งชื่อเองได้ นับยอดคลิก และทำ QR ต่อได้ทันที</p>
    </div>
  </div>
</div>

<div class="create-grid">
  <!-- FORM -->
  <div class="card card-pad">
    <div class="field">
      <label class="lab">ลิงก์ปลายทาง (URL)</label>
      <input class="input" id="f-url" placeholder="https://example.com/หน้ายาวๆ-ที่อยากย่อ">
    </div>
    <div class="field">
      <label class="lab">ชื่อย่อเอง (ไม่บังคับ)</label>
      <input class="input" id="f-alias" placeholder="เช่น promo2026">
      <div class="hint">ตัวอักษร a-z, 0-9, - และ _ เท่านั้น ยาว 3–32 ตัว — เว้นว่างไว้เพื่อให้สุ่มให้อัตโนมัติ</div>
    </div>
    <div class="field">
      <label class="lab">วันหมดอายุ (ไม่บังคับ)</label>
      <input type="datetime-local" class="input" id="f-expiry">
      <div class="hint">เมื่อถึงวันเวลาที่กำหนด ลิงก์นี้จะไม่เด้งไปปลายทางอีกต่อไป</div>
    </div>
    <button type="button" class="btn btn-primary btn-block" id="btn-shorten" style="margin-top:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 7h3a5 5 0 015 5v0a5 5 0 01-5 5h-3m-2 0H8a5 5 0 01-5-5v0a5 5 0 015-5h3" stroke-linecap="round"/></svg>
      ย่อลิงก์
    </button>
    <?php if ($isGuest): ?>
    <div class="hint" style="margin-top:12px">เข้าสู่ระบบเพื่อดูรายการลิงก์ทั้งหมดของคุณ แก้ไขปลายทาง หรือปิดการใช้งานภายหลังได้</div>
    <?php endif; ?>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap">
    <div class="card">
      <div class="card-pad">
        <div class="sec-title" style="margin-bottom:14px">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          ลิงก์สั้นของคุณ
        </div>

        <div id="result-empty" class="hint" style="padding:24px 4px;text-align:center">
          กรอกลิงก์ปลายทางแล้วกดปุ่ม "ย่อลิงก์" เพื่อรับลิงก์สั้น
        </div>

        <div id="result-body" class="hidden">
          <div class="short-link-box">
            <input id="out-url" readonly>
            <button type="button" class="btn btn-ghost" id="btn-copy">คัดลอก</button>
          </div>
          <div class="hint" id="out-target" style="margin-top:10px"></div>
          <div class="hint" id="out-expiry"></div>

          <button type="button" class="btn btn-ghost btn-block" id="btn-make-qr" style="margin-top:16px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6z" stroke-linejoin="round"/><path d="M14 14h2.5v2.5H14V14zM17.5 17.5H20V20h-2.5v-2.5zM14 20h2.5M20 14v2.5" stroke-linecap="round"/></svg>
            ทำ QR ของลิงก์นี้
          </button>

          <div id="qr-box" class="hidden" style="margin-top:16px">
            <div class="short-qr-stage"><div id="qr-preview"></div></div>
            <div class="preview-actions" style="border-top:none;padding:14px 0 0">
              <button type="button" class="btn btn-primary" id="qr-dl-png"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>PNG</button>
              <button type="button" class="btn btn-ghost" id="qr-dl-svg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>SVG</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!$isGuest): ?>
<section style="margin-top:34px">
  <div style="margin-bottom:14px">
    <h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">ลิงก์ของฉัน</h2>
    <p class="hint" style="margin-top:4px">รายการลิงก์สั้นที่คุณเคยสร้าง พร้อมยอดคลิกและตัวจัดการ</p>
  </div>
  <div class="short-table">
    <div class="short-row head">
      <div>ลิงก์</div>
      <div style="text-align:right">คลิก</div>
      <div class="sr-col-date">สร้างเมื่อ</div>
      <div class="sr-col-acts" style="text-align:right">จัดการ</div>
    </div>
    <div id="my-links-body"><div class="hist-loading">กำลังโหลด...</div></div>
  </div>
</section>
<?php endif; ?>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?>, isLoggedIn: <?= $isGuest ? 'false' : 'true' ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
