/* ================= QR Studio — analytics page ================= */
(function () {
'use strict';
const root = document.getElementById('an-root');
const params = new URLSearchParams(location.search);
const id = params.get('id');

function esc(s) { return (s || '').toString().replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

/** Inline SVG bar chart for a 30-day daily series. No external chart library. */
function barChart(daily) {
  const max = Math.max(1, ...daily.map(d => d.count));
  const w = 720, h = 220, padL = 8, padR = 8, padT = 20, padB = 24;
  const chartW = w - padL - padR;
  const chartH = h - padT - padB;
  const n = daily.length;
  const bw = chartW / n;
  let bars = '';
  daily.forEach((d, i) => {
    const bh = Math.round((d.count / max) * (chartH - 4));
    const x = padL + i * bw;
    const y = padT + chartH - bh;
    const barH = d.count > 0 ? Math.max(bh, 2) : 0;
    bars += `<rect x="${(x + 1).toFixed(1)}" y="${(padT + chartH - barH).toFixed(1)}" width="${Math.max(bw - 2, 1).toFixed(1)}" height="${barH}" rx="2" fill="var(--accent)"><title>${esc(d.label)}: ${d.count} สแกน</title></rect>`;
  });
  let labels = '';
  const step = Math.ceil(n / 6);
  daily.forEach((d, i) => {
    if (i % step === 0 || i === n - 1) {
      const x = padL + i * bw + bw / 2;
      labels += `<text x="${x.toFixed(1)}" y="${h - 6}" font-size="10.5" text-anchor="middle" fill="var(--text-3)">${esc(d.label)}</text>`;
    }
  });
  return `
  <svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" style="width:100%;height:220px;display:block">
    <text x="${padL}" y="13" font-size="11" fill="var(--text-3)">สูงสุด/วัน: ${max}</text>
    <line x1="${padL}" y1="${padT + chartH}" x2="${w - padR}" y2="${padT + chartH}" stroke="var(--border)" stroke-width="1"/>
    ${bars}
    ${labels}
  </svg>`;
}

function emptyState() {
  return `<div class="an-empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
    <p>ยังไม่มีข้อมูลการสแกน — QR แบบไดนามิกจะเริ่มนับเมื่อมีคนสแกน</p>
  </div>`;
}

function typeBadge(type) {
  const dyn = type === 'dynamic';
  return `<span class="cat-tag ${dyn ? 'type-dyn' : 'type-sta'}">${dyn ? 'ไดนามิก' : 'ปกติ'}</span>`;
}

let viewAll = false;

async function loadOverview() {
  root.innerHTML = '<div class="hist-loading">กำลังโหลด...</div>';
  const all = (viewAll && window.APP.isAdmin) ? '?all=1' : '';
  try {
    const j = await (await fetch('api/analytics.php' + all)).json();
    if (!j.ok) { root.innerHTML = '<div class="no-result">โหลดข้อมูลไม่สำเร็จ</div>'; return; }
    renderOverview(j);
  } catch (e) {
    root.innerHTML = '<div class="no-result">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

function renderOverview(j) {
  const hasScans = j.total_scans > 0;
  let topRows = '';
  if (j.top.length) {
    topRows = j.top.map(t => `
      <a class="hist-row an-toprow" style="grid-template-columns:1fr 110px 110px;text-decoration:none;color:inherit" href="analytics.php?id=${t.id}">
        <div>
          <div class="hn-name">${esc(t.name)}</div>
          ${t.owner ? `<div style="font-size:11.5px;color:var(--text-3);margin-top:2px">โดย ${esc(t.owner)}</div>` : ''}
        </div>
        <div>${typeBadge(t.type)}</div>
        <div style="text-align:right;font-weight:600">${t.scan_count.toLocaleString()}</div>
      </a>`).join('');
  } else {
    topRows = `<div class="no-result">ยังไม่มี QR ที่ถูกสแกน</div>`;
  }

  root.innerHTML = `
    <div class="stats stats-2">
      <div class="stat"><div class="sv">${j.total_scans.toLocaleString()}</div><div class="sl">ยอดสแกนรวม</div></div>
      <div class="stat"><div class="sv">${j.dynamic_count.toLocaleString()}</div><div class="sl">QR ไดนามิกทั้งหมด</div></div>
    </div>
    <div class="card card-pad an-chart-card">
      <div class="an-card-title">สแกนย้อนหลัง 30 วัน</div>
      ${hasScans ? barChart(j.daily) : emptyState()}
    </div>
    <div class="hist-table">
      <div class="hist-row head" style="grid-template-columns:1fr 110px 110px">
        <div>Top 5 ที่ถูกสแกนมากสุด</div><div>ชนิด</div><div style="text-align:right">จำนวนสแกน</div>
      </div>
      <div>${topRows}</div>
    </div>`;
}

async function loadDetail(qid) {
  root.innerHTML = '<div class="hist-loading">กำลังโหลด...</div>';
  try {
    const j = await (await fetch('api/analytics.php?id=' + encodeURIComponent(qid))).json();
    if (!j.ok) { root.innerHTML = `<div class="no-result">${j.error === 'ไม่พบ QR นี้' ? 'ไม่พบ QR นี้ หรือไม่มีสิทธิ์เข้าถึง' : 'โหลดข้อมูลไม่สำเร็จ'}</div>`; return; }
    renderDetail(j);
  } catch (e) {
    root.innerHTML = '<div class="no-result">เชื่อมต่อไม่สำเร็จ</div>';
  }
}

function renderDetail(j) {
  const hasScans = j.qr.scan_count > 0;
  let recentRows = '';
  if (j.recent.length) {
    recentRows = j.recent.map(r => `
      <div class="hist-row" style="grid-template-columns:170px 1fr">
        <div class="hist-date">${esc(r.scanned_at)}</div>
        <div style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;color:var(--text-2)">${esc(r.user_agent)}</div>
      </div>`).join('');
  } else {
    recentRows = `<div class="no-result">ยังไม่มีประวัติการสแกน</div>`;
  }

  root.innerHTML = `
    <p><a class="an-back" href="analytics.php">&larr; กลับ</a></p>
    <div class="an-detail-head">
      <h2>${esc(j.qr.name)}</h2>
      ${typeBadge(j.qr.type)}
    </div>
    <div class="stats stats-2">
      <div class="stat"><div class="sv">${j.qr.scan_count.toLocaleString()}</div><div class="sl">ยอดสแกนรวม (ทั้งหมด)</div></div>
      <div class="stat"><div class="sv">${j.daily.reduce((a, d) => a + d.count, 0).toLocaleString()}</div><div class="sl">สแกนใน 30 วันล่าสุด</div></div>
    </div>
    <div class="card card-pad an-chart-card">
      <div class="an-card-title">สแกนย้อนหลัง 30 วัน</div>
      ${hasScans ? barChart(j.daily) : emptyState()}
    </div>
    <div class="hist-table">
      <div class="hist-row head" style="grid-template-columns:170px 1fr">
        <div>เวลา</div><div>อุปกรณ์ / เบราว์เซอร์</div>
      </div>
      <div>${recentRows}</div>
    </div>`;
}

if (window.APP.isAdmin && !id) {
  const toggle = document.getElementById('admin-all-toggle');
  const sw = document.getElementById('admin-all-sw');
  if (toggle && sw) {
    toggle.addEventListener('click', () => {
      viewAll = !viewAll;
      sw.classList.toggle('on', viewAll);
      loadOverview();
    });
  }
}

if (id) loadDetail(id); else loadOverview();
})();
