/* ================= QR Studio — settings page (category manager) ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);
const list = $('cat-list');
if (!list) return; // card not on this page

function esc(s) { return (s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

async function postJSON(url, data) {
  data.csrf = window.APP.csrf;
  try {
    return await (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })).json();
  } catch (e) { return { ok: false, error: 'เชื่อมต่อไม่สำเร็จ' }; }
}

function trashIcon() {
  return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" stroke-linecap="round"/></svg>';
}

function render(items) {
  if (!items.length) { list.innerHTML = '<div class="hint">ยังไม่มีหมวดหมู่ — เพิ่มหมวดหมู่แรกด้านล่าง</div>'; return; }
  list.innerHTML = '';
  items.forEach(cat => {
    const row = document.createElement('div');
    row.className = 'cat-row';
    row.dataset.id = cat.id;
    row.style.cssText = 'display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid var(--border);border-radius:10px';
    row.innerHTML = `
      <span style="width:14px;height:14px;border-radius:50%;background:${esc(cat.color || '#9098a6')};flex-shrink:0"></span>
      <span style="flex:1;font-size:13.5px">${esc(cat.name)}</span>
      <button type="button" class="icon-btn" title="ลบหมวดหมู่นี้"></button>`;
    const delBtn = row.querySelector('button');
    delBtn.innerHTML = trashIcon();
    delBtn.onclick = () => removeCat(cat, row);
    list.appendChild(row);
  });
}

async function load() {
  try {
    const j = await (await fetch('api/categories.php')).json();
    if (!j.ok) { list.innerHTML = '<div class="hint">โหลดหมวดหมู่ไม่สำเร็จ</div>'; return; }
    render(j.items);
  } catch (e) {
    list.innerHTML = '<div class="hint">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

async function removeCat(cat, row) {
  if (!confirm('ลบหมวดหมู่ "' + cat.name + '" ? (QR ที่ใช้หมวดนี้อยู่จะไม่เปลี่ยนแปลง)')) return;
  const j = await postJSON('api/categories.php', { action: 'delete', id: cat.id });
  if (j.ok) { row.style.opacity = '0'; setTimeout(() => row.remove(), 150); toast('ลบหมวดหมู่แล้ว'); }
  else toast(j.error || 'ลบไม่สำเร็จ', 'err');
}

$('cat-add-form').addEventListener('submit', async e => {
  e.preventDefault();
  const nameInput = $('cat-name'), colorInput = $('cat-color');
  const name = nameInput.value.trim();
  if (!name) { toast('กรุณากรอกชื่อหมวดหมู่', 'err'); return; }
  const j = await postJSON('api/categories.php', { action: 'add', name, color: colorInput.value });
  if (!j.ok) { toast(j.error || 'เพิ่มไม่สำเร็จ', 'err'); return; }
  nameInput.value = '';
  toast('เพิ่มหมวดหมู่แล้ว');
  load();
});

load();
})();
