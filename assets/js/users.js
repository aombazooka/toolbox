/* ================= QR Studio — user management (admin) ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);
const list = $('user-list');
if (!list) return; // not on this page

const currentUserId = (window.APP && window.APP.currentUserId) || 0;

function esc(s) { return (s == null ? '' : String(s)).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

async function postJSON(url, data) {
  data.csrf = window.APP.csrf;
  try {
    return await (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })).json();
  } catch (e) { return { ok: false, error: 'เชื่อมต่อไม่สำเร็จ' }; }
}

function saveIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
function keyIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2L19 4M16 7l3 3M13 10l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
function trashIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" stroke-linecap="round"/></svg>';
}

function render(items) {
  if (!items.length) { list.innerHTML = '<div class="hist-loading">ไม่มีผู้ใช้</div>'; return; }
  list.innerHTML = '';
  items.forEach(u => {
    const row = document.createElement('div');
    row.className = 'hist-row';
    row.style.gridTemplateColumns = '1fr 1fr 130px 110px 132px';
    row.dataset.id = u.id;

    const isSelf = Number(u.id) === Number(currentUserId);

    row.innerHTML = `
      <div style="font-weight:600">${esc(u.username)}${isSelf ? ' <span class="hint" style="font-weight:400">(คุณ)</span>' : ''}</div>
      <div><input class="input" style="padding:8px 10px;font-size:13px" data-role="display" value="${esc(u.display_name)}" maxlength="100"></div>
      <div>
        <select class="input" style="padding:8px 10px;font-size:13px" data-role="role">
          <option value="staff"${u.role === 'staff' ? ' selected' : ''}>พนักงาน</option>
          <option value="admin"${u.role === 'admin' ? ' selected' : ''}>ผู้ดูแลระบบ</option>
        </select>
      </div>
      <div class="hist-date">${esc(u.created_at)}</div>
      <div class="hist-acts" style="justify-content:flex-end">
        <button type="button" class="save-btn" title="บันทึกการแก้ไข"></button>
        <button type="button" class="reset-btn" title="รีเซ็ตรหัสผ่าน"></button>
        <button type="button" class="del-btn del" title="ลบผู้ใช้"${isSelf ? ' disabled' : ''}></button>
      </div>`;

    row.querySelector('.save-btn').innerHTML = saveIcon();
    row.querySelector('.reset-btn').innerHTML = keyIcon();
    row.querySelector('.del-btn').innerHTML = trashIcon();

    row.querySelector('.save-btn').onclick = () => saveRow(u, row);
    row.querySelector('.reset-btn').onclick = () => resetPassword(u);
    row.querySelector('.del-btn').onclick = () => removeUser(u, row);

    list.appendChild(row);
  });
}

async function load() {
  try {
    const j = await (await fetch('api/users.php')).json();
    if (!j.ok) { list.innerHTML = '<div class="hist-loading">โหลดรายชื่อผู้ใช้ไม่สำเร็จ</div>'; return; }
    render(j.items);
  } catch (e) {
    list.innerHTML = '<div class="hist-loading">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

async function saveRow(u, row) {
  const display = row.querySelector('[data-role="display"]').value.trim();
  const role = row.querySelector('[data-role="role"]').value;
  const j = await postJSON('api/users.php', { action: 'update', id: u.id, display_name: display, role });
  if (!j.ok) { toast(j.error || 'บันทึกไม่สำเร็จ', 'err'); return; }
  toast('บันทึกการแก้ไขแล้ว');
  load();
}

async function resetPassword(u) {
  const pw = prompt('ตั้งรหัสผ่านใหม่สำหรับ "' + u.username + '" (อย่างน้อย 6 ตัวอักษร)');
  if (pw === null) return;
  if (pw.length < 6) { toast('รหัสผ่านอย่างน้อย 6 ตัวอักษร', 'err'); return; }
  const j = await postJSON('api/users.php', { action: 'reset_password', id: u.id, password: pw });
  if (!j.ok) { toast(j.error || 'รีเซ็ตรหัสผ่านไม่สำเร็จ', 'err'); return; }
  toast('รีเซ็ตรหัสผ่านแล้ว');
}

async function removeUser(u, row) {
  if (!confirm('ลบผู้ใช้ "' + u.username + '" ? ข้อมูล QR/หมวดหมู่/เทมเพลตของผู้ใช้นี้จะถูกลบทั้งหมด')) return;
  const j = await postJSON('api/users.php', { action: 'delete', id: u.id });
  if (!j.ok) { toast(j.error || 'ลบไม่สำเร็จ', 'err'); return; }
  row.style.opacity = '0';
  setTimeout(() => row.remove(), 150);
  toast('ลบผู้ใช้แล้ว');
}

$('user-add-form').addEventListener('submit', async e => {
  e.preventDefault();
  const username = $('nu-username').value.trim();
  const display  = $('nu-display').value.trim();
  const password = $('nu-password').value;
  const role     = $('nu-role').value;

  if (!username) { toast('กรุณากรอกชื่อผู้ใช้', 'err'); return; }
  if (password.length < 6) { toast('รหัสผ่านอย่างน้อย 6 ตัวอักษร', 'err'); return; }

  const j = await postJSON('api/users.php', { action: 'add', username, display_name: display, password, role });
  if (!j.ok) { toast(j.error || 'เพิ่มผู้ใช้ไม่สำเร็จ', 'err'); return; }

  $('nu-username').value = '';
  $('nu-display').value = '';
  $('nu-password').value = '';
  $('nu-role').value = 'staff';
  toast('เพิ่มผู้ใช้แล้ว');
  load();
});

load();
})();
