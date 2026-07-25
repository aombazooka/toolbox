<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'calc-loan';
$page_title = 'คำนวณผ่อน/สินเชื่อ';
$page_js = ['calc-loan.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18" stroke-linecap="round"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>คำนวณผ่อน/สินเชื่อ</h1>
      <p>คำนวณค่างวดรายเดือน ดอกเบี้ยรวม และตารางผ่อนชำระ ทั้งแบบลดต้นลดดอกและแบบคงที่</p>
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
        ข้อมูลเงินกู้
      </div>

      <div class="field">
        <label class="lab">เงินต้น (บาท)</label>
        <input class="input" id="f-principal" inputmode="decimal" value="100,000">
      </div>
      <div class="row2">
        <div class="field">
          <label class="lab">ดอกเบี้ย (% ต่อปี)</label>
          <input class="input" id="f-rate" inputmode="decimal" value="12">
        </div>
        <div class="field">
          <label class="lab">จำนวนงวด (เดือน)</label>
          <input class="input" id="f-months" inputmode="numeric" value="12">
        </div>
      </div>

      <div class="field" style="margin-top:4px">
        <label class="lab">แบบคิดดอกเบี้ย</label>
        <div class="seg" id="method-tabs">
          <button type="button" data-v="reducing" class="on">ลดต้นลดดอก</button>
          <button type="button" data-v="flat">คงที่ (Flat)</button>
        </div>
        <div class="hint" id="method-hint" style="margin-top:8px">ดอกเบี้ยคิดจากยอดคงเหลือแต่ละงวด (แบบที่สินเชื่อบ้าน/รถส่วนใหญ่ใช้) — ค่างวดคงที่ทุกเดือน</div>
      </div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <div class="stats" id="loan-stats">
      <div class="stat">
        <div class="sv" id="st-payment">—</div>
        <div class="sl">ค่างวดต่อเดือน</div>
      </div>
      <div class="stat">
        <div class="sv" id="st-interest">—</div>
        <div class="sl">ดอกเบี้ยรวม</div>
      </div>
      <div class="stat">
        <div class="sv" id="st-total">—</div>
        <div class="sl">ยอดชำระรวม</div>
      </div>
    </div>

    <div class="card loan-table-card">
      <div class="form-section" style="border-bottom:1px solid var(--border)">
        <div class="sec-title" style="margin-bottom:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h18M3 14h18M8 4v16M16 4v16" stroke-linecap="round"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
          ตารางผ่อนชำระ (Amortization)
        </div>
      </div>
      <div class="amort-wrap" id="amort-wrap">
        <table class="amort-table" id="amort-table">
          <thead>
            <tr>
              <th>งวดที่</th>
              <th>ค่างวด</th>
              <th>เงินต้น</th>
              <th>ดอกเบี้ย</th>
              <th>คงเหลือ</th>
            </tr>
          </thead>
          <tbody id="amort-body"></tbody>
        </table>
        <div class="no-result hidden" id="amort-empty">กรอกข้อมูลด้านซ้ายให้ครบเพื่อดูตารางผ่อนชำระ</div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
