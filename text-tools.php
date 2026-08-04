<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'text-tools';
$page_title = 'ชุดเครื่องมือข้อความ';
$page_js = ['text-tools.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h11M4 18h16" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>ชุดเครื่องมือข้อความ</h1>
      <p>นับคำ/ตัวอักษร แปลงตัวพิมพ์ จัดระเบียบ เรียงบรรทัด แปลงเลขไทย↔อารบิก และตรวจเลขบัตรประชาชน — ประมวลผลสดในเบราว์เซอร์ของคุณ</p>
    </div>
  </div>
</div>

<div class="create-grid">
  <!-- TEXT INPUT -->
  <div class="card card-pad">
    <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
      <span>ข้อความทั้งหมดประมวลผลในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
      <div class="sec-title" style="margin-bottom:0">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        ข้อความ
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button type="button" class="btn btn-ghost" id="tt-undo-btn" disabled>ย้อนกลับ</button>
        <button type="button" class="btn btn-ghost" id="tt-copy-btn">คัดลอก</button>
        <button type="button" class="btn btn-ghost" id="tt-clear-btn">ล้าง</button>
      </div>
    </div>

    <textarea class="input" id="tt-text" rows="16" style="font-family:'Inter',monospace;resize:vertical" placeholder="วางหรือพิมพ์ข้อความที่นี่..."></textarea>

    <div class="stats" id="tt-stats" style="grid-template-columns:repeat(auto-fit,minmax(126px,1fr));margin-top:18px;margin-bottom:0">
      <div class="stat"><div class="sv" id="tt-count-chars">0</div><div class="sl">ตัวอักษร (รวมเว้นวรรค)</div></div>
      <div class="stat"><div class="sv" id="tt-count-chars-nospace">0</div><div class="sl">ตัวอักษร (ไม่รวมเว้นวรรค)</div></div>
      <div class="stat"><div class="sv" id="tt-count-words">0</div><div class="sl">คำ (นับจากช่องว่าง)</div></div>
      <div class="stat"><div class="sv" id="tt-count-lines">0</div><div class="sl">บรรทัด</div></div>
      <div class="stat"><div class="sv" id="tt-count-paras">0</div><div class="sl">ย่อหน้า</div></div>
    </div>
    <div class="hint" style="margin-top:10px">
      ภาษาไทยไม่มีการเว้นวรรคระหว่างคำ ตัวนับ "คำ" จึงนับจากช่องว่าง/ขึ้นบรรทัดใหม่ (เหมาะกับข้อความภาษาอังกฤษหรือข้อความที่เว้นวรรคระหว่างคำ) — สำหรับข้อความไทยแนะนำให้ดูค่า "ตัวอักษร" แทน ซึ่งนับทีละอักขระ Unicode หนึ่งหน่วย (สระ/วรรณยุกต์ไทยที่เป็นอักขระแยกต่างหาก เช่น ่ ้ ๊ ๋ ึ ั จะถูกนับเป็นอักขระของตัวเองแยกจากพยัญชนะ)
    </div>
  </div>

  <!-- ACTIONS -->
  <div class="preview-wrap">
    <div class="card card-pad" style="margin-bottom:16px">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h9a4 4 0 010 8H6zM6 12h10a4 4 0 010 8H6z" stroke-linejoin="round"/></svg>
        แปลงตัวพิมพ์
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <button type="button" class="btn btn-ghost" data-case="upper">UPPERCASE</button>
        <button type="button" class="btn btn-ghost" data-case="lower">lowercase</button>
        <button type="button" class="btn btn-ghost" data-case="title">Title Case</button>
        <button type="button" class="btn btn-ghost" data-case="sentence">Sentence case</button>
        <button type="button" class="btn btn-ghost" data-case="toggle">tOGGLE cASE</button>
      </div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 7l-1.4 13.5a2 2 0 01-2 1.5H8.4a2 2 0 01-2-1.5L5 7M3 7h18M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ลบ/จัดระเบียบ
      </div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <button type="button" class="btn btn-ghost btn-block" data-clean="dedupe">ลบบรรทัดซ้ำ</button>
        <button type="button" class="btn btn-ghost btn-block" data-clean="empty">ลบบรรทัดว่าง</button>
        <button type="button" class="btn btn-ghost btn-block" data-clean="trim">ตัดช่องว่างหัว-ท้ายแต่ละบรรทัด</button>
        <button type="button" class="btn btn-ghost btn-block" data-clean="collapse">ยุบช่องว่างซ้ำให้เหลือ 1</button>
        <button type="button" class="btn btn-ghost btn-block" data-clean="nospace">ลบช่องว่างทั้งหมด</button>
      </div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h10M4 12h6M4 17h10" stroke-linecap="round"/><path d="M17 5v14M17 5l3 3M17 19l-3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        เรียงบรรทัด
      </div>
      <div class="toggle-row" id="tt-sort-dedupe-row">
        <span class="tr-txt">ลบซ้ำระหว่างเรียง<small>ตัดบรรทัดที่ซ้ำออกก่อนเรียงลำดับ</small></span>
        <span class="sw-toggle" id="tt-sort-dedupe-toggle"></span>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
        <button type="button" class="btn btn-ghost" data-sort="asc">ก→ฮ / A→Z</button>
        <button type="button" class="btn btn-ghost" data-sort="desc">ฮ→ก / Z→A</button>
        <button type="button" class="btn btn-ghost" data-sort="reverse">กลับด้าน</button>
        <button type="button" class="btn btn-ghost" data-sort="shuffle">สุ่มลำดับ</button>
      </div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 8V6a2 2 0 012-2h2M17 8V6a2 2 0 00-2-2h-2M7 16v2a2 2 0 002 2h2M17 16v2a2 2 0 01-2 2h-2" stroke-linecap="round"/></svg>
        เลขไทย ↔ อารบิก
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-ghost" style="flex:1" data-num="th2ar">๐๑๒ → 012</button>
        <button type="button" class="btn btn-ghost" style="flex:1" data-num="ar2th">012 → ๐๑๒</button>
      </div>
    </div>

    <div class="card card-pad">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M6 16c.5-2 2-3 2.5-3s2 1 2.5 3M13 9h5M13 13h5" stroke-linecap="round"/></svg>
        ตรวจเลขบัตรประชาชน
      </div>
      <div class="field">
        <label class="lab">เลขบัตรประชาชน 13 หลัก</label>
        <input class="input" id="tt-id-input" inputmode="numeric" maxlength="13" placeholder="เช่น 1101700207366">
      </div>
      <div class="pwd-display" id="tt-id-result" style="font-size:15.5px;font-weight:600">กรอกเลข 13 หลักด้านบน</div>
      <div class="hint" style="margin-top:10px">ตรวจสอบด้วยสูตรหลักตรวจสอบ (check digit) มาตรฐาน: นำเลข 12 หลักแรกคูณน้ำหนัก 13 ถึง 2 ตามลำดับแล้วบวกกัน จากนั้นคำนวณ (11 − ผลรวม mod 11) mod 10 ต้องตรงกับหลักที่ 13</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
