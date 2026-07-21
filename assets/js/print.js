/* ================= QR Studio — A4 print sheet ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);
const grid = $('print-grid');

function esc(s) { return (s || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

function buildOpts(item, size) {
  const st = item.style || {};
  const dot = st.dotColor || '#191c24';
  return {
    width: size, height: size, type: 'svg',
    data: item.qr_data || 'https://example.com',
    dotsOptions: { color: dot, type: st.dotType || 'rounded' },
    backgroundOptions: { color: '#ffffff' },
    cornersSquareOptions: { type: st.dotType === 'square' ? 'square' : 'extra-rounded', color: dot },
    cornersDotOptions: { color: dot },
    qrOptions: { errorCorrectionLevel: st.ec || 'M' }
  };
}

async function load() {
  const p = new URLSearchParams(location.search);
  const q = encodeURIComponent(p.get('q') || '');
  const c = encodeURIComponent(p.get('category') || '');
  try {
    const j = await (await fetch(`api/list.php?q=${q}&category=${c}`)).json();
    if (!j.ok || !j.items.length) {
      grid.innerHTML = '<div class="no-result" style="grid-column:1/-1">ยังไม่มี QR ให้พิมพ์ — สร้างก่อนที่หน้า "สร้าง QR"</div>';
      return;
    }
    render(j.items);
  } catch (e) {
    grid.innerHTML = '<div class="no-result" style="grid-column:1/-1">โหลดข้อมูลไม่สำเร็จ</div>';
  }
}

function render(items) {
  grid.innerHTML = '';
  items.forEach(item => {
    const cell = document.createElement('div');
    cell.className = 'p-cell';
    cell.innerHTML = `
      <div class="pq"></div>
      <div class="pl">${esc(item.name)}</div>
      <div class="psub plink">${esc(item.destination_url)}</div>`;
    new QRCodeStyling(buildOpts(item, 300)).append(cell.querySelector('.pq'));
    grid.appendChild(cell);
  });
}

/* ---- controls ---- */
document.querySelectorAll('#cols button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#cols button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  grid.style.setProperty('--cols', b.dataset.v);
});
$('opt-labels').onchange = e => grid.classList.toggle('no-labels', !e.target.checked);
$('opt-lines').onchange  = e => grid.classList.toggle('no-lines', !e.target.checked);
$('opt-link').onchange   = e => grid.classList.toggle('show-link', e.target.checked);
$('do-print').onclick    = () => window.print();

load();
})();
