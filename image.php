<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'image';
$page_title = 'ลดขนาด/แปลงรูปภาพ';
$page_js = ['image.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <div>
      <h1>ลดขนาด/บีบอัดรูป + แปลงไฟล์</h1>
      <p>ย่อขนาด บีบอัด และแปลงไฟล์ภาพหลายไฟล์พร้อมกัน (JPG / PNG / WebP) ได้ทันที</p>
    </div>
  </div>
</div>

<div class="create-grid">
  <!-- INPUT -->
  <div class="card card-pad">
    <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:18px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
      <span>ไฟล์รูปทั้งหมดประมวลผลในเบราว์เซอร์ของคุณเท่านั้น <strong>ไม่มีการอัปโหลดขึ้นเซิร์ฟเวอร์</strong> หรือบันทึกที่ใดทั้งสิ้น</span>
    </div>

    <div id="img-drop" class="img-drop">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17.5A4.5 4.5 0 017.5 8.6 6 6 0 0119 10.5a4 4 0 01-.5 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12v7m0-7l-2.5 2.5M12 12l2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <div class="t">ลากรูปมาวางที่นี่ หรือ <span class="link">คลิกเพื่อเลือกไฟล์</span></div>
      <div class="s">เลือกได้หลายไฟล์ · JPG, PNG, WebP, GIF ฯลฯ</div>
      <input type="file" id="img-input" accept="image/*" multiple hidden>
    </div>

    <div class="form-section" style="padding:20px 0 0;border:none">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v4a1 1 0 01-1 1H3M16 3v4a1 1 0 001 1h4M8 21v-4a1 1 0 00-1-1H3M16 21v-4a1 1 0 011-1h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ปรับขนาด
      </div>
      <div class="seg" id="resize-mode">
        <button type="button" data-v="px" class="on">ขนาดสูงสุด (px)</button>
        <button type="button" data-v="percent">เปอร์เซ็นต์ (%)</button>
      </div>
      <div class="field" id="resize-px-field" style="margin-top:14px">
        <label class="lab">ด้านที่ยาวที่สุดไม่เกิน <span id="maxdim-val" style="color:var(--text-3)">1920</span> px</label>
        <input type="range" id="opt-maxdim" min="100" max="4000" step="10" value="1920">
        <div class="hint">ถ้ารูปเล็กกว่าค่านี้อยู่แล้ว จะไม่ขยายให้ใหญ่ขึ้น</div>
      </div>
      <div class="field hidden" id="resize-percent-field" style="margin-top:14px">
        <label class="lab">ย่อเหลือ <span id="percent-val" style="color:var(--text-3)">100</span>% ของขนาดเดิม</label>
        <input type="range" id="opt-percent" min="1" max="100" step="1" value="100">
      </div>
    </div>

    <div class="form-section" style="padding:20px 0;border-top:1px solid var(--border);border-bottom:none">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10" stroke-linecap="round"/></svg>
        ฟอร์แมตไฟล์ออก
      </div>
      <div class="seg" id="out-format">
        <button type="button" data-v="original" class="on">เดิม</button>
        <button type="button" data-v="jpg">JPG</button>
        <button type="button" data-v="png">PNG</button>
        <button type="button" data-v="webp">WebP</button>
      </div>
      <div class="field" id="quality-field" style="margin-top:14px">
        <label class="lab">คุณภาพ (ใช้กับ JPG / WebP) <span id="quality-val" style="color:var(--text-3)">80</span>%</label>
        <input type="range" id="opt-quality" min="10" max="100" step="1" value="80">
      </div>
      <div class="hint">ไฟล์ GIF/เคลื่อนไหวจะถูกแปลงเป็นภาพนิ่งเมื่อเลือกฟอร์แมตอื่นที่ไม่ใช่ "เดิม"</div>
    </div>

    <button type="button" class="btn btn-primary btn-block" id="btn-process" disabled style="margin-top:6px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v4a1 1 0 01-1 1H3M16 3v4a1 1 0 001 1h4M8 21v-4a1 1 0 00-1-1H3M16 21v-4a1 1 0 011-1h4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      ประมวลผลทั้งหมด
    </button>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap">
    <div class="card">
      <div class="card-pad" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding-bottom:14px">
        <div class="sec-title" style="margin:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          ผลลัพธ์ <span id="img-count" style="color:var(--text-3);font-weight:400;text-transform:none;letter-spacing:0;margin-left:2px"></span>
        </div>
        <div style="display:flex;gap:8px">
          <button type="button" class="btn btn-ghost" id="btn-clear" style="padding:8px 12px;font-size:12.5px" disabled>ล้างทั้งหมด</button>
          <button type="button" class="btn btn-ghost" id="btn-download-all" style="padding:8px 12px;font-size:12.5px" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>
            ดาวน์โหลดทั้งหมด
          </button>
        </div>
      </div>
      <div id="img-list" class="img-list">
        <div class="img-empty" id="img-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M20 20v.01M14 20v.01M20 14v.01"/></svg>
          <div>ยังไม่มีไฟล์ — ลากรูปมาวางด้านซ้าย หรือคลิกเพื่อเลือกไฟล์</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= e(asset_url('assets/vendor/jszip.min.js')) ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
