<?php
require_once __DIR__ . '/includes/auth.php';
require_installed(); // public tool, no login needed — fully client-side, nothing to persist
$active = 'password';
$page_title = 'สร้างรหัสผ่านปลอดภัย';
$page_js = ['password.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div class="page-head-ico">
    <div class="ph-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/><circle cx="12" cy="15.5" r="1.3" fill="currentColor" stroke="none"/></svg>
    </div>
    <div>
      <h1>สร้างรหัสผ่านปลอดภัย</h1>
      <p>สุ่มรหัสผ่านที่แข็งแรงด้วยตัวสุ่มเข้ารหัส ปรับความยาวและชุดตัวอักษรได้ตามต้องการ</p>
    </div>
  </div>
</div>

<div class="loan-grid">
  <!-- INPUT -->
  <div class="card">
    <div class="form-section">
      <div class="hint" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:16px;padding:10px 12px;background:var(--surface-3);border-radius:10px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4" stroke-linecap="round"/></svg>
        <span>รหัสผ่านทั้งหมดสุ่มในเบราว์เซอร์ของคุณเท่านั้น ไม่ถูกส่งขึ้นเซิร์ฟเวอร์หรือบันทึกที่ใดทั้งสิ้น</span>
      </div>

      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        โหมด
      </div>
      <div class="seg" id="mode-tabs">
        <button type="button" data-v="password" class="on">รหัสผ่านสุ่ม</button>
        <button type="button" data-v="passphrase">Passphrase</button>
      </div>
    </div>

    <!-- RANDOM PASSWORD MODE -->
    <div class="form-section tab-panel" id="panel-password">
      <div class="field">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <label class="lab" style="margin-bottom:0">ความยาวรหัสผ่าน</label>
          <span class="pw-len-badge" id="pw-length-val">20</span>
        </div>
        <input type="range" id="pw-length" min="4" max="64" value="20" style="margin-top:10px">
        <div class="hint">เลือกได้ตั้งแต่ 4 ถึง 64 ตัวอักษร</div>
      </div>

      <div class="pw-toggles" style="margin-top:6px">
        <div class="toggle-row">
          <span class="tr-txt">ตัวพิมพ์ใหญ่<small>A B C … Z</small></span>
          <span class="sw-toggle on" id="pw-upper-toggle"></span>
        </div>
        <div class="toggle-row">
          <span class="tr-txt">ตัวพิมพ์เล็ก<small>a b c … z</small></span>
          <span class="sw-toggle on" id="pw-lower-toggle"></span>
        </div>
        <div class="toggle-row">
          <span class="tr-txt">ตัวเลข<small>0 1 2 … 9</small></span>
          <span class="sw-toggle on" id="pw-number-toggle"></span>
        </div>
        <div class="toggle-row">
          <span class="tr-txt">สัญลักษณ์<small>! @ # $ % ^ &amp; *</small></span>
          <span class="sw-toggle" id="pw-symbol-toggle"></span>
        </div>
        <div class="toggle-row">
          <span class="tr-txt">เลี่ยงตัวอักษรที่กำกวม<small>ไม่ใช้ 0 O 1 l I</small></span>
          <span class="sw-toggle" id="pw-ambiguous-toggle"></span>
        </div>
      </div>
      <div class="hint" style="margin-top:10px">หากเลือกหลายชุด ระบบการันตีว่ารหัสผ่านจะมีตัวอักษรจากทุกชุดที่เลือกอย่างน้อยชุดละ 1 ตัวเสมอ</div>
    </div>

    <!-- PASSPHRASE MODE -->
    <div class="form-section tab-panel hidden" id="panel-passphrase">
      <div class="field">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <label class="lab" style="margin-bottom:0">จำนวนคำ</label>
          <span class="pw-len-badge" id="pp-words-val">4</span>
        </div>
        <input type="range" id="pp-words" min="3" max="10" value="4" style="margin-top:10px">
      </div>
      <div class="field">
        <label class="lab">ตัวคั่นระหว่างคำ</label>
        <select class="input" id="pp-sep">
          <option value="-" selected>ขีด ( - )</option>
          <option value="_">ขีดล่าง ( _ )</option>
          <option value=".">จุด ( . )</option>
          <option value=" ">เว้นวรรค</option>
        </select>
      </div>
      <div class="pw-toggles">
        <div class="toggle-row">
          <span class="tr-txt">ขึ้นต้นด้วยตัวพิมพ์ใหญ่<small>Correct-Horse-Battery</small></span>
          <span class="sw-toggle on" id="pp-cap-toggle"></span>
        </div>
        <div class="toggle-row">
          <span class="tr-txt">เติมตัวเลขต่อท้าย<small>เช่น …-battery-74</small></span>
          <span class="sw-toggle" id="pp-number-toggle"></span>
        </div>
      </div>
      <div class="hint" style="margin-top:10px">Passphrase คือรหัสผ่านที่ประกอบจากคำหลายคำ จำง่ายกว่าแต่ยาวและปลอดภัยไม่แพ้กัน (เช่น correct-horse-battery-staple)</div>
    </div>
  </div>

  <!-- RESULT -->
  <div class="preview-wrap loan-result-wrap">
    <div class="card card-pad">
      <div class="sec-title" style="margin-bottom:12px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        รหัสผ่านที่สร้าง
      </div>
      <div class="pwd-display" id="pwd-output">—</div>

      <div class="pwd-meter">
        <div class="pwd-meter-track"><div class="pwd-meter-fill" id="pwd-meter-fill"></div></div>
        <div class="pwd-meter-label">
          <span>ความแข็งแรง</span>
          <span class="pml-tag" id="pwd-meter-tag">—</span>
        </div>
        <div class="hint" id="pwd-meter-bits"></div>
      </div>

      <div style="display:flex;gap:10px;margin-top:18px">
        <button type="button" class="btn btn-primary" style="flex:1" id="pwd-generate-btn">สุ่มรหัสผ่านใหม่</button>
        <button type="button" class="btn btn-ghost" id="pwd-copy-btn">คัดลอก</button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
