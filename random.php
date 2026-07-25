<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'random';
$page_title = 'สุ่มรายชื่อ / วงล้อสุ่ม / สุ่มเลข / ทอยลูกเต๋า';
$page_js = ['random.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.4" fill="currentColor" stroke="none"/><circle cx="16" cy="8" r="1.4" fill="currentColor" stroke="none"/><circle cx="8" cy="16" r="1.4" fill="currentColor" stroke="none"/><circle cx="16" cy="16" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/></svg>
    </div>
    <div>
      <h1>เครื่องมือสุ่ม</h1>
      <p>สุ่มรายชื่อ หมุนวงล้อสุ่ม สุ่มเลข หรือทอยลูกเต๋า/โยนเหรียญ — สุ่มด้วยตัวสุ่มเข้ารหัส (crypto) เป็นธรรม ไม่มีอคติ</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>ข้อมูลที่กรอกทั้งหมดสุ่มในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        โหมดสุ่ม
      </div>
      <div class="seg" id="mode-tabs">
        <button type="button" data-v="name" class="on">สุ่มรายชื่อ</button>
        <button type="button" data-v="wheel">วงล้อสุ่ม</button>
        <button type="button" data-v="number">สุ่มเลข</button>
        <button type="button" data-v="dice">ลูกเต๋า/เหรียญ</button>
      </div>
    </div>

    <!-- NAME PICKER -->
    <div class="form-section tab-panel" id="panel-name">
      <div class="field">
        <label class="lab">รายชื่อ (บรรทัดละ 1 ชื่อ)</label>
        <textarea class="input" id="name-list" rows="8" placeholder="สมชาย
สมหญิง
มานี
มานะ
ปิติ">สมชาย
สมหญิง
มานี
มานะ
ปิติ
ชูใจ
วีระ
ศรีใจ
ดวงใจ
ประเสริฐ</textarea>
      </div>
      <div class="row2">
        <div class="field">
          <label class="lab">จำนวนที่จะสุ่ม</label>
          <input class="input" id="name-count" inputmode="numeric" value="1">
        </div>
        <div class="field">
          <label class="lab">&nbsp;</label>
          <div class="toggle-row" id="name-repeat-row" style="height:38px">
            <span class="tr-txt">สุ่มซ้ำได้<small>เลือกชื่อเดิมซ้ำได้</small></span>
            <span class="sw-toggle" id="name-repeat-toggle"></span>
          </div>
        </div>
      </div>
      <div class="hint">ค่าเริ่มต้น: สุ่มแบบไม่ซ้ำ (หยิบออกทีละคน แต่ละชื่อถูกเลือกได้ครั้งเดียว)</div>
    </div>

    <!-- WHEEL -->
    <div class="form-section tab-panel hidden" id="panel-wheel">
      <div class="field">
        <label class="lab">รายชื่อ/ตัวเลือก (บรรทัดละ 1 รายการ)</label>
        <textarea class="input" id="wheel-list" rows="8" placeholder="พิซซ่า
ส้มตำ
ก๋วยเตี๋ยว
ข้าวมันไก่">พิซซ่า
ส้มตำ
ก๋วยเตี๋ยว
ข้าวมันไก่
ชาบู
ข้าวผัด</textarea>
      </div>
      <div class="hint">ใส่อย่างน้อย 2 รายการ วงล้อจะอัปเดตทันทีที่แก้ไขรายการ</div>
    </div>

    <!-- NUMBER PICKER -->
    <div class="form-section tab-panel hidden" id="panel-number">
      <div class="row3">
        <div class="field">
          <label class="lab">ค่าต่ำสุด</label>
          <input class="input" id="num-min" inputmode="numeric" value="1">
        </div>
        <div class="field">
          <label class="lab">ค่าสูงสุด</label>
          <input class="input" id="num-max" inputmode="numeric" value="100">
        </div>
        <div class="field">
          <label class="lab">จำนวนที่สุ่ม</label>
          <input class="input" id="num-count" inputmode="numeric" value="1">
        </div>
      </div>
      <div class="toggle-row" id="num-unique-row">
        <span class="tr-txt">ไม่สุ่มซ้ำ<small>แต่ละเลขจะออกได้ครั้งเดียวเท่านั้น</small></span>
        <span class="sw-toggle" id="num-unique-toggle"></span>
      </div>
    </div>

    <!-- DICE / COIN -->
    <div class="form-section tab-panel hidden" id="panel-dice">
      <div class="field">
        <label class="lab">ชนิด</label>
        <div class="seg" id="dice-sub-tabs">
          <button type="button" data-v="die" class="on">ลูกเต๋า</button>
          <button type="button" data-v="coin">เหรียญ</button>
        </div>
      </div>
      <div class="field" id="dice-count-field">
        <label class="lab">จำนวนลูกเต๋า</label>
        <input class="input" id="dice-count" inputmode="numeric" value="5">
      </div>
      <div class="hint">ทอยลูกเต๋า 6 หน้า หรือโยนเหรียญ ได้ครั้งละหลายลูก/หลายเหรียญ</div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <!-- Name result -->
    <div class="card card-pad result-panel" id="result-name">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ผู้ถูกสุ่มเลือก
      </div>
      <div class="rnd-empty" id="name-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6" stroke-linecap="round"/></svg>
        <div>วางรายชื่อด้านซ้าย แล้วกด &quot;สุ่มเลย&quot; เพื่อดูผล</div>
      </div>
      <div class="chip-list hidden" id="name-winners"></div>
      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="button" class="btn btn-primary" style="flex:1" id="name-draw-btn">สุ่มเลย</button>
        <button type="button" class="btn btn-ghost" id="name-copy-btn">คัดลอก</button>
      </div>
    </div>

    <!-- Wheel result -->
    <div class="card card-pad result-panel hidden" id="result-wheel">
      <div class="wheel-stage">
        <div class="wheel-svg-wrap" id="wheel-svg-wrap">
          <svg viewBox="0 0 300 300">
            <g id="wheel-rotor"></g>
            <circle class="wheel-hub" cx="150" cy="150" r="14"/>
            <path class="wheel-pointer" d="M150,32 L132,2 L168,2 Z" fill="var(--accent-strong)"/>
          </svg>
        </div>
        <div class="rnd-empty hidden" id="wheel-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3v9l6 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <div>ใส่อย่างน้อย 2 รายการด้านซ้ายเพื่อสร้างวงล้อ</div>
        </div>
        <div class="wheel-winner-box">
          <div class="ww-label" id="wheel-winner-label"></div>
          <div class="ww-name" id="wheel-winner"></div>
        </div>
        <button type="button" class="btn btn-primary btn-block" id="wheel-spin-btn">หมุนวงล้อ!</button>
      </div>
    </div>

    <!-- Number result -->
    <div class="card card-pad result-panel hidden" id="result-number">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        เลขที่สุ่มได้
      </div>
      <div class="rnd-empty" id="num-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18" stroke-linecap="round"/></svg>
        <div>กำหนดช่วงตัวเลขด้านซ้าย แล้วกด &quot;สุ่มเลข&quot;</div>
      </div>
      <div class="chip-list hidden" id="num-winners"></div>
      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="button" class="btn btn-primary" style="flex:1" id="num-draw-btn">สุ่มเลข</button>
        <button type="button" class="btn btn-ghost" id="num-copy-btn">คัดลอก</button>
      </div>
    </div>

    <!-- Dice/coin result -->
    <div class="card card-pad result-panel hidden" id="result-dice">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ผลการทอย/โยน
      </div>
      <div class="rnd-empty" id="dice-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="3"/><circle cx="9" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="15" r="1" fill="currentColor" stroke="none"/></svg>
        <div>กดปุ่มด้านล่างเพื่อทอยลูกเต๋าหรือโยนเหรียญ</div>
      </div>
      <div class="dice-row hidden" id="dice-results"></div>
      <div class="dice-sum hidden" id="dice-sum"></div>
      <button type="button" class="btn btn-primary btn-block" id="dice-roll-btn" style="margin-top:18px">ทอยลูกเต๋า</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
