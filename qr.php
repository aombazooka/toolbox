<?php
require_once __DIR__ . '/includes/auth.php';
// Fully client-side: create a static QR + download. No login, no editing, no DB.
$isGuest = true;
$active = 'qr';
$edit = null;
$categories = array_keys(default_categories());

$page_title = 'สร้าง QR';
$page_js = ['create.js'];
$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1><?= $edit ? 'แก้ไข QR' : 'สร้าง QR Code' ?></h1>
  <p><?= $edit ? 'แก้ไขชื่อ ลิงก์ปลายทาง สไตล์ หรือโลโก้ ของ QR นี้ แล้วบันทึกทับรายการเดิม' : 'แปลงลิงก์เอกสารเป็น QR โค้ด พร้อมโลโก้ตรงกลางและปรับแต่งสไตล์ได้ตามต้องการ' ?></p>
</div>

<div class="create-grid">
  <!-- FORM -->
  <div class="card">
    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        เนื้อหา
      </div>

      <div class="field">
        <label class="lab">ชนิด QR</label>
        <div class="seg" id="qrtype">
          <button type="button" data-v="static" class="on">ปกติ (Static)</button>
          <button type="button" data-v="dynamic"<?= $isGuest ? ' disabled title="เข้าสู่ระบบเพื่อใช้ QR ไดนามิก" style="opacity:.5;cursor:not-allowed"' : '' ?>>ไดนามิก (Dynamic)</button>
        </div>
        <div class="type-note static-note" id="type-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01" stroke-linecap="round"/></svg>
          <span>ลิงก์ฝังในโค้ดโดยตรง สแกนแล้วเปิดทันที — แก้ปลายทางภายหลังไม่ได้</span>
        </div>
      </div>

      <div class="field">
        <label class="lab">ชื่อ / ป้ายกำกับ</label>
        <input class="input" id="f-name" placeholder="เช่น ใบส่งตัวผู้ป่วย — คุณสมชาย">
      </div>
      <div class="field">
        <label class="lab">ลิงก์ปลายทาง (URL)</label>
        <input class="input" id="f-link" placeholder="https://..." value="https://example.com/document">
      </div>
      <div class="field">
        <label class="lab">หมวดหมู่</label>
        <select class="input" id="f-cat">
          <?php foreach ($categories as $catName): ?>
            <option<?= ($edit && $edit['category'] === $catName) ? ' selected' : '' ?>><?= e($catName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
        โลโก้ตรงกลาง
      </div>
      <div class="field">
        <div class="logo-drop" id="logo-drop">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 0L8 7m4-4l4 4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 15v4a2 2 0 002 2h14a2 2 0 002-2v-4" stroke-linecap="round"/></svg>
          <div class="t">อัปโหลดโลโก้</div>
          <div class="s">PNG, JPG, SVG — แนะนำพื้นหลังโปร่งใส</div>
        </div>
        <div class="logo-preview" id="logo-preview">
          <img id="logo-img" alt="logo">
          <div class="lp-name" id="logo-name">logo.png</div>
          <button type="button" class="link-btn" id="logo-remove">ลบ</button>
        </div>
        <input type="file" id="logo-file" accept="image/*" hidden>
      </div>
      <div class="field" id="logo-size-field" style="display:none">
        <label class="lab">ขนาดโลโก้ <span id="logo-size-val" style="color:var(--text-3)">40%</span></label>
        <input type="range" id="f-logosize" min="20" max="55" value="40">
      </div>
    </div>

    <div class="form-section">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 22a10 10 0 0 0 0-20"/></svg>
        รูปแบบ &amp; สี
      </div>
      <?php if (!$isGuest): ?>
      <div class="field">
        <label class="lab">สไตล์ที่บันทึกไว้</label>
        <div class="row2" style="grid-template-columns:1fr auto auto auto;gap:8px">
          <select class="input" id="tpl-select" style="margin:0">
            <option value="">— สไตล์ที่บันทึก —</option>
          </select>
          <button type="button" class="btn btn-ghost" id="tpl-apply">ใช้สไตล์</button>
          <button type="button" class="btn btn-ghost" id="tpl-save">บันทึกสไตล์</button>
          <button type="button" class="icon-btn" id="tpl-delete" title="ลบเทมเพลตนี้">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke-linecap="round"/></svg>
          </button>
        </div>
      </div>
      <?php endif; ?>
      <div class="field">
        <label class="lab">สไตล์สำเร็จรูป</label>
        <div class="preset-grid" id="presets"></div>
      </div>
      <div class="row2" style="margin-top:14px">
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
          <button type="button" data-v="square" class="on">เหลี่ยม</button>
          <button type="button" data-v="rounded">มน</button>
          <button type="button" data-v="dots">กลม</button>
          <button type="button" data-v="classy">คลาสสิก</button>
          <button type="button" data-v="extra-rounded">มนมาก</button>
        </div>
      </div>
    </div>

    <details class="adv">
      <summary>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-2.82 1.17V21a2 2 0 11-4 0v-.09A1.65 1.65 0 007 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 003.6 15H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9.4l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 3.6V3a2 2 0 114 0v.09a1.65 1.65 0 002.82 1.18l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0021 9h.6a2 2 0 110 4H21z"/></svg>
        ตัวเลือกขั้นสูง
        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M9 18l6-6-6-6"/></svg>
      </summary>
      <div class="adv-body">
        <div class="field">
          <label class="lab">ระดับการกันความเสียหาย (Error Correction)</label>
          <div class="seg" id="eclevel">
            <button type="button" data-v="L">L</button>
            <button type="button" data-v="M" class="on">M</button>
            <button type="button" data-v="Q">Q</button>
            <button type="button" data-v="H">H</button>
          </div>
          <div class="hint">ยิ่งสูงยิ่งสแกนติดแม้ QR เสียหายบางส่วน — เมื่อใส่โลโก้ระบบจะใช้ H ให้อัตโนมัติ</div>
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
          <div class="hint">ยิ่งใหญ่ยิ่งคมเวลาปริ้นต์ · แนะนำ 1024px ขึ้นไปสำหรับติดเอกสาร</div>
        </div>
        <div class="field" id="expiry-field" style="margin-top:16px;display:none">
          <label class="lab">วันหมดอายุ (ไม่บังคับ)</label>
          <input type="datetime-local" class="input" id="f-expiry">
          <div class="hint">มีผลเฉพาะ QR แบบไดนามิก</div>
        </div>
      </div>
    </details>
  </div>

  <!-- PREVIEW -->
  <div class="preview-wrap">
    <div class="preview-card">
      <div class="preview-stage"><div id="qr-preview"></div></div>
      <div class="preview-meta">
        <div class="pm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6zM14 14h6v6h-6v-6z"/></svg></div>
        <div class="pm-txt">
          <div class="pm-name" id="pm-name">ยังไม่มีชื่อ</div>
          <div class="pm-link" id="pm-link">—</div>
        </div>
      </div>
      <div id="dyn-link-box" class="hidden" style="padding:0 20px 16px">
        <div class="hint" style="margin-bottom:2px">ลิงก์ไดนามิก (นำไปทำ QR / แชร์):</div>
        <div class="copy-field">
          <input id="dyn-link" readonly>
          <button type="button" id="dyn-copy">คัดลอก</button>
        </div>
      </div>
      <?php if ($edit): ?>
      <div style="padding:0 20px 12px">
        <button type="button" class="btn btn-primary" id="edit-save-btn" style="width:100%"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8" stroke-linecap="round" stroke-linejoin="round"/></svg>บันทึกการแก้ไข</button>
      </div>
      <?php endif; ?>
      <div class="preview-actions">
        <button type="button" class="btn btn-primary" id="dl-png"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>PNG</button>
        <button type="button" class="btn btn-ghost" id="dl-svg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>SVG</button>
      </div>
      <div class="hint" style="text-align:center;padding:2px 20px 16px"><?= $edit ? 'ดาวน์โหลดจะบันทึกการแก้ไขให้อัตโนมัติด้วย' : ($isGuest ? 'ดาวน์โหลดเป็นไฟล์ PNG / SVG' : 'ดาวน์โหลดครั้งแรกจะบันทึกลงประวัติให้อัตโนมัติ') ?></div>
    </div>
  </div>
</div>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?>, isGuest: <?= $isGuest ? 'true' : 'false' ?>, edit: <?= $edit ? json_encode($edit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'null' ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
