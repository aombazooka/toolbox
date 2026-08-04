<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'promptpay';
$page_title = 'QR พร้อมเพย์ (PromptPay)';
$page_js = ['promptpay.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 8h2v2H8zM14 8h2v2h-2zM8 14h2v2H8z" stroke-linejoin="round"/><path d="M14 14h1.5v1.5H14zM15.5 15.5H17V17h-1.5zM14 17h1.5M17 14v1.5" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>QR พร้อมเพย์ (PromptPay)</h1>
      <p>สร้าง QR โอนเงินพร้อมเพย์จากเบอร์โทรศัพท์หรือเลขบัตรประชาชน ระบุจำนวนเงินได้ตามต้องการ</p>
    </div>
  </div>
</div>

<div class="create-grid">
  <!-- FORM -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>ข้อมูลที่กรอกทั้งหมดประมวลผลในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        ผูกบัญชีพร้อมเพย์ด้วย
      </div>
      <div class="field">
        <div class="seg" id="pp-mode">
          <button type="button" data-v="phone" class="on">เบอร์โทรศัพท์</button>
          <button type="button" data-v="citizen">เลขบัตรประชาชน</button>
        </div>
      </div>

      <div class="field" id="field-phone">
        <label class="lab">เบอร์โทรศัพท์มือถือ</label>
        <input class="input" id="pp-phone" placeholder="เช่น 0812345678" inputmode="tel" maxlength="10">
        <div class="hint" id="phone-hint">กรอกเบอร์มือถือ 9–10 หลัก (มีหรือไม่มีเลข 0 นำหน้าก็ได้)</div>
      </div>
      <div class="field hidden" id="field-citizen">
        <label class="lab">เลขบัตรประชาชน (13 หลัก)</label>
        <input class="input" id="pp-citizen" placeholder="เช่น 1234567890123" inputmode="numeric" maxlength="13">
        <div class="hint" id="citizen-hint">กรอกเลขบัตรประชาชน 13 หลัก</div>
      </div>

      <div class="field">
        <label class="lab">จำนวนเงิน บาท (ไม่บังคับ)</label>
        <input class="input" id="pp-amount" placeholder="เว้นว่างถ้าไม่ระบุจำนวนเงิน" inputmode="decimal">
        <div class="hint">ถ้าไม่ระบุ จะได้ QR แบบไม่ผูกยอด (Static) ใช้ซ้ำได้หลายครั้ง — ถ้าระบุจะได้ QR แบบระบุยอด (Dynamic) ใช้ได้ครั้งเดียว</div>
      </div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 22a10 10 0 0 0 0-20"/></svg>
        รูปแบบ &amp; สี
      </div>
      <div class="row2">
        <div class="field" style="margin:0">
          <label class="lab">สีจุด</label>
          <div class="color-field">
            <span class="color-chip"><input type="color" id="f-dotcolor" value="#000000"></span>
            <span class="color-val" id="dotcolor-val">#000000</span>
          </div>
        </div>
        <div class="field" style="margin:0">
          <label class="lab">สีพื้นหลัง</label>
          <div class="color-field">
            <span class="color-chip"><input type="color" id="f-bgcolor" value="#ffffff"></span>
            <span class="color-val" id="bgcolor-val">#ffffff</span>
          </div>
        </div>
      </div>
      <div class="swatches" id="swatches"></div>
      <div class="field" style="margin-top:16px">
        <label class="lab">รูปแบบจุด</label>
        <div class="seg" id="dotstyle">
          <button type="button" data-v="rounded" class="on">มน</button>
          <button type="button" data-v="dots">กลม</button>
          <button type="button" data-v="square">เหลี่ยม</button>
          <button type="button" data-v="classy">คลาสสิก</button>
          <button type="button" data-v="extra-rounded">มนมาก</button>
        </div>
      </div>
      <div class="field" style="margin-top:16px">
        <label class="lab">ขนาดภาพส่งออก <span id="size-val" style="color:var(--text-3)">512 × 512 px</span></label>
        <div class="seg" id="qsize">
          <button type="button" data-v="256">เล็ก</button>
          <button type="button" data-v="512" class="on">กลาง</button>
          <button type="button" data-v="1024">ใหญ่</button>
          <button type="button" data-v="2048">ปริ้นต์</button>
        </div>
        <input type="range" id="f-qsize" min="128" max="2048" step="32" value="512" style="margin-top:12px">
      </div>
    </div>
  </div>

  <!-- PREVIEW -->
  <div class="preview-wrap">
    <div class="preview-card">
      <div class="preview-stage">
        <div id="qr-preview"></div>
        <div id="qr-empty" class="hidden no-result" style="padding:0">กรอกเบอร์โทรศัพท์หรือเลขบัตรประชาชนเพื่อสร้าง QR</div>
      </div>
      <div class="preview-meta">
        <div class="pm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6zM14 14h6v6h-6v-6z"/></svg></div>
        <div class="pm-txt">
          <div class="pm-name" id="pm-name">พร้อมเพย์</div>
          <div class="pm-link" id="pm-link" style="word-break:break-all">—</div>
        </div>
      </div>
      <div style="padding:0 20px 16px">
        <div class="hint" style="margin-bottom:4px">Payload ที่เข้ารหัสใน QR (EMVCo):</div>
        <textarea id="raw-data" class="input" readonly style="min-height:64px;font-family:'Inter';font-size:12px;resize:vertical;word-break:break-all"></textarea>
        <button type="button" class="btn btn-ghost btn-block" id="raw-copy" style="margin-top:8px">คัดลอก Payload</button>
      </div>
      <div class="preview-actions">
        <button type="button" class="btn btn-primary" id="dl-png"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>PNG</button>
        <button type="button" class="btn btn-ghost" id="dl-svg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>SVG</button>
      </div>
      <div class="hint" style="text-align:center;padding:2px 20px 16px">ดาวน์โหลดเป็นไฟล์ PNG / SVG — ไม่มีการบันทึกลงระบบ</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
