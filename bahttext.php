<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'bahttext';
$page_title = 'อ่านจำนวนเงินเป็นตัวอักษรไทย';
$page_js = ['bahttext.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 9v.01M18 9v.01M6 15v.01M18 15v.01" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>อ่านจำนวนเงินเป็นตัวอักษรไทย</h1>
      <p>พิมพ์จำนวนเงิน แปลงเป็นคำอ่านภาษาไทยแบบ "...บาทถ้วน" ทันที ตามหลักการอ่านตัวเลขภาษาไทย</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>ตัวเลขทั้งหมดคำนวณในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="field">
        <label class="lab">จำนวนเงิน (บาท)</label>
        <input class="input" id="bt-input" inputmode="decimal" placeholder="เช่น 1250.50" value="1250.50" autocomplete="off">
        <div class="hint">รองรับทศนิยมไม่เกิน 2 ตำแหน่ง (สตางค์) และจำนวนติดลบ — พิมพ์คอมมาคั่นหลักได้ตามสบาย</div>
      </div>

      <div class="field" style="margin-top:18px">
        <label class="lab">ตัวอย่างด่วน</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px" id="bt-examples">
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="0">0</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="21">21</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="101">101</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="1250.50">1,250.50</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="1234567">1,234,567</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="1000001">1,000,001</button>
          <button type="button" class="btn btn-ghost" style="padding:6px 12px;font-size:13px" data-v="0.25">0.25</button>
        </div>
      </div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <div class="card card-pad">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        คำอ่านภาษาไทย
      </div>
      <div class="pwd-display" id="bt-output">—</div>
      <div class="hint" id="bt-detail" style="margin-top:10px">พิมพ์จำนวนเงินด้านซ้ายเพื่อดูคำอ่าน</div>

      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="button" class="btn btn-primary" style="flex:1" id="bt-copy-btn">คัดลอกคำอ่าน</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
