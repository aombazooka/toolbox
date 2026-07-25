<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'calc';
$page_title = 'ชุดเครื่องคำนวณ';
$page_js = ['calc.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 6h6" stroke-linecap="round"/><path d="M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>ชุดเครื่องคำนวณ</h1>
      <p>BMI · ส่วนลด · VAT 7% · เปอร์เซ็นต์ · ทิป — เลือกโหมด กรอกตัวเลข ผลลัพธ์คำนวณให้ทันที</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>ข้อมูลที่กรอกทั้งหมดคำนวณในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        โหมดคำนวณ
      </div>
      <div class="seg" id="mode-tabs">
        <button type="button" data-v="bmi" class="on">BMI</button>
        <button type="button" data-v="discount">ส่วนลด</button>
        <button type="button" data-v="vat">VAT</button>
        <button type="button" data-v="percent">เปอร์เซ็นต์</button>
        <button type="button" data-v="tip">ทิป</button>
      </div>
    </div>

    <!-- BMI -->
    <div class="form-section tab-panel" id="panel-bmi">
      <div class="row2">
        <div class="field">
          <label class="lab">น้ำหนัก (กก.)</label>
          <input class="input" id="bmi-weight" inputmode="decimal" value="70">
        </div>
        <div class="field">
          <label class="lab">ส่วนสูง (ซม.)</label>
          <input class="input" id="bmi-height" inputmode="decimal" value="170">
        </div>
      </div>
      <div class="hint">ใช้เกณฑ์ BMI มาตรฐานเอเชีย/กระทรวงสาธารณสุข (ต่างจากเกณฑ์สากลตะวันตกที่ 25/30)</div>
    </div>

    <!-- Discount -->
    <div class="form-section tab-panel hidden" id="panel-discount">
      <div class="row2">
        <div class="field">
          <label class="lab">ราคาสินค้า (บาท)</label>
          <input class="input" id="disc-price" inputmode="decimal" value="1,000">
        </div>
        <div class="field">
          <label class="lab">ส่วนลด (%)</label>
          <input class="input" id="disc-percent" inputmode="decimal" value="20">
        </div>
      </div>
    </div>

    <!-- VAT -->
    <div class="form-section tab-panel hidden" id="panel-vat">
      <div class="field">
        <label class="lab">ทิศทางการคำนวณ</label>
        <div class="seg" id="vat-dir">
          <button type="button" data-v="add" class="on">บวก VAT เข้าไป</button>
          <button type="button" data-v="extract">ถอด VAT ออกมา</button>
        </div>
        <div class="hint" id="vat-hint" style="margin-top:8px">ราคาที่กรอกยังไม่รวม VAT — ระบบจะบวก VAT 7% ให้</div>
      </div>
      <div class="field">
        <label class="lab" id="vat-amount-label">ราคาก่อน VAT (บาท)</label>
        <input class="input" id="vat-amount" inputmode="decimal" value="100">
      </div>
    </div>

    <!-- Percent -->
    <div class="form-section tab-panel hidden" id="panel-percent">
      <div class="field">
        <label class="lab">รูปแบบคำถาม</label>
        <div class="seg" id="percent-mode">
          <button type="button" data-v="ratio" class="on">X เป็นกี่% ของ Y</button>
          <button type="button" data-v="change">เพิ่ม/ลด Y กี่%</button>
          <button type="button" data-v="base">X คือ N% ของเลขอะไร</button>
        </div>
      </div>

      <div id="percent-fields-ratio">
        <div class="row2">
          <div class="field">
            <label class="lab">X</label>
            <input class="input" id="pr-x" inputmode="decimal" value="25">
          </div>
          <div class="field">
            <label class="lab">Y</label>
            <input class="input" id="pr-y" inputmode="decimal" value="200">
          </div>
        </div>
        <div class="hint">คำนวณ: X เป็นกี่เปอร์เซ็นต์ของ Y</div>
      </div>

      <div id="percent-fields-change" class="hidden">
        <div class="row2">
          <div class="field">
            <label class="lab">ค่าเริ่มต้น (Y)</label>
            <input class="input" id="pc-y" inputmode="decimal" value="200">
          </div>
          <div class="field">
            <label class="lab">เปอร์เซ็นต์ (%)</label>
            <input class="input" id="pc-x" inputmode="decimal" value="15">
          </div>
        </div>
        <div class="field">
          <label class="lab">การเปลี่ยนแปลง</label>
          <div class="seg" id="pc-dir">
            <button type="button" data-v="inc" class="on">เพิ่มขึ้น</button>
            <button type="button" data-v="dec">ลดลง</button>
          </div>
        </div>
        <div class="hint">คำนวณ: เพิ่ม/ลดค่าเริ่มต้นด้วยเปอร์เซ็นต์ที่กำหนด</div>
      </div>

      <div id="percent-fields-base" class="hidden">
        <div class="row2">
          <div class="field">
            <label class="lab">X (ค่าที่ทราบ)</label>
            <input class="input" id="pb-x" inputmode="decimal" value="12">
          </div>
          <div class="field">
            <label class="lab">N (%)</label>
            <input class="input" id="pb-n" inputmode="decimal" value="25">
          </div>
        </div>
        <div class="hint">คำนวณ: X คือ N% ของเลขอะไร (หาค่าตั้งต้นทั้งหมด)</div>
      </div>
    </div>

    <!-- Tip -->
    <div class="form-section tab-panel hidden" id="panel-tip">
      <div class="row2">
        <div class="field">
          <label class="lab">ยอดบิล (บาท)</label>
          <input class="input" id="tip-bill" inputmode="decimal" value="500">
        </div>
        <div class="field">
          <label class="lab">ทิป (%)</label>
          <input class="input" id="tip-percent" inputmode="decimal" value="10">
        </div>
      </div>
      <div class="field">
        <label class="lab">หารกี่คน</label>
        <input class="input" id="tip-split" inputmode="numeric" value="4">
      </div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <!-- BMI result -->
    <div class="card card-pad result-panel" id="result-bmi">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="bmi-value">—</div>
          <div class="sl">ค่า BMI</div>
        </div>
        <div class="stat">
          <div class="sv" id="bmi-category">—</div>
          <div class="sl">เกณฑ์สุขภาพ</div>
        </div>
      </div>
      <div class="hint" id="bmi-detail">กรอกน้ำหนักและส่วนสูงด้านซ้ายเพื่อดูผล BMI</div>
    </div>

    <!-- Discount result -->
    <div class="card card-pad result-panel hidden" id="result-discount">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="disc-final">—</div>
          <div class="sl">ราคาหลังหักส่วนลด</div>
        </div>
        <div class="stat">
          <div class="sv" id="disc-saved">—</div>
          <div class="sl">ประหยัดไป</div>
        </div>
      </div>
      <div class="hint" id="disc-detail">กรอกราคาและส่วนลดด้านซ้าย</div>
    </div>

    <!-- VAT result -->
    <div class="card card-pad result-panel hidden" id="result-vat">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="vat-pre">—</div>
          <div class="sl">ราคาก่อน VAT</div>
        </div>
        <div class="stat">
          <div class="sv" id="vat-amount-out">—</div>
          <div class="sl">VAT 7%</div>
        </div>
      </div>
      <div class="stats stats-2" style="margin-top:14px;margin-bottom:0">
        <div class="stat" style="grid-column:1/-1">
          <div class="sv" id="vat-total">—</div>
          <div class="sl">ราคารวม VAT</div>
        </div>
      </div>
      <div class="hint" id="vat-detail" style="margin-top:14px">กรอกจำนวนเงินด้านซ้าย</div>
    </div>

    <!-- Percent result -->
    <div class="card card-pad result-panel hidden" id="result-percent">
      <div class="stats stats-2">
        <div class="stat">
          <div class="sv" id="percent-value">—</div>
          <div class="sl" id="percent-label">ผลลัพธ์</div>
        </div>
        <div class="stat">
          <div class="sv" id="percent-secondary">—</div>
          <div class="sl" id="percent-secondary-label">ส่วนต่าง</div>
        </div>
      </div>
      <div class="hint" id="percent-detail">กรอกตัวเลขด้านซ้าย</div>
    </div>

    <!-- Tip result -->
    <div class="card card-pad result-panel hidden" id="result-tip">
      <div class="stats" style="grid-template-columns:repeat(3,1fr)">
        <div class="stat">
          <div class="sv" id="tip-amount">—</div>
          <div class="sl">ค่าทิป</div>
        </div>
        <div class="stat">
          <div class="sv" id="tip-total">—</div>
          <div class="sl">ยอดรวมทั้งหมด</div>
        </div>
        <div class="stat">
          <div class="sv" id="tip-per-person">—</div>
          <div class="sl">ต่อคน</div>
        </div>
      </div>
      <div class="hint" id="tip-detail">กรอกยอดบิล เปอร์เซ็นต์ทิป และจำนวนคนด้านซ้าย</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
