/* ================= QR Studio — history page ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);
const body = $('hist-body');
let timer = null;

function esc(s) { return (s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

/* Convert "#rrggbb" -> "r, g, b" for a tinted rgba() background. */
function hexToRgb(hex) {
  const m = /^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(hex || '');
  return m ? [parseInt(m[1], 16), parseInt(m[2], 16), parseInt(m[3], 16)].join(', ') : null;
}
function catTagStyle(color) {
  const rgb = hexToRgb(color);
  if (!rgb) return '';
  return ` style="background:rgba(${rgb},.16);color:${esc(color)}"`;
}

function buildOpts(item, size, withLogo) {
  const st = item.style || {};
  const dot = st.dotColor || '#191c24';
  return {
    width: size, height: size, type: 'svg',
    data: item.qr_data || 'https://example.com',
    image: (withLogo && item._logo) ? item._logo : undefined,
    dotsOptions: { color: dot, type: st.dotType || 'rounded' },
    backgroundOptions: { color: st.bgColor || '#ffffff' },
    cornersSquareOptions: { type: st.dotType === 'square' ? 'square' : 'extra-rounded', color: dot },
    cornersDotOptions: { color: dot },
    imageOptions: { margin: 5, imageSize: (st.logoSize || 40) / 100, hideBackgroundDots: true },
    qrOptions: { errorCorrectionLevel: (withLogo && item._logo) ? 'H' : (st.ec || 'M') }
  };
}

let viewAll = false;

async function load() {
  const q = encodeURIComponent($('hist-search').value.trim());
  const c = encodeURIComponent($('hist-filter').value);
  const all = (viewAll && window.APP.isAdmin) ? '&all=1' : '';
  body.innerHTML = '<div class="hist-loading">กำลังโหลด...</div>';
  try {
    const j = await (await fetch(`api/list.php?q=${q}&category=${c}${all}`)).json();
    if (!j.ok) { body.innerHTML = '<div class="no-result">โหลดข้อมูลไม่สำเร็จ</div>'; return; }
    if (j.stats) {
      $('st-total').textContent = j.stats.total;
      $('st-week').textContent  = j.stats.week;
      $('st-cats').textContent  = j.stats.cats;
      $('st-scans').textContent = Number(j.stats.scans).toLocaleString();
    }
    render(j.items);
  } catch (e) {
    body.innerHTML = '<div class="no-result">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

function render(items) {
  if (!items.length) { body.innerHTML = '<div class="no-result">ไม่พบรายการที่ตรงกับการค้นหา</div>'; return; }
  body.innerHTML = '';
  items.forEach(item => {
    const dyn = item.type === 'dynamic';
    const row = document.createElement('div');
    row.className = 'hist-row';
    row.innerHTML = `
      <div class="hist-thumb"></div>
      <div style="min-width:0">
        <div class="hn-name">${esc(item.name)}</div>
        <div class="hn-link">${esc(item.destination_url)}</div>
        ${item.owner ? `<div class="hn-owner" style="font-size:11.5px;color:var(--text-3);margin-top:2px">โดย ${esc(item.owner)}</div>` : ''}
      </div>
      <div>
        <span class="cat-tag ${dyn ? 'type-dyn' : 'type-sta'}">${dyn ? 'ไดนามิก' : 'ปกติ'}</span>
        ${item.category ? `<div style="margin-top:5px"><span class="cat-tag"${catTagStyle(item.category_color)}>${esc(item.category)}</span></div>` : ''}
        ${dyn ? `<div class="scan-pill" style="margin-top:5px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>${item.scan_count} สแกน</div>` : ''}
        ${dyn ? `<div class="status-slot" style="margin-top:5px"></div>` : ''}
      </div>
      <div class="hist-date col-date">${esc(item.created_at)}</div>
      <div class="hist-acts col-acts"></div>`;

    // thumbnail
    new QRCodeStyling(buildOpts(item, 40, false)).append(row.querySelector('.hist-thumb'));

    // status badge (dynamic only): "ปิด" / "หมดอายุ" / nothing (active)
    if (dyn) renderStatusBadge(row, item);

    // actions
    const acts = row.querySelector('.hist-acts');
    acts.appendChild(iconBtn('ดาวน์โหลด', 'dl', () => downloadItem(item)));
    if (dyn) {
      acts.appendChild(iconBtn('คัดลอกลิงก์', 'copy', () => {
        navigator.clipboard.writeText(item.qr_data).then(() => toast('คัดลอกลิงก์แล้ว'));
      }));
      acts.appendChild(iconBtn('สถิติ', 'stats', () => { location.href = 'analytics.php?id=' + item.id; }));
      acts.appendChild(iconBtn(item.is_active ? 'ปิดการใช้งาน' : 'เปิดใช้งาน', item.is_active ? 'off' : 'on', () => toggleActive(item, row)));
    }
    acts.appendChild(iconBtn('แก้ไข', 'edit', () => { location.href = 'index.php?edit=' + item.id; }));
    acts.appendChild(iconBtn('ลบ', 'del', () => del(item, row)));
    body.appendChild(row);
  });
}

const TOGGLE_ICONS = {
  off: '<circle cx="12" cy="12" r="10"/><path d="M4.9 4.9l14.2 14.2" stroke-linecap="round"/>',
  on:  '<circle cx="12" cy="12" r="10"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-linecap="round" stroke-linejoin="round"/>'
};
function iconBtn(title, kind, fn) {
  const icons = {
    dl:   '<path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/>',
    copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
    stats: '<path d="M3 3v18h18" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 15l4-5 3 3 5-7" stroke-linecap="round" stroke-linejoin="round"/>',
    edit: '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z" stroke-linecap="round" stroke-linejoin="round"/>',
    del:  '<path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" stroke-linecap="round"/>',
    ...TOGGLE_ICONS
  };
  const b = document.createElement('button');
  b.type = 'button'; b.title = title;
  if (kind === 'del') b.className = 'del';
  if (kind === 'off' || kind === 'on') b.dataset.toggle = 'active';
  b.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icons[kind]}</svg>`;
  b.onclick = fn;
  return b;
}

async function downloadItem(item) {
  try {
    const j = await (await fetch('api/get.php?id=' + item.id)).json();
    if (!j.ok) { toast('โหลดไม่สำเร็จ', 'err'); return; }
    const full = j.item; full._logo = full.logo_data;
    const size = (full.style && full.style.size) || 1024;
    new QRCodeStyling(buildOpts(full, size, true))
      .download({ name: (full.name || 'qr').replace(/\s+/g, '_') + '_' + size, extension: 'png' });
  } catch (e) { toast('ดาวน์โหลดไม่สำเร็จ', 'err'); }
}

function renderStatusBadge(row, item) {
  const slot = row.querySelector('.status-slot');
  if (!slot) return;
  slot.innerHTML = '';
  if (!item.is_active) {
    slot.innerHTML = '<span class="cat-tag status-off">ปิด</span>';
  } else if (item.expired) {
    slot.innerHTML = '<span class="cat-tag status-expired">หมดอายุ</span>';
  }
}

async function toggleActive(item, row) {
  const next = item.is_active ? 0 : 1;
  const j = await postJSON('api/update.php', { id: item.id, is_active: next });
  if (!j.ok) { toast('อัปเดตไม่สำเร็จ', 'err'); return; }
  item.is_active = next;
  renderStatusBadge(row, item);
  const btn = row.querySelector('[data-toggle="active"]');
  if (btn) {
    btn.title = next ? 'ปิดการใช้งาน' : 'เปิดใช้งาน';
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${TOGGLE_ICONS[next ? 'off' : 'on']}</svg>`;
  }
  toast(next ? 'เปิดใช้งานแล้ว' : 'ปิดการใช้งานแล้ว');
}

async function del(item, row) {
  if (!confirm('ลบ "' + item.name + '" ?')) return;
  const j = await postJSON('api/delete.php', { id: item.id });
  if (j.ok) { row.style.opacity = '0'; setTimeout(() => { row.remove(); load(); }, 180); toast('ลบแล้ว'); }
  else toast('ลบไม่สำเร็จ', 'err');
}

async function postJSON(url, data) {
  data.csrf = window.APP.csrf;
  try {
    return await (await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })).json();
  } catch (e) { return { ok: false, error: 'เชื่อมต่อไม่สำเร็จ' }; }
}

$('hist-search').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); });
$('hist-filter').addEventListener('change', load);

if (window.APP.isAdmin) {
  const toggle = $('admin-all-toggle');
  const sw = $('admin-all-sw');
  if (toggle && sw) {
    toggle.addEventListener('click', () => {
      viewAll = !viewAll;
      sw.classList.toggle('on', viewAll);
      load();
    });
  }
}
$('print-link').onclick = e => {
  e.preventDefault();
  const p = new URLSearchParams();
  const q = $('hist-search').value.trim(); if (q) p.set('q', q);
  const c = $('hist-filter').value; if (c) p.set('category', c);
  location.href = 'print.php' + (p.toString() ? '?' + p.toString() : '');
};
load();
})();
