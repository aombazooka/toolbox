<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'tax';
$page_title = 'คำนวณภาษีเงินได้บุคคลธรรมดา';
$page_js = ['tax.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/><path d="M8 13h1.5M8 17h1.5M13 12v6M13 12c1.4 0 2.2.6 2.2 1.5S14.4 15 13 15c1.4 0 2.3.6 2.3 1.5S14.4 18 13 18" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div>
      <h1>คำนวณภาษีเงินได้บุคคลธรรมดา</h1>
      <p>ประมาณการภาษีเงินได้บุคคลธรรมดาแบบขั้นบันได จากเงินได้และค่าลดหย่อนของคุณ อัปเดตผลทันทีที่กรอก</p>
    </div>
  </div>
</div>

<div class="hint tax-notice">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L14.71 3.86a2 2 0 00-3.42 0z" stroke-linejoin="round"/></svg>
  <span><strong>หมายเหตุ:</strong> นี่คือ<strong>การประมาณการเบื้องต้น</strong> อ้างอิงอัตราภาษีขั้นบันไดและเพดานค่าลดหย่อนมาตรฐานสำหรับ<strong>ปีภาษี 2568 (2025)</strong> เท่านั้น กฎเกณฑ์ค่าลดหย่อนและเพดานต่างๆ อาจเปลี่ยนแปลงในแต่ละปี กรุณาตรวจสอบความถูกต้องกับ<strong>กรมสรรพากร</strong> (rd.go.th) ก่อนใช้ยื่นภาษีจริง เครื่องมือนี้ไม่ใช่คำแนะนำทางภาษีหรือบัญชี</span>
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke-linecap="round"/></svg>
        เงินได้
      </div>
      <div class="field">
        <label class="lab">เงินได้ทั้งปี (เงินเดือน/ค่าจ้าง ประเภท 40(1)/(2))</label>
        <input class="input" id="t-income" inputmode="decimal" value="600,000">
      </div>
      <div class="hint" id="t-expense-hint">ค่าใช้จ่าย (หักอัตโนมัติ 50% ไม่เกิน 100,000 บาท): —</div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="3.2"/><path d="M2.5 20c0-3.6 3-6.2 6.5-6.2s6.5 2.6 6.5 6.2" stroke-linecap="round"/><circle cx="17" cy="7.5" r="2.4"/><path d="M15.5 13.2c2.8.4 4.9 2.6 5 5.5" stroke-linecap="round"/></svg>
        ส่วนตัวและครอบครัว
      </div>
      <div class="hint" style="margin-bottom:14px">ค่าลดหย่อนส่วนตัว 60,000 บาท ถูกหักให้อัตโนมัติเสมอ</div>

      <div class="field">
        <label class="lab">คู่สมรส (ไม่มีเงินได้) — ลดหย่อนได้ 60,000 บาท</label>
        <div class="seg" id="t-spouse-seg">
          <button type="button" data-v="0" class="on">ไม่มี</button>
          <button type="button" data-v="1">มี</button>
        </div>
      </div>

      <div class="row2" style="margin-top:14px">
        <div class="field">
          <label class="lab">จำนวนบุตร<small style="display:block;font-weight:400;color:var(--text-3)">30,000 บาท/คน</small></label>
          <input class="input" id="t-children" inputmode="numeric" value="0">
        </div>
        <div class="field">
          <label class="lab">บิดามารดา (อุปการะ)<small style="display:block;font-weight:400;color:var(--text-3)">30,000 บาท/คน สูงสุด 4 คน</small></label>
          <input class="input" id="t-parents" inputmode="numeric" value="0">
        </div>
      </div>
      <div class="hint" id="t-parents-hint" style="margin-top:6px"></div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 3.6v5.2c0 5-3.4 8.9-8 10.2-4.6-1.3-8-5.2-8-10.2V5.6L12 2z" stroke-linejoin="round"/></svg>
        ประกันและเงินออม
      </div>
      <div class="field">
        <label class="lab">เงินสมทบประกันสังคม<small style="display:block;font-weight:400;color:var(--text-3)">ตามจริง สูงสุด 9,000 บาท</small></label>
        <input class="input" id="t-social" inputmode="decimal" value="9,000">
      </div>
      <div class="hint" id="t-social-hint" style="margin-top:6px"></div>

      <div class="row2" style="margin-top:14px">
        <div class="field">
          <label class="lab">เบี้ยประกันชีวิต<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 100,000 บาท</small></label>
          <input class="input" id="t-life" inputmode="decimal" value="0">
        </div>
        <div class="field">
          <label class="lab">เบี้ยประกันสุขภาพ<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 25,000 บาท</small></label>
          <input class="input" id="t-health" inputmode="decimal" value="0">
        </div>
      </div>
      <div class="hint" id="t-insurance-hint" style="margin-top:6px">รวมประกันชีวิต+สุขภาพหักได้ไม่เกิน 100,000 บาท</div>

      <div class="row2" style="margin-top:14px">
        <div class="field">
          <label class="lab">กองทุน SSF<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 200,000 บาท และไม่เกิน 30% ของเงินได้</small></label>
          <input class="input" id="t-ssf" inputmode="decimal" value="0">
        </div>
        <div class="field">
          <label class="lab">กองทุน RMF<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 500,000 บาท และไม่เกิน 30% ของเงินได้</small></label>
          <input class="input" id="t-rmf" inputmode="decimal" value="0">
        </div>
      </div>
      <div class="hint" id="t-fund-hint" style="margin-top:6px"></div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10l9-7 9 7" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 9v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        อื่นๆ
      </div>
      <div class="field">
        <label class="lab">ดอกเบี้ยกู้ยืมเพื่อที่อยู่อาศัย<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 100,000 บาท</small></label>
        <input class="input" id="t-mortgage" inputmode="decimal" value="0">
      </div>
      <div class="field" style="margin-top:14px">
        <label class="lab">เงินบริจาค<small style="display:block;font-weight:400;color:var(--text-3)">สูงสุด 10% ของเงินได้หลังหักค่าลดหย่อนอื่น</small></label>
        <input class="input" id="t-donation" inputmode="decimal" value="0">
      </div>
      <div class="hint" id="t-other-hint" style="margin-top:6px"></div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <div class="card card-pad">
      <div class="sec-title" style="margin-bottom:2px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H7" stroke-linecap="round"/></svg>
        ภาษีที่ต้องชำระทั้งปี (โดยประมาณ)
      </div>
      <div class="tax-hero" id="t-tax-total">0 บาท</div>
      <div class="stats stats-2" style="margin-top:8px;margin-bottom:0">
        <div class="stat">
          <div class="sv" id="t-net-income">—</div>
          <div class="sl">เงินได้สุทธิ</div>
        </div>
        <div class="stat">
          <div class="sv" id="t-effective-rate">—</div>
          <div class="sl">อัตราภาษีเฉลี่ย (Effective Rate)</div>
        </div>
      </div>
    </div>

    <div class="card loan-table-card">
      <div class="form-section" style="border-bottom:1px solid var(--border)">
        <div class="sec-title" style="margin-bottom:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 10h18M3 14h18M8 4v16M16 4v16" stroke-linecap="round"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
          ตารางภาษีขั้นบันได
        </div>
      </div>
      <div class="amort-wrap" id="t-bracket-wrap">
        <table class="amort-table" id="t-bracket-table">
          <thead>
            <tr>
              <th>ช่วงเงินได้สุทธิ</th>
              <th>อัตรา</th>
              <th>เงินได้ในขั้นนี้</th>
              <th>ภาษีในขั้นนี้</th>
            </tr>
          </thead>
          <tbody id="t-bracket-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
