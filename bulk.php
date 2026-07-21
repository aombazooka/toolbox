<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$active = 'bulk';
$page_title = 'แปลงหลายลิงก์';
$page_js = ['bulk.js'];
$csrf = csrf_token();
include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>แปลงหลายลิงก์พร้อมกัน</h1>
  <p>วางรายการ (ชื่อ, ลิงก์, หมวดหมู่) หรือนำเข้าไฟล์ CSV/Excel สร้าง QR ทั้งหมดในครั้งเดียว ดาวน์โหลดหรือบันทึกเข้าประวัติได้</p>
</div>

<div class="bulk-grid">
  <div class="card card-pad">
    <div class="field">
      <label class="lab">รายการ (บรรทัดละ 1 รายการ)</label>
      <textarea class="input" id="bulk-text" style="min-height:200px" placeholder="ชื่อเอกสาร 1,https://example.com/doc/1001,ผลตรวจ&#10;https://example.com/doc/1002&#10;ชื่อเอกสาร 3,https://example.com/doc/1003"></textarea>
      <div class="hint">รูปแบบ: ชื่อ, ลิงก์, หมวดหมู่ (บรรทัดละ 1 รายการ) · วางเฉพาะลิงก์ก็ได้ ไม่ต้องมีชื่อ/หมวด</div>
    </div>
    <div class="field">
      <label class="lab">หรือนำเข้าไฟล์ (CSV / Excel)</label>
      <div style="display:flex;gap:9px;align-items:center;flex-wrap:wrap">
        <button type="button" class="btn btn-ghost" id="bulk-file-btn">เลือกไฟล์...</button>
        <span id="bulk-file-name" style="font-size:12px;color:var(--text-3)">ยังไม่ได้เลือกไฟล์</span>
        <input type="file" id="bulk-file" accept=".csv,.txt,.xlsx" hidden>
      </div>
      <label style="display:flex;align-items:center;gap:6px;margin-top:9px;font-size:12.5px;color:var(--text-2);cursor:pointer">
        <input type="checkbox" id="bulk-skip-header"> แถวแรกเป็นหัวตาราง (ข้าม)
      </label>
    </div>
    <button type="button" class="btn btn-primary btn-block" id="bulk-gen" style="margin-bottom:9px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke-linejoin="round"/></svg>สร้างทั้งหมด</button>
    <div style="display:flex;gap:9px">
      <button type="button" class="btn btn-ghost btn-block" id="bulk-zip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/></svg>ดาวน์โหลด ZIP</button>
      <button type="button" class="btn btn-ghost btn-block" id="bulk-save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>บันทึกทั้งหมด</button>
    </div>
  </div>

  <div class="card card-pad">
    <div style="font-size:13px;font-weight:600;color:var(--text-2);margin-bottom:14px">ผลลัพธ์ <span id="bulk-count" style="color:var(--text-3);font-weight:400"></span></div>
    <div class="bulk-out" id="bulk-out">
      <div class="bulk-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M20 20v.01M14 20v.01M20 14v.01"/></svg>
        <div>กด "สร้างทั้งหมด" เพื่อดูผลลัพธ์</div>
      </div>
    </div>
  </div>
</div>

<script src="assets/vendor/jszip.min.js"></script>
<script src="assets/vendor/xlsx.full.min.js"></script>
<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
