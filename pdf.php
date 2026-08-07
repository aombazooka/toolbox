<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'pdf';
$page_title = 'รูป → PDF · รวม/แยกหน้า PDF';
$page_js = ['pdf.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/><path d="M8 13h8M8 17h5" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>รูป → PDF · รวม PDF · แยกหน้า</h1>
      <p>แปลงรูปเป็น PDF, รวมหลายไฟล์ PDF เป็นไฟล์เดียว หรือแยกหน้าที่ต้องการออกมา — ทำงานในเบราว์เซอร์ล้วน</p>
    </div>
  </div>
</div>

<div class="create-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:18px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>ไฟล์ทั้งหมดประมวลผลในเบราว์เซอร์ของคุณเท่านั้น <strong>ไม่มีการอัปโหลดขึ้นเซิร์ฟเวอร์</strong> หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        โหมดการทำงาน
      </div>
      <div class="seg" id="pdf-tabs">
        <button type="button" data-v="img2pdf" class="on">รูป→PDF</button>
        <button type="button" data-v="merge">รวม PDF</button>
        <button type="button" data-v="split">แยกหน้า</button>
      </div>
    </div>

    <!-- Tab: รูป -> PDF -->
    <div class="form-section tab-panel" id="tab-img2pdf">
      <div id="i2p-drop" class="img-drop">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M7 17.5A4.5 4.5 0 017.5 8.6 6 6 0 0119 10.5a4 4 0 01-.5 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12v7m0-7l-2.5 2.5M12 12l2.5 2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="t">ลากรูปมาวางที่นี่ หรือ <span class="link">คลิกเพื่อเลือกไฟล์</span></div>
        <div class="s">เลือกได้หลายไฟล์ · เรียงลำดับหน้าได้ด้วยปุ่มลูกศร</div>
        <input type="file" id="i2p-input" accept="image/*" multiple hidden>
      </div>
      <div id="i2p-list" class="img-list"></div>

      <div style="padding:18px 0 0">
        <div class="sec-title" style="margin-bottom:10px">ขนาดหน้า PDF</div>
        <div class="seg" id="i2p-pagesize">
          <button type="button" data-v="a4" class="on">พอดีหน้า A4</button>
          <button type="button" data-v="fit">พอดีกับรูป</button>
        </div>
        <div class="hint">"พอดีหน้า A4" จะวางรูปกึ่งกลางหน้ากระดาษมาตรฐาน ส่วน "พอดีกับรูป" ให้ขนาดหน้าเท่ากับรูปพอดี (เต็มหน้า ไม่มีขอบ)</div>
      </div>

      <button type="button" class="btn btn-primary btn-block" id="i2p-btn" disabled style="margin-top:16px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/></svg>
        รวมเป็น PDF
      </button>
    </div>

    <!-- Tab: รวม PDF -->
    <div class="form-section tab-panel hidden" id="tab-merge">
      <div id="mg-drop" class="img-drop">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 3v5h5" stroke-linejoin="round"/></svg>
        <div class="t">ลากไฟล์ PDF มาวางที่นี่ หรือ <span class="link">คลิกเพื่อเลือกไฟล์</span></div>
        <div class="s">เลือกได้หลายไฟล์ · เรียงลำดับก่อน-หลังได้ด้วยปุ่มลูกศร</div>
        <input type="file" id="mg-input" accept="application/pdf,.pdf" multiple hidden>
      </div>
      <div id="mg-list" class="img-list"></div>

      <button type="button" class="btn btn-primary btn-block" id="mg-btn" disabled style="margin-top:16px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 7h3a5 5 0 015 5v0a5 5 0 01-5 5h-3m-2 0H8a5 5 0 01-5-5v0a5 5 0 015-5h3" stroke-linecap="round"/></svg>
        รวม PDF
      </button>
    </div>

    <!-- Tab: แยกหน้า -->
    <div class="form-section tab-panel hidden" id="tab-split">
      <div id="sp-drop" class="img-drop">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 3v5h5" stroke-linejoin="round"/></svg>
        <div class="t">ลากไฟล์ PDF 1 ไฟล์มาวางที่นี่ หรือ <span class="link">คลิกเพื่อเลือกไฟล์</span></div>
        <div class="s">เลือกได้ทีละ 1 ไฟล์</div>
        <input type="file" id="sp-input" accept="application/pdf,.pdf" hidden>
      </div>
      <div id="sp-list" class="img-list"></div>

      <div style="padding:18px 0 0">
        <div class="sec-title" style="margin-bottom:10px">รูปแบบการแยก</div>
        <div class="seg" id="sp-mode">
          <button type="button" data-v="separate" class="on">แยกเป็นไฟล์ละหน้า (ZIP)</button>
          <button type="button" data-v="combine">รวมหน้าที่เลือกเป็นไฟล์เดียว</button>
        </div>
        <div class="hint" id="sp-mode-hint">"แยกเป็นไฟล์ละหน้า" จะได้ PDF แยกไฟล์ทีละหน้า รวมมาเป็น .zip · "รวมเป็นไฟล์เดียว" จะได้ PDF ไฟล์เดียวที่มีเฉพาะหน้าที่เลือก</div>
      </div>

      <div class="field" style="margin-top:16px">
        <label class="lab">ช่วงหน้าที่ต้องการ <span style="color:var(--text-3);font-weight:400">(เว้นว่าง = ทุกหน้า)</span></label>
        <input class="input" id="sp-range" placeholder="เช่น 1-3,5 หรือเว้นว่างเพื่อแยกทุกหน้า" disabled>
        <div class="hint" id="sp-hint">เลือกไฟล์ PDF ก่อนเพื่อดูจำนวนหน้าทั้งหมด — ใส่ช่วงหน้าคั่นด้วยจุลภาค เช่น 1-3,5 หรือเว้นว่างเพื่อแยกทุกหน้า</div>
      </div>

      <button type="button" class="btn btn-primary btn-block" id="sp-btn" disabled style="margin-top:6px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H6a2 2 0 00-2 2v14a2 2 0 002 2h3M15 3h3a2 2 0 012 2v14a2 2 0 01-2 2h-3M9 3v18" stroke-linecap="round" stroke-linejoin="round"/></svg>
        แยกหน้า
      </button>

      <div class="hint" style="margin-top:14px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        ยังไม่รองรับ: การบีบอัดขนาด PDF และการแปลง PDF → Word (ต้องใช้เครื่องมือฝั่งเซิร์ฟเวอร์)
      </div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap">
    <div class="card">
      <div class="card-pad" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding-bottom:14px">
        <div class="sec-title" style="margin:0">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          ผลลัพธ์
        </div>
        <button type="button" class="btn btn-ghost" id="btn-reset" style="padding:8px 12px;font-size:12.5px" disabled>เริ่มใหม่</button>
      </div>

      <div class="card-pad" style="padding-top:0">
        <div id="result-empty" class="no-result">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;margin-bottom:10px;opacity:.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/></svg>
          <div id="result-empty-text">เลือกไฟล์และกดปุ่มด้านซ้ายเพื่อเริ่มประมวลผล</div>
        </div>

        <div id="result-loading" class="no-result hidden">
          <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:32px;height:32px;margin-bottom:10px"><path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/></svg>
          <div>กำลังประมวลผล...</div>
        </div>

        <div id="result-body" class="hidden">
          <div id="result-stage" class="short-qr-stage" style="min-height:260px;padding:0;overflow:hidden">
            <iframe id="result-frame" title="ตัวอย่าง PDF" style="width:100%;height:360px;border:0;border-radius:12px;background:#fff"></iframe>
          </div>
          <div id="result-zip" class="no-result hidden" style="padding:44px 20px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin-bottom:12px;opacity:.6"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/><path d="M10 12h2v2h-2zM12 14h2v2h-2zM10 16h2v2h-2z" fill="currentColor" stroke="none"/></svg>
            <div id="result-zip-text">ไฟล์ ZIP พร้อมดาวน์โหลด</div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
            <span class="badge" id="result-pages"></span>
            <span class="badge" id="result-size"></span>
          </div>
          <div class="ii-name" id="result-name" style="margin-top:10px;font-size:14.5px"></div>
          <button type="button" class="btn btn-primary btn-block" id="btn-download" style="margin-top:14px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>
            <span id="btn-download-label">ดาวน์โหลด PDF</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= e(asset_url('assets/vendor/pdf-lib.min.js')) ?>"></script>
<script src="<?= e(asset_url('assets/vendor/jszip.min.js')) ?>"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
