<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed
$active = 'convert';
$page_title = 'แปลงหน่วย · อุณหภูมิ · สกุลเงิน';
$page_js = ['convert.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16M4 9l3-3M4 9l3 3" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 15H4M20 15l-3-3M20 15l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div>
      <h1>แปลงหน่วย · อุณหภูมิ · สกุลเงิน</h1>
      <p>ความยาว น้ำหนัก พื้นที่ ปริมาตร ความเร็ว ข้อมูล เวลา อุณหภูมิ และสกุลเงิน — แปลงสดทันทีที่พิมพ์</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>หน่วยทั่วไปคำนวณในเบราว์เซอร์ของคุณล้วนๆ ไม่ส่งข้อมูลขึ้นเซิร์ฟเวอร์ — ยกเว้นสกุลเงินที่ต้องดึงอัตราแลกเปลี่ยนผ่านตัวกลางของเราเอง (ไม่เชื่อมต่อ frankfurter.app ตรงจากเบราว์เซอร์คุณ)</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        หมวดหมู่
      </div>
      <div class="seg" id="cat-tabs">
        <button type="button" data-v="length" class="on">ความยาว</button>
        <button type="button" data-v="weight">น้ำหนัก</button>
        <button type="button" data-v="area">พื้นที่</button>
        <button type="button" data-v="volume">ปริมาตร</button>
        <button type="button" data-v="speed">ความเร็ว</button>
        <button type="button" data-v="digital">ข้อมูล</button>
        <button type="button" data-v="time">เวลา</button>
        <button type="button" data-v="temperature">อุณหภูมิ</button>
        <button type="button" data-v="currency">สกุลเงิน</button>
      </div>
    </div>

    <div class="form-section">
      <div class="hint" id="cat-note" style="margin-bottom:16px"></div>

      <div class="field">
        <label class="lab">จาก</label>
        <div class="conv-pair">
          <input class="input" id="from-value" inputmode="decimal" value="1">
          <select class="input" id="from-unit"></select>
        </div>
      </div>

      <div class="conv-swap-wrap">
        <button type="button" class="conv-swap-btn" id="swap-btn" title="สลับหน่วย">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3v14M17 17l-4-4M17 17l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 21V7M7 7L3 11M7 7l4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
      </div>

      <div class="field">
        <label class="lab">เป็น</label>
        <div class="conv-pair">
          <input class="input" id="to-value" inputmode="decimal" value="">
          <select class="input" id="to-unit"></select>
        </div>
      </div>

      <div class="hint hidden" id="currency-status" style="margin-top:14px"></div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap">
    <div class="card card-pad">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        เทียบกับหน่วยอื่นในหมวดนี้
      </div>
      <div class="stats" id="all-units-grid"></div>
      <div class="no-result hidden" id="all-units-empty">กรอกค่าด้านซ้ายเพื่อดูการแปลงหน่วย</div>
    </div>
  </div>
</div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
