<?php
// ============================================================
//  QR Studio — user management (admin only)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_admin();

$active     = '';
$page_title = 'จัดการผู้ใช้';
$page_js    = ['users.js'];
$csrf       = csrf_token();

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>จัดการผู้ใช้</h1>
  <p>เพิ่ม แก้ไข รีเซ็ตรหัสผ่าน หรือลบผู้ใช้ในระบบ — เฉพาะผู้ดูแลระบบเท่านั้น</p>
</div>

<div style="display:flex;flex-direction:column;gap:20px;max-width:860px">

  <div class="card">
    <div class="card-pad">
      <div class="sec-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6" stroke-linecap="round"/></svg>
        เพิ่มผู้ใช้ใหม่
      </div>
      <form id="user-add-form" autocomplete="off">
        <div class="row2">
          <div class="field">
            <label class="lab">ชื่อผู้ใช้ (สำหรับเข้าสู่ระบบ)</label>
            <input class="input" id="nu-username" placeholder="เช่น staff1" maxlength="64" autocomplete="off" required>
          </div>
          <div class="field">
            <label class="lab">ชื่อที่แสดง</label>
            <input class="input" id="nu-display" placeholder="เช่น เจ้าหน้าที่ ก" maxlength="100" autocomplete="off">
          </div>
        </div>
        <div class="row2">
          <div class="field">
            <label class="lab">รหัสผ่าน</label>
            <input class="input" type="password" id="nu-password" minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร" autocomplete="new-password" required>
          </div>
          <div class="field">
            <label class="lab">สิทธิ์การใช้งาน</label>
            <select class="input" id="nu-role">
              <option value="staff">พนักงาน</option>
              <option value="admin">ผู้ดูแลระบบ</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">เพิ่มผู้ใช้</button>
      </form>
    </div>
  </div>

  <div class="hist-table">
    <div class="hist-row head" style="grid-template-columns:1fr 1fr 130px 110px 132px">
      <div>ชื่อผู้ใช้</div><div>ชื่อที่แสดง</div><div>สิทธิ์</div><div>สร้างเมื่อ</div><div style="text-align:right">จัดการ</div>
    </div>
    <div id="user-list"><div class="hist-loading">กำลังโหลด...</div></div>
  </div>

</div>

<script>
window.APP = {
  baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>,
  csrf: <?= json_encode($csrf) ?>,
  currentUserId: <?= (int)uid() ?>
};
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
