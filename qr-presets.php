<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'qr-presets';
$page_title = 'QR สำเร็จรูป';
$page_js = ['qr-presets.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M6 16c.5-2 2-3 2.5-3s2 1 2.5 3M13 9h5M13 13h5" stroke-linecap="round"/></svg>
    </div>
    <div>
      <h1>QR สำเร็จรูป</h1>
      <p>สร้าง QR สำหรับ WiFi, นามบัตร (vCard), อีเมล, SMS หรือเบอร์โทร — กรอกข้อมูลแล้วได้ QR ทันที</p>
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
        ชนิดข้อมูล
      </div>
      <div class="seg" id="preset-tabs">
        <button type="button" data-v="wifi" class="on">WiFi</button>
        <button type="button" data-v="tel">โทร</button>
        <button type="button" data-v="sms">SMS</button>
        <button type="button" data-v="email">อีเมล</button>
        <button type="button" data-v="vcard">vCard</button>
      </div>
    </div>

    <!-- WiFi -->
    <div class="form-section tab-panel" id="tab-wifi">
      <div class="field">
        <label class="lab">ชื่อ WiFi (SSID)</label>
        <input class="input" id="wifi-ssid" placeholder="เช่น Home_WiFi">
      </div>
      <div class="field">
        <label class="lab">รหัสผ่าน</label>
        <input class="input" id="wifi-pass" placeholder="เว้นว่างถ้าไม่มีรหัสผ่าน">
      </div>
      <div class="field">
        <label class="lab">ชนิดการเข้ารหัส</label>
        <div class="seg" id="wifi-sec">
          <button type="button" data-v="WPA" class="on">WPA/WPA2/WPA3</button>
          <button type="button" data-v="WEP">WEP</button>
          <button type="button" data-v="nopass">ไม่มีรหัสผ่าน</button>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:6px;margin-top:9px;font-size:12.5px;color:var(--text-2);cursor:pointer">
        <input type="checkbox" id="wifi-hidden"> เครือข่ายซ่อน (Hidden SSID)
      </label>
    </div>

    <!-- Tel -->
    <div class="form-section tab-panel hidden" id="tab-tel">
      <div class="field">
        <label class="lab">เบอร์โทรศัพท์</label>
        <input class="input" id="tel-num" placeholder="เช่น 0812345678" inputmode="tel">
        <div class="hint">สแกนแล้วเปิดหน้าโทรออกไปยังเบอร์นี้ทันที</div>
      </div>
    </div>

    <!-- SMS -->
    <div class="form-section tab-panel hidden" id="tab-sms">
      <div class="field">
        <label class="lab">เบอร์โทรศัพท์ผู้รับ</label>
        <input class="input" id="sms-num" placeholder="เช่น 0812345678" inputmode="tel">
      </div>
      <div class="field">
        <label class="lab">ข้อความ (ไม่บังคับ)</label>
        <textarea class="input" id="sms-msg" placeholder="ข้อความที่ต้องการส่ง"></textarea>
      </div>
    </div>

    <!-- Email -->
    <div class="form-section tab-panel hidden" id="tab-email">
      <div class="field">
        <label class="lab">อีเมลผู้รับ</label>
        <input class="input" id="email-to" placeholder="เช่น name@example.com" inputmode="email">
      </div>
      <div class="field">
        <label class="lab">หัวข้อ (ไม่บังคับ)</label>
        <input class="input" id="email-subject" placeholder="หัวข้ออีเมล">
      </div>
      <div class="field">
        <label class="lab">เนื้อหา (ไม่บังคับ)</label>
        <textarea class="input" id="email-body" placeholder="เนื้อหาอีเมล"></textarea>
      </div>
    </div>

    <!-- vCard -->
    <div class="form-section tab-panel hidden" id="tab-vcard">
      <div class="field">
        <label class="lab">ชื่อ-นามสกุล</label>
        <input class="input" id="vc-name" placeholder="เช่น สมชาย ใจดี">
      </div>
      <div class="field">
        <label class="lab">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
        <input class="input" id="vc-phone" placeholder="เช่น 0812345678" inputmode="tel">
      </div>
      <div class="field">
        <label class="lab">อีเมล (ไม่บังคับ)</label>
        <input class="input" id="vc-email" placeholder="เช่น name@example.com" inputmode="email">
      </div>
      <div class="field">
        <label class="lab">บริษัท/หน่วยงาน (ไม่บังคับ)</label>
        <input class="input" id="vc-company" placeholder="ชื่อบริษัท">
      </div>
      <div class="field">
        <label class="lab">ที่อยู่ (ไม่บังคับ)</label>
        <input class="input" id="vc-address" placeholder="ที่อยู่">
      </div>
      <div class="field">
        <label class="lab">เว็บไซต์ (ไม่บังคับ)</label>
        <input class="input" id="vc-website" placeholder="เช่น https://example.com">
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
        <div id="qr-empty" class="hidden no-result" style="padding:0">กรอกข้อมูลด้านซ้ายเพื่อสร้าง QR</div>
      </div>
      <div class="preview-meta">
        <div class="pm-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6zM14 14h6v6h-6v-6z"/></svg></div>
        <div class="pm-txt">
          <div class="pm-name" id="pm-name">WiFi</div>
          <div class="pm-link" id="pm-link" style="word-break:break-all">—</div>
        </div>
      </div>
      <div style="padding:0 20px 16px">
        <div class="hint" style="margin-bottom:4px">ข้อมูลที่เข้ารหัสใน QR:</div>
        <textarea id="raw-data" class="input" readonly style="min-height:64px;font-family:'Inter';font-size:12.5px;resize:vertical"></textarea>
        <button type="button" class="btn btn-ghost btn-block" id="raw-copy" style="margin-top:8px">คัดลอกข้อมูลนี้</button>
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
