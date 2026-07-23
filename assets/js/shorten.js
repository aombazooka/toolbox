/* ================= Toolbox — shorten.php (URL shortener) ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);

function esc(s) { return (s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

async function postJSON(url, data, method) {
  data = data || {};
  data.csrf = window.APP.csrf;
  try {
    const res = await fetch(url, {
      method: method || 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return await res.json();
  } catch (e) {
    return { ok: false, error: 'เชื่อมต่อไม่สำเร็จ' };
  }
}

/* ---------------- shorten form ---------------- */
let current = null; // last created { code, short_url, target_url, expires_at }
let qrBuilt = null;  // QRCodeStyling instance backing #qr-preview, once built

function showResult(r) {
  current = r;
  $('result-empty').classList.add('hidden');
  $('result-body').classList.remove('hidden');
  $('out-url').value = r.short_url;
  $('out-target').textContent = 'ไปยัง: ' + r.target_url;
  $('out-expiry').textContent = r.expires_at ? ('หมดอายุ: ' + r.expires_at) : '';
  $('qr-box').classList.add('hidden');
  qrBuilt = null; // force a fresh QR next time "ทำ QR" is pressed
}

async function doShorten() {
  const url = $('f-url').value.trim();
  if (!url) { toast('กรุณากรอกลิงก์ปลายทาง', 'err'); $('f-url').focus(); return; }
  const btn = $('btn-shorten');
  btn.disabled = true;
  try {
    const j = await postJSON('api/shorten/create.php', {
      target_url: url,
      alias: $('f-alias').value.trim(),
      expires_at: $('f-expiry').value || null
    });
    if (!j.ok) { toast(j.error || 'ย่อลิงก์ไม่สำเร็จ', 'err'); return; }
    showResult(j);
    toast('ย่อลิงก์สำเร็จ');
    $('f-alias').value = '';
    if (window.APP.isLoggedIn) loadMyLinks();
  } finally {
    btn.disabled = false;
  }
}
$('btn-shorten').onclick = doShorten;
['f-url', 'f-alias'].forEach(id => {
  $(id).addEventListener('keydown', e => { if (e.key === 'Enter') doShorten(); });
});

$('btn-copy').onclick = () => {
  navigator.clipboard.writeText($('out-url').value).then(() => toast('คัดลอกลิงก์แล้ว'));
};

function qrOpts(size, data) {
  return {
    width: size, height: size, type: 'svg',
    data: data,
    dotsOptions: { color: '#191c24', type: 'rounded' },
    backgroundOptions: { color: '#ffffff' },
    cornersSquareOptions: { type: 'extra-rounded', color: '#191c24' },
    cornersDotOptions: { color: '#191c24' },
    qrOptions: { errorCorrectionLevel: 'M' }
  };
}

$('btn-make-qr').onclick = () => {
  if (!current) return;
  const box = $('qr-box');
  box.classList.toggle('hidden');
  if (box.classList.contains('hidden')) return;
  const stage = $('qr-preview');
  if (!qrBuilt) {
    stage.innerHTML = '';
    qrBuilt = new QRCodeStyling(qrOpts(220, current.short_url));
    qrBuilt.append(stage);
  }
};
$('qr-dl-png').onclick = () => {
  if (!current) return;
  new QRCodeStyling(qrOpts(1024, current.short_url)).download({ name: 'short_' + current.code, extension: 'png' });
};
$('qr-dl-svg').onclick = () => {
  if (!current) return;
  new QRCodeStyling(qrOpts(1024, current.short_url)).download({ name: 'short_' + current.code, extension: 'svg' });
};

/* ---------------- "my links" (logged-in only) ---------------- */
function iconBtn(title, kind, fn) {
  const icons = {
    copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
    edit: '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z" stroke-linecap="round" stroke-linejoin="round"/>',
    off:  '<circle cx="12" cy="12" r="10"/><path d="M4.9 4.9l14.2 14.2" stroke-linecap="round"/>',
    on:   '<circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-linecap="round" stroke-linejoin="round"/>',
    del:  '<path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" stroke-linecap="round"/>'
  };
  const b = document.createElement('button');
  b.type = 'button'; b.title = title;
  if (kind === 'del') b.className = 'del';
  b.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icons[kind]}</svg>`;
  b.onclick = fn;
  return b;
}

function statusNote(item) {
  if (!item.is_active) return '<div class="hint" style="color:var(--danger)">ปิดการใช้งาน</div>';
  if (item.expired) return '<div class="hint" style="color:var(--danger)">หมดอายุแล้ว</div>';
  return '';
}

function renderMyLinks(items) {
  const body = $('my-links-body');
  if (!items.length) { body.innerHTML = '<div class="no-result">ยังไม่เคยสร้างลิงก์สั้น</div>'; return; }
  body.innerHTML = '';
  items.forEach(item => {
    const row = document.createElement('div');
    row.className = 'short-row';
    row.innerHTML = `
      <div style="min-width:0">
        <div class="sr-code">${esc(item.short_url.replace(/^https?:\/\//, ''))}</div>
        <div class="sr-target">${esc(item.target_url)}</div>
        ${statusNote(item)}
      </div>
      <div class="sr-clicks">${item.clicks.toLocaleString()}</div>
      <div class="sr-date sr-col-date">${esc(item.created_at)}</div>
      <div class="sr-acts sr-col-acts"></div>`;

    const acts = row.querySelector('.sr-acts');
    acts.appendChild(iconBtn('คัดลอกลิงก์', 'copy', () => {
      navigator.clipboard.writeText(item.short_url).then(() => toast('คัดลอกลิงก์แล้ว'));
    }));
    acts.appendChild(iconBtn('แก้ไขปลายทาง', 'edit', () => editDestination(item, row)));
    acts.appendChild(iconBtn(item.is_active ? 'ปิดการใช้งาน' : 'เปิดใช้งาน', item.is_active ? 'off' : 'on', () => toggleActive(item, row)));
    acts.appendChild(iconBtn('ลบ', 'del', () => del(item, row)));
    body.appendChild(row);
  });
}

async function editDestination(item, row) {
  const next = prompt('แก้ไขลิงก์ปลายทาง', item.target_url);
  if (next === null) return;
  const trimmed = next.trim();
  if (!trimmed) { toast('กรุณากรอกลิงก์ปลายทาง', 'err'); return; }
  const j = await postJSON('api/shorten/list.php', { action: 'update', id: item.id, target_url: trimmed });
  if (!j.ok) { toast(j.error || 'แก้ไขไม่สำเร็จ', 'err'); return; }
  item.target_url = trimmed;
  row.querySelector('.sr-target').textContent = trimmed;
  toast('บันทึกแล้ว');
}

async function toggleActive(item, row) {
  const nextVal = item.is_active ? 0 : 1;
  const j = await postJSON('api/shorten/list.php', { action: 'update', id: item.id, is_active: nextVal });
  if (!j.ok) { toast(j.error || 'อัปเดตไม่สำเร็จ', 'err'); return; }
  item.is_active = nextVal;
  loadMyLinks();
  toast(nextVal ? 'เปิดใช้งานแล้ว' : 'ปิดการใช้งานแล้ว');
}

async function del(item, row) {
  if (!confirm('ลบลิงก์นี้ ?')) return;
  const j = await postJSON('api/shorten/list.php', { action: 'delete', id: item.id });
  if (!j.ok) { toast(j.error || 'ลบไม่สำเร็จ', 'err'); return; }
  row.style.opacity = '0';
  setTimeout(() => row.remove(), 180);
  toast('ลบแล้ว');
}

async function loadMyLinks() {
  const body = $('my-links-body');
  if (!body) return;
  try {
    const j = await (await fetch('api/shorten/list.php')).json();
    if (!j.ok) { body.innerHTML = '<div class="no-result">โหลดข้อมูลไม่สำเร็จ</div>'; return; }
    renderMyLinks(j.items);
  } catch (e) {
    body.innerHTML = '<div class="no-result">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

if (window.APP.isLoggedIn) loadMyLinks();
})();
