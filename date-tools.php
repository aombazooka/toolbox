<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'date-tools';
$page_title = 'อายุ · นับวัน · แปลง พ.ศ.↔ค.ศ.';
$page_js = ['date-tools.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18M8 2v4M16 2v4" stroke-linecap="round"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>อายุ · นับวัน · แปลง พ.ศ.↔ค.ศ.</h1>
      <p>คำนวณอายุ นับวันระหว่างสองวันที่ บวก/ลบวัน และแปลงปี พ.ศ./ค.ศ. — คำนวณสดในเบราว์เซอร์ของคุณ</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>วันที่ทั้งหมดคำนวณในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        โหมด
      </div>
      <div class="seg" id="mode-tabs">
        <button type="button" data-v="age" class="on">อายุ</button>
        <button type="button" data-v="between">นับวัน</button>
        <button type="button" data-v="addsub">บวก/ลบวัน</button>
        <button type="button" data-v="convert">พ.ศ.↔ค.ศ.</button>
      </div>
    </div>

    <!-- AGE -->
    <div class="form-section tab-panel" id="panel-age">
      <div class="row2">
        <div class="field">
          <label class="lab">วันเกิด</label>
          <input type="date" class="input" id="age-birth">
        </div>
        <div class="field">
          <label class="lab">คำนวณ ณ วันที่</label>
          <input type="date" class="input" id="age-asof">
        </div>
      </div>
      <div class="hint">ค่าเริ่มต้นของ "คำนวณ ณ วันที่" คือวันนี้ — เปลี่ยนได้ตามต้องการ</div>
    </div>

    <!-- BETWEEN -->
    <div class="form-section tab-panel hidden" id="panel-between">
      <div class="row2">
        <div class="field">
          <label class="lab">วันที่ A</label>
          <input type="date" class="input" id="between-a">
        </div>
        <div class="field">
          <label class="lab">วันที่ B</label>
          <input type="date" class="input" id="between-b">
        </div>
      </div>
      <div class="hint">สลับลำดับวันที่ A/B ก่อน-หลังได้ ระบบจะแสดงผลต่างเป็นค่าสัมบูรณ์ให้เสมอ</div>
    </div>

    <!-- ADD/SUBTRACT -->
    <div class="form-section tab-panel hidden" id="panel-addsub">
      <div class="field">
        <label class="lab">วันที่เริ่มต้น</label>
        <input type="date" class="input" id="add-start">
      </div>
      <div class="field">
        <label class="lab">จำนวนวัน (ใส่ค่าติดลบเพื่อลบวัน)</label>
        <input class="input" id="add-days" inputmode="numeric" value="7">
      </div>
      <div class="hint">เช่น ใส่ 30 เพื่อบวก 30 วัน หรือ -14 เพื่อลบ 14 วัน จากวันที่เริ่มต้น</div>
    </div>

    <!-- CONVERT BE/CE -->
    <div class="form-section tab-panel hidden" id="panel-convert">
      <div class="field">
        <label class="lab">ทิศทางการแปลงปี</label>
        <div class="seg" id="conv-dir">
          <button type="button" data-v="ce2be" class="on">ค.ศ. → พ.ศ.</button>
          <button type="button" data-v="be2ce">พ.ศ. → ค.ศ.</button>
        </div>
      </div>
      <div class="field">
        <label class="lab" id="conv-year-label">ปี ค.ศ.</label>
        <input class="input" id="conv-year" inputmode="numeric" value="<?= (int)date('Y') ?>">
      </div>
      <div class="hint">พ.ศ. = ค.ศ. + 543</div>

      <div class="field" style="margin-top:18px">
        <label class="lab">แสดงวันที่แบบไทย</label>
        <input type="date" class="input" id="conv-date">
      </div>
      <div class="hint">แปลงวันที่ที่เลือกเป็นรูปแบบไทย เช่น "25 กรกฎาคม 2569"</div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <!-- AGE result -->
    <div class="card card-pad result-panel" id="result-age">
      <div class="stats">
        <div class="stat">
          <div class="sv" id="age-years">—</div>
          <div class="sl">ปี</div>
        </div>
        <div class="stat">
          <div class="sv" id="age-months">—</div>
          <div class="sl">เดือน</div>
        </div>
        <div class="stat">
          <div class="sv" id="age-days">—</div>
          <div class="sl">วัน</div>
        </div>
        <div class="stat">
          <div class="sv" id="age-next">—</div>
          <div class="sl">วันเกิดถัดไปอีก (วัน)</div>
        </div>
      </div>
      <div class="hint" id="age-detail">กรอกวันเกิดด้านซ้ายเพื่อดูผลลัพธ์</div>
    </div>

    <!-- BETWEEN result -->
    <div class="card card-pad result-panel hidden" id="result-between">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="between-days">—</div>
          <div class="sl">จำนวนวันทั้งหมด</div>
        </div>
        <div class="stat">
          <div class="sv" id="between-weeks">—</div>
          <div class="sl">สัปดาห์ + วัน</div>
        </div>
      </div>
      <div class="hint" id="between-detail">กรอกวันที่ A และ B ด้านซ้าย</div>
    </div>

    <!-- ADD/SUBTRACT result -->
    <div class="card card-pad result-panel hidden" id="result-addsub">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        วันที่ผลลัพธ์
      </div>
      <div class="pwd-display" id="add-result-date" style="font-size:22px">—</div>
      <div class="hint" id="add-detail" style="margin-top:10px">กรอกวันที่เริ่มต้นและจำนวนวันด้านซ้าย</div>
    </div>

    <!-- CONVERT result -->
    <div class="card card-pad result-panel hidden" id="result-convert">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="conv-year-out">—</div>
          <div class="sl" id="conv-year-out-label">ผลลัพธ์</div>
        </div>
      </div>
      <div class="hint" id="conv-year-detail" style="margin-bottom:16px">กรอกปีด้านซ้าย</div>

      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
        วันที่แบบไทย
      </div>
      <div class="pwd-display" id="conv-date-out" style="font-size:20px">—</div>
      <div class="hint" id="conv-date-detail" style="margin-top:10px">เลือกวันที่ด้านซ้ายเพื่อแปลงเป็นรูปแบบไทย</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
