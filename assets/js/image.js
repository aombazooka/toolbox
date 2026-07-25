/* ================= Toolbox — image resize/convert page (T3) =================
   Fully client-side: nothing here ever leaves the browser. Uses <canvas> +
   toBlob() to resize/re-encode images, and the local JSZip vendor lib
   (assets/vendor/jszip.min.js — same lib/pattern as assets/js/bulk.js) to
   bundle multiple results into one .zip download.
*/
(function () {
'use strict';
const $ = id => document.getElementById(id);

let uidCounter = 0;
const state = { files: [] }; // [{id,file,name,origSize,origW,origH,img,previewUrl,status,resultBlob,resultUrl,resultSize,resultW,resultH,resultExt,errorMsg}]

let resizeMode = 'px';
let outFormat = 'original';

/* ---------- helpers ---------- */
function formatBytes(n) {
  if (!isFinite(n) || n < 0) return '—';
  if (n < 1024) return n + ' B';
  if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
  return (n / 1024 / 1024).toFixed(2) + ' MB';
}

function loadImage(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => resolve({ img, url });
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('เปิดไฟล์รูปไม่สำเร็จ')); };
    img.src = url;
  });
}

function computeTargetSize(w, h, opts) {
  if (opts.mode === 'percent') {
    const f = opts.percent / 100;
    return { width: Math.max(1, Math.round(w * f)), height: Math.max(1, Math.round(h * f)) };
  }
  const maxDim = opts.maxDim;
  const longest = Math.max(w, h);
  if (longest <= maxDim) return { width: w, height: h };
  const f = maxDim / longest;
  return { width: Math.max(1, Math.round(w * f)), height: Math.max(1, Math.round(h * f)) };
}

function pickMime(format, originalType) {
  if (format === 'jpg') return { mime: 'image/jpeg', ext: 'jpg' };
  if (format === 'png') return { mime: 'image/png', ext: 'png' };
  if (format === 'webp') return { mime: 'image/webp', ext: 'webp' };
  // "original" — keep the same family as the source file when we recognize it
  if (originalType === 'image/png') return { mime: 'image/png', ext: 'png' };
  if (originalType === 'image/webp') return { mime: 'image/webp', ext: 'webp' };
  if (originalType === 'image/jpeg') return { mime: 'image/jpeg', ext: 'jpg' };
  // Unknown/animated types (gif, bmp, svg, ...) can't be re-encoded as-is via
  // canvas — fall back to PNG so transparency/quality isn't silently lost.
  return { mime: 'image/png', ext: 'png' };
}

function outputName(item) {
  const base = item.name.replace(/\.[^.\/]+$/, '') || 'image';
  return base + '.' + (item.resultExt || 'jpg');
}

function downloadBlob(blob, filename) {
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(a.href), 1500);
}

function getOptions() {
  return {
    mode: resizeMode,
    maxDim: parseInt($('opt-maxdim').value, 10) || 1920,
    percent: parseInt($('opt-percent').value, 10) || 100,
    format: outFormat,
    quality: parseInt($('opt-quality').value, 10) || 80,
  };
}

/* ---------- rendering ---------- */
function iconSvg(path, cls) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '2');
  if (cls) svg.setAttribute('class', cls);
  svg.innerHTML = path;
  return svg;
}

const ICO_DOWNLOAD = '<path d="M12 15V3m0 12l-4-4m4 4l4-4" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2" stroke-linecap="round"/>';
const ICO_TRASH = '<path d="M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m2 0v13a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z" stroke-linecap="round" stroke-linejoin="round"/>';
const ICO_ARROW = '<path d="M5 12h14m0 0l-5-5m5 5l-5 5" stroke-linecap="round" stroke-linejoin="round"/>';
const ICO_SPIN = '<path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/>';

function emptyStateNode() {
  const d = document.createElement('div');
  d.className = 'img-empty';
  d.id = 'img-empty';
  d.appendChild(iconSvg('<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M20 20v.01M14 20v.01M20 14v.01"/>'));
  const t = document.createElement('div');
  t.textContent = 'ยังไม่มีไฟล์ — ลากรูปมาวางด้านซ้าย หรือคลิกเพื่อเลือกไฟล์';
  d.appendChild(t);
  return d;
}

function statusText(item) {
  switch (item.status) {
    case 'loading': return 'กำลังเปิดไฟล์...';
    case 'pending': return 'พร้อมประมวลผล';
    case 'processing': return 'กำลังประมวลผล...';
    case 'error': return item.errorMsg || 'เกิดข้อผิดพลาด';
    case 'done': return 'เสร็จแล้ว';
    default: return '';
  }
}

function buildItemNode(item) {
  const row = document.createElement('div');
  row.className = 'img-item';
  row.dataset.id = item.id;

  const thumb = document.createElement('div');
  thumb.className = 'ii-thumb';
  const thumbSrc = item.resultUrl || item.previewUrl;
  if (thumbSrc) {
    const img = document.createElement('img');
    img.src = thumbSrc;
    img.alt = item.name;
    thumb.appendChild(img);
  } else {
    thumb.appendChild(iconSvg('<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>', ));
  }
  row.appendChild(thumb);

  const body = document.createElement('div');
  body.className = 'ii-body';

  const name = document.createElement('div');
  name.className = 'ii-name';
  name.textContent = item.name;
  body.appendChild(name);

  const meta = document.createElement('div');
  meta.className = 'ii-meta';
  if (item.origW) {
    const before = document.createElement('span');
    before.textContent = item.origW + '×' + item.origH + ' · ' + formatBytes(item.origSize);
    meta.appendChild(before);
    if (item.status === 'done') {
      meta.appendChild(iconSvg(ICO_ARROW));
      const after = document.createElement('span');
      after.textContent = item.resultW + '×' + item.resultH + ' · ' + formatBytes(item.resultSize);
      meta.appendChild(after);
      const pct = Math.round((1 - item.resultSize / item.origSize) * 100);
      const save = document.createElement('span');
      save.className = 'ii-save' + (pct < 0 ? ' worse' : '');
      save.textContent = (pct >= 0 ? '-' : '+') + Math.abs(pct) + '%';
      meta.appendChild(save);
    }
  } else {
    const before = document.createElement('span');
    before.textContent = formatBytes(item.origSize);
    meta.appendChild(before);
  }
  body.appendChild(meta);

  const status = document.createElement('div');
  status.className = 'ii-status' + (item.status === 'error' ? ' err' : '');
  status.textContent = statusText(item);
  body.appendChild(status);

  row.appendChild(body);

  const acts = document.createElement('div');
  acts.className = 'ii-acts';

  const dlBtn = document.createElement('button');
  dlBtn.type = 'button';
  dlBtn.title = 'ดาวน์โหลด';
  dlBtn.disabled = item.status !== 'done';
  if (item.status === 'processing') {
    dlBtn.appendChild(iconSvg(ICO_SPIN, 'spin'));
  } else {
    dlBtn.appendChild(iconSvg(ICO_DOWNLOAD));
  }
  dlBtn.onclick = () => {
    if (item.status !== 'done') return;
    downloadBlob(item.resultBlob, outputName(item));
    toast('ดาวน์โหลดแล้ว: ' + outputName(item));
  };
  acts.appendChild(dlBtn);

  const rmBtn = document.createElement('button');
  rmBtn.type = 'button';
  rmBtn.className = 'ii-remove';
  rmBtn.title = 'ลบออกจากรายการ';
  rmBtn.appendChild(iconSvg(ICO_TRASH));
  rmBtn.onclick = () => removeItem(item.id);
  acts.appendChild(rmBtn);

  row.appendChild(acts);
  return row;
}

function renderList() {
  const wrap = $('img-list');
  wrap.innerHTML = '';
  $('img-count').textContent = state.files.length ? `(${state.files.length} ไฟล์)` : '';
  if (!state.files.length) {
    wrap.appendChild(emptyStateNode());
    $('btn-download-all').disabled = true;
    $('btn-clear').disabled = true;
    $('btn-process').disabled = true;
    return;
  }
  state.files.forEach(item => wrap.appendChild(buildItemNode(item)));
  $('btn-download-all').disabled = !state.files.some(f => f.status === 'done');
  $('btn-clear').disabled = false;
  $('btn-process').disabled = false;
}

/* ---------- file management ---------- */
function removeItem(id) {
  const idx = state.files.findIndex(f => f.id === id);
  if (idx === -1) return;
  const item = state.files[idx];
  if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
  if (item.resultUrl) URL.revokeObjectURL(item.resultUrl);
  state.files.splice(idx, 1);
  renderList();
}

function clearAll() {
  state.files.forEach(item => {
    if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
    if (item.resultUrl) URL.revokeObjectURL(item.resultUrl);
  });
  state.files = [];
  renderList();
}

async function addFiles(fileList) {
  const arr = Array.from(fileList || []).filter(f => f.type && f.type.startsWith('image/'));
  if (!arr.length) { toast('กรุณาเลือกไฟล์รูปภาพเท่านั้น', 'err'); return; }

  const newItems = arr.map(file => ({
    id: 'f' + (++uidCounter),
    file, name: file.name, origSize: file.size, status: 'loading',
  }));
  state.files.push(...newItems);
  renderList();

  for (const item of newItems) {
    try {
      const { img, url } = await loadImage(item.file);
      item.img = img;
      item.origW = img.naturalWidth;
      item.origH = img.naturalHeight;
      item.previewUrl = url;
      item.status = 'pending';
    } catch (e) {
      item.status = 'error';
      item.errorMsg = 'เปิดไฟล์รูปไม่สำเร็จ';
    }
    renderList();
  }
  toast(`เพิ่มไฟล์แล้ว ${arr.length} ไฟล์`);
}

/* ---------- processing ---------- */
async function processOne(item, opts) {
  if (!item.img) { item.status = 'error'; item.errorMsg = 'ไม่มีข้อมูลรูปภาพ'; renderList(); return; }
  item.status = 'processing';
  renderList();
  try {
    const { mime, ext } = pickMime(opts.format, item.file.type);
    const { width, height } = computeTargetSize(item.origW, item.origH, opts);
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    if (mime === 'image/jpeg') { ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, width, height); }
    ctx.drawImage(item.img, 0, 0, width, height);
    const quality = mime === 'image/png' ? undefined : Math.min(1, Math.max(0.05, opts.quality / 100));
    const blob = await new Promise((resolve, reject) => {
      canvas.toBlob(b => (b ? resolve(b) : reject(new Error('แปลงไฟล์ไม่สำเร็จ'))), mime, quality);
    });
    if (item.resultUrl) URL.revokeObjectURL(item.resultUrl);
    item.resultBlob = blob;
    item.resultUrl = URL.createObjectURL(blob);
    item.resultSize = blob.size;
    item.resultW = width;
    item.resultH = height;
    item.resultExt = ext;
    item.status = 'done';
  } catch (e) {
    item.status = 'error';
    item.errorMsg = (e && e.message) ? e.message : 'ประมวลผลไม่สำเร็จ';
  }
  renderList();
}

async function processAll() {
  const pending = state.files.filter(f => f.status !== 'loading');
  if (!pending.length) return;
  const opts = getOptions();
  const btn = $('btn-process');
  btn.disabled = true;
  const orig = btn.innerHTML;
  btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/></svg>กำลังประมวลผล...';
  for (const item of pending) {
    await processOne(item, opts);
  }
  btn.disabled = false;
  btn.innerHTML = orig;
  const okCount = state.files.filter(f => f.status === 'done').length;
  if (okCount) toast(`ประมวลผลสำเร็จ ${okCount}/${state.files.length} ไฟล์`);
  else toast('ประมวลผลไม่สำเร็จ', 'err');
}

/* ---------- download ---------- */
async function downloadAll() {
  const done = state.files.filter(f => f.status === 'done');
  if (!done.length) { toast('ยังไม่มีไฟล์ที่ประมวลผลสำเร็จ', 'err'); return; }
  if (done.length === 1) {
    downloadBlob(done[0].resultBlob, outputName(done[0]));
    toast('ดาวน์โหลดแล้ว');
    return;
  }
  if (typeof JSZip === 'undefined') { toast('โหลดไลบรารี ZIP ไม่สำเร็จ', 'err'); return; }
  const btn = $('btn-download-all');
  btn.disabled = true;
  const orig = btn.innerHTML;
  btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/></svg>กำลังสร้าง ZIP...';
  try {
    const zip = new JSZip();
    const used = {};
    done.forEach(item => {
      let nm = outputName(item), k = 2;
      while (used[nm]) { nm = outputName(item).replace(/(\.[^.]+)$/, '_' + (k++) + '$1'); }
      used[nm] = 1;
      zip.file(nm, item.resultBlob);
    });
    const out = await zip.generateAsync({ type: 'blob' });
    downloadBlob(out, 'images_' + done.length + '.zip');
    toast(`ดาวน์โหลด ZIP แล้ว (${done.length} ไฟล์)`);
  } catch (e) {
    toast('สร้าง ZIP ไม่สำเร็จ', 'err');
  }
  btn.disabled = false;
  btn.innerHTML = orig;
}

/* ---------- controls ---------- */
function bindSeg(containerId, onChange) {
  const el = $(containerId);
  el.addEventListener('click', e => {
    const b = e.target.closest('button[data-v]');
    if (!b) return;
    el.querySelectorAll('button').forEach(x => x.classList.remove('on'));
    b.classList.add('on');
    onChange(b.dataset.v);
  });
}

bindSeg('resize-mode', v => {
  resizeMode = v;
  $('resize-px-field').classList.toggle('hidden', v !== 'px');
  $('resize-percent-field').classList.toggle('hidden', v !== 'percent');
});

bindSeg('out-format', v => {
  outFormat = v;
  $('quality-field').classList.toggle('hidden', v === 'png');
});

$('opt-maxdim').addEventListener('input', e => { $('maxdim-val').textContent = e.target.value; });
$('opt-percent').addEventListener('input', e => { $('percent-val').textContent = e.target.value; });
$('opt-quality').addEventListener('input', e => { $('quality-val').textContent = e.target.value; });

/* ---------- drop zone / file input ---------- */
const dropEl = $('img-drop');
const inputEl = $('img-input');

dropEl.addEventListener('click', () => inputEl.click());
['dragenter', 'dragover'].forEach(evt => dropEl.addEventListener(evt, e => {
  e.preventDefault(); e.stopPropagation(); dropEl.classList.add('drag');
}));
['dragleave', 'drop'].forEach(evt => dropEl.addEventListener(evt, e => {
  e.preventDefault(); e.stopPropagation(); dropEl.classList.remove('drag');
}));
dropEl.addEventListener('drop', e => {
  const dt = e.dataTransfer;
  if (dt && dt.files && dt.files.length) addFiles(dt.files);
});
inputEl.addEventListener('change', e => {
  if (e.target.files && e.target.files.length) addFiles(e.target.files);
  e.target.value = '';
});

$('btn-process').onclick = processAll;
$('btn-download-all').onclick = downloadAll;
$('btn-clear').onclick = clearAll;

renderList();
})();
