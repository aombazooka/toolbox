/* ================= Toolbox — pdf.php (T4: รูป→PDF / รวม PDF / แยกหน้า) =================
   Fully client-side: nothing here ever leaves the browser. Uses the local
   pdf-lib vendor lib (assets/vendor/pdf-lib.min.js) for all PDF creation,
   merging and page extraction. Images are decoded via <canvas> only when
   pdf-lib can't embed the source format directly (anything other than
   JPEG/PNG gets re-encoded to PNG first).
*/
(function () {
'use strict';
const $ = id => document.getElementById(id);
const { PDFDocument } = window.PDFLib || {};

let uidCounter = 0;
let activeTab = 'img2pdf';
let pageSizeMode = 'a4';

const i2p = { items: [] };   // [{id,file,name,size,previewUrl,img}]
const mg  = { items: [] };   // [{id,file,name,size}]

let result = null; // { blob, url, name, pages, size }

/* ---------- small helpers ---------- */
function formatBytes(n) {
  if (!isFinite(n) || n < 0) return '—';
  if (n < 1024) return n + ' B';
  if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
  return (n / 1024 / 1024).toFixed(2) + ' MB';
}

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

const ICO_UP    = '<path d="M12 19V5m0 0l-6 6m6-6l6 6" stroke-linecap="round" stroke-linejoin="round"/>';
const ICO_DOWN  = '<path d="M12 5v14m0 0l-6-6m6 6l6-6" stroke-linecap="round" stroke-linejoin="round"/>';
const ICO_TRASH = '<path d="M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m2 0v13a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z" stroke-linecap="round" stroke-linejoin="round"/>';
const ICO_IMG   = '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>';
const ICO_PDF   = '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/>';

function downloadBlob(blob, filename) {
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(a.href), 1500);
}

/* ---------- generic reorderable file list (used by img2pdf + merge tabs) ---------- */
function buildListItem(item, index, total, opts) {
  const row = document.createElement('div');
  row.className = 'img-item';
  row.dataset.id = item.id;

  const thumb = document.createElement('div');
  thumb.className = 'ii-thumb';
  if (opts.thumbUrl && item.previewUrl) {
    const img = document.createElement('img');
    img.src = item.previewUrl;
    img.alt = item.name;
    thumb.appendChild(img);
  } else {
    thumb.appendChild(iconSvg(opts.icon || ICO_IMG));
  }
  row.appendChild(thumb);

  const body = document.createElement('div');
  body.className = 'ii-body';
  const name = document.createElement('div');
  name.className = 'ii-name';
  name.textContent = (index + 1) + '. ' + item.name;
  body.appendChild(name);
  const meta = document.createElement('div');
  meta.className = 'ii-meta';
  meta.textContent = formatBytes(item.size);
  body.appendChild(meta);
  row.appendChild(body);

  const acts = document.createElement('div');
  acts.className = 'ii-acts';

  const upBtn = document.createElement('button');
  upBtn.type = 'button';
  upBtn.title = 'เลื่อนขึ้น';
  upBtn.disabled = index === 0;
  upBtn.appendChild(iconSvg(ICO_UP));
  upBtn.onclick = () => opts.onMove(item.id, -1);
  acts.appendChild(upBtn);

  const downBtn = document.createElement('button');
  downBtn.type = 'button';
  downBtn.title = 'เลื่อนลง';
  downBtn.disabled = index === total - 1;
  downBtn.appendChild(iconSvg(ICO_DOWN));
  downBtn.onclick = () => opts.onMove(item.id, 1);
  acts.appendChild(downBtn);

  const rmBtn = document.createElement('button');
  rmBtn.type = 'button';
  rmBtn.className = 'ii-remove';
  rmBtn.title = 'ลบออกจากรายการ';
  rmBtn.appendChild(iconSvg(ICO_TRASH));
  rmBtn.onclick = () => opts.onRemove(item.id);
  acts.appendChild(rmBtn);

  row.appendChild(acts);
  return row;
}

function renderList(containerId, items, opts) {
  const wrap = $(containerId);
  wrap.innerHTML = '';
  if (!items.length) return;
  items.forEach((item, i) => wrap.appendChild(buildListItem(item, i, items.length, opts)));
}

function moveItem(list, id, dir) {
  const idx = list.findIndex(x => x.id === id);
  if (idx === -1) return;
  const to = idx + dir;
  if (to < 0 || to >= list.length) return;
  const [it] = list.splice(idx, 1);
  list.splice(to, 0, it);
}

/* ---------- image -> PDF tab ---------- */
function loadImageEl(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => resolve({ img, url });
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('เปิดไฟล์รูปไม่สำเร็จ')); };
    img.src = url;
  });
}

function renderI2p() {
  renderList('i2p-list', i2p.items, {
    thumbUrl: true, icon: ICO_IMG,
    onMove: (id, dir) => { moveItem(i2p.items, id, dir); renderI2p(); },
    onRemove: (id) => {
      const idx = i2p.items.findIndex(x => x.id === id);
      if (idx > -1) {
        if (i2p.items[idx].previewUrl) URL.revokeObjectURL(i2p.items[idx].previewUrl);
        i2p.items.splice(idx, 1);
      }
      renderI2p();
    },
  });
  $('i2p-btn').disabled = i2p.items.length === 0;
}

async function addImages(fileList) {
  const arr = Array.from(fileList || []).filter(f => f.type && f.type.startsWith('image/'));
  if (!arr.length) { toast('กรุณาเลือกไฟล์รูปภาพเท่านั้น', 'err'); return; }
  for (const file of arr) {
    try {
      const { img, url } = await loadImageEl(file);
      i2p.items.push({ id: 'i' + (++uidCounter), file, name: file.name, size: file.size, previewUrl: url, img });
    } catch (e) {
      toast('เปิดไฟล์ไม่สำเร็จ: ' + file.name, 'err');
    }
  }
  renderI2p();
  toast(`เพิ่มรูปแล้ว ${arr.length} ไฟล์`);
}

async function imageFileToPngBytes(img) {
  const canvas = document.createElement('canvas');
  canvas.width = img.naturalWidth;
  canvas.height = img.naturalHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0);
  const blob = await new Promise((resolve, reject) => {
    canvas.toBlob(b => (b ? resolve(b) : reject(new Error('แปลงรูปไม่สำเร็จ'))), 'image/png');
  });
  return new Uint8Array(await blob.arrayBuffer());
}

async function embedImage(pdfDoc, item) {
  const type = (item.file.type || '').toLowerCase();
  if (type === 'image/jpeg' || type === 'image/jpg') {
    return pdfDoc.embedJpg(await item.file.arrayBuffer());
  }
  if (type === 'image/png') {
    return pdfDoc.embedPng(await item.file.arrayBuffer());
  }
  // any other format (webp, gif, bmp, ...) — re-encode via canvas to PNG first
  return pdfDoc.embedPng(await imageFileToPngBytes(item.img));
}

async function imagesToPdf(items, mode) {
  const pdfDoc = await PDFDocument.create();
  const A4_W = 595.28, A4_H = 841.89, MARGIN = 24;
  for (const item of items) {
    const embedded = await embedImage(pdfDoc, item);
    const iw = embedded.width, ih = embedded.height;
    if (mode === 'fit') {
      const page = pdfDoc.addPage([iw, ih]);
      page.drawImage(embedded, { x: 0, y: 0, width: iw, height: ih });
    } else {
      const landscape = iw > ih;
      const pw = landscape ? A4_H : A4_W;
      const ph = landscape ? A4_W : A4_H;
      const maxW = pw - MARGIN * 2, maxH = ph - MARGIN * 2;
      const scale = Math.min(maxW / iw, maxH / ih, 1) || 1;
      const dw = iw * scale, dh = ih * scale;
      const page = pdfDoc.addPage([pw, ph]);
      page.drawImage(embedded, { x: (pw - dw) / 2, y: (ph - dh) / 2, width: dw, height: dh });
    }
  }
  const bytes = await pdfDoc.save();
  return new Blob([bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength)], { type: 'application/pdf' });
}

/* ---------- merge tab ---------- */
function renderMg() {
  renderList('mg-list', mg.items, {
    thumbUrl: false, icon: ICO_PDF,
    onMove: (id, dir) => { moveItem(mg.items, id, dir); renderMg(); },
    onRemove: (id) => {
      const idx = mg.items.findIndex(x => x.id === id);
      if (idx > -1) mg.items.splice(idx, 1);
      renderMg();
    },
  });
  $('mg-btn').disabled = mg.items.length < 2;
}

function isPdfFile(f) {
  return (f.type === 'application/pdf') || /\.pdf$/i.test(f.name || '');
}

function addMergeFiles(fileList) {
  const arr = Array.from(fileList || []).filter(isPdfFile);
  if (!arr.length) { toast('กรุณาเลือกไฟล์ PDF เท่านั้น', 'err'); return; }
  arr.forEach(file => mg.items.push({ id: 'm' + (++uidCounter), file, name: file.name, size: file.size }));
  renderMg();
  toast(`เพิ่มไฟล์ PDF แล้ว ${arr.length} ไฟล์`);
}

async function mergePdfs(items) {
  const mergedDoc = await PDFDocument.create();
  for (const item of items) {
    const bytes = await item.file.arrayBuffer();
    const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
    const copied = await mergedDoc.copyPages(srcDoc, srcDoc.getPageIndices());
    copied.forEach(p => mergedDoc.addPage(p));
  }
  const bytes = await mergedDoc.save();
  return new Blob([bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength)], { type: 'application/pdf' });
}

/* ---------- split tab ---------- */
function parsePageRanges(input, maxPage) {
  const result = [];
  const seen = new Set();
  const tokens = String(input || '').split(',').map(s => s.trim()).filter(Boolean);
  // เว้นว่าง = เลือกทุกหน้า (1..maxPage)
  if (!tokens.length) {
    for (let p = 1; p <= maxPage; p++) result.push(p - 1);
    return result;
  }
  for (const tok of tokens) {
    const range = tok.match(/^(\d+)\s*-\s*(\d+)$/);
    if (range) {
      let a = parseInt(range[1], 10), b = parseInt(range[2], 10);
      if (a > b) { const t = a; a = b; b = t; }
      for (let p = a; p <= b; p++) {
        if (p >= 1 && p <= maxPage && !seen.has(p)) { seen.add(p); result.push(p - 1); }
      }
    } else if (/^\d+$/.test(tok)) {
      const p = parseInt(tok, 10);
      if (p < 1 || p > maxPage) throw new Error('หมายเลขหน้า ' + p + ' เกินขอบเขต (มีทั้งหมด ' + maxPage + ' หน้า)');
      if (!seen.has(p)) { seen.add(p); result.push(p - 1); }
    } else {
      throw new Error('รูปแบบช่วงหน้าไม่ถูกต้อง: "' + tok + '"');
    }
  }
  if (!result.length) throw new Error('ไม่พบหมายเลขหน้าที่ถูกต้องในช่วงที่ระบุ');
  return result;
}

/* ตัวจัดการ "แผงไฟล์ PDF เดียว + ช่วงหน้า" ใช้ร่วมกันทั้งแท็บแยกหน้า (sp) และดึงหน้า (ex)
 * รับ prefix ของ id ใน HTML: <prefix>-list, <prefix>-range, <prefix>-btn, <prefix>-hint */
function makePdfPanel(prefix) {
  const ctrl = { file: null, name: '', size: 0, totalPages: 0, loading: false };

  ctrl.render = function () {
    const wrap = $(prefix + '-list');
    wrap.innerHTML = '';
    const rangeInput = $(prefix + '-range');
    const btn = $(prefix + '-btn');
    const hint = $(prefix + '-hint');
    if (!ctrl.file) {
      rangeInput.disabled = true;
      btn.disabled = true;
      hint.textContent = 'เลือกไฟล์ PDF ก่อนเพื่อดูจำนวนหน้าทั้งหมด — ใส่ช่วงหน้าคั่นด้วยจุลภาค เช่น 1-3,5';
      return;
    }
    const row = document.createElement('div');
    row.className = 'img-item';
    const thumb = document.createElement('div');
    thumb.className = 'ii-thumb';
    thumb.appendChild(iconSvg(ICO_PDF));
    row.appendChild(thumb);
    const body = document.createElement('div');
    body.className = 'ii-body';
    const name = document.createElement('div');
    name.className = 'ii-name';
    name.textContent = ctrl.name;
    body.appendChild(name);
    const meta = document.createElement('div');
    meta.className = 'ii-meta';
    meta.textContent = ctrl.loading ? 'กำลังอ่านไฟล์...' : formatBytes(ctrl.size) + (ctrl.totalPages ? ' · ทั้งหมด ' + ctrl.totalPages + ' หน้า' : '');
    body.appendChild(meta);
    row.appendChild(body);
    const acts = document.createElement('div');
    acts.className = 'ii-acts';
    const rmBtn = document.createElement('button');
    rmBtn.type = 'button';
    rmBtn.className = 'ii-remove';
    rmBtn.title = 'ลบไฟล์';
    rmBtn.appendChild(iconSvg(ICO_TRASH));
    rmBtn.onclick = () => { ctrl.file = null; ctrl.totalPages = 0; ctrl.render(); };
    acts.appendChild(rmBtn);
    row.appendChild(acts);
    wrap.appendChild(row);

    if (ctrl.loading) {
      rangeInput.disabled = true;
      btn.disabled = true;
      hint.textContent = 'กำลังอ่านไฟล์ PDF...';
    } else {
      rangeInput.disabled = false;
      hint.textContent = 'ไฟล์นี้มีทั้งหมด ' + ctrl.totalPages + ' หน้า — ใส่ช่วงหน้าคั่นด้วยจุลภาค เช่น 1-3,5 หรือเว้นว่างเพื่อทำทุกหน้า';
      btn.disabled = false;
    }
  };

  ctrl.setFile = async function (file) {
    if (!isPdfFile(file)) { toast('กรุณาเลือกไฟล์ PDF เท่านั้น', 'err'); return; }
    ctrl.file = file;
    ctrl.name = file.name;
    ctrl.size = file.size;
    ctrl.totalPages = 0;
    ctrl.loading = true;
    ctrl.render();
    try {
      const bytes = await file.arrayBuffer();
      const doc = await PDFDocument.load(bytes, { ignoreEncryption: true });
      ctrl.totalPages = doc.getPageCount();
    } catch (e) {
      toast('เปิดไฟล์ PDF ไม่สำเร็จ (ไฟล์อาจเสียหายหรือมีรหัสผ่าน)', 'err');
      ctrl.file = null;
    }
    ctrl.loading = false;
    ctrl.render();
  };

  return ctrl;
}

const sp = makePdfPanel('sp'); // แท็บ "แยกหน้า" → ไฟล์ละหน้า (ZIP)
const ex = makePdfPanel('ex'); // แท็บ "ดึงหน้า" → รวมหน้าที่เลือกเป็นไฟล์เดียว

function bytesToBlob(outBytes, type) {
  return new Blob([outBytes.buffer.slice(outBytes.byteOffset, outBytes.byteOffset + outBytes.byteLength)], { type: type });
}

/* รวมหน้าที่เลือกเป็น PDF ไฟล์เดียว */
async function splitPdfCombine(file, rangeStr) {
  const bytes = await file.arrayBuffer();
  const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
  const total = srcDoc.getPageCount();
  const indices = parsePageRanges(rangeStr, total);
  const newDoc = await PDFDocument.create();
  const pages = await newDoc.copyPages(srcDoc, indices);
  pages.forEach(p => newDoc.addPage(p));
  const outBytes = await newDoc.save();
  return { blob: bytesToBlob(outBytes, 'application/pdf'), count: indices.length };
}

/* แยกแต่ละหน้าเป็น PDF ไฟล์ละหน้า แล้วรวมเป็น .zip */
async function splitPdfSeparate(file, rangeStr, baseName) {
  if (typeof JSZip === 'undefined') throw new Error('โหลดไลบรารี ZIP ไม่สำเร็จ กรุณารีเฟรชหน้า');
  const bytes = await file.arrayBuffer();
  const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
  const total = srcDoc.getPageCount();
  const indices = parsePageRanges(rangeStr, total);
  const zip = new JSZip();
  const pad = String(total).length; // เลขหน้าเติมศูนย์นำหน้าให้เรียงถูก เช่น page_01.pdf
  for (const idx of indices) {
    const doc = await PDFDocument.create();
    const [pg] = await doc.copyPages(srcDoc, [idx]);
    doc.addPage(pg);
    const outBytes = await doc.save();
    const n = String(idx + 1).padStart(pad, '0');
    zip.file(baseName + '_page_' + n + '.pdf', bytesToBlob(outBytes, 'application/pdf'));
  }
  const zipBlob = await zip.generateAsync({ type: 'blob' });
  return { blob: zipBlob, count: indices.length };
}

/* ---------- result panel ---------- */
function showResult(blob, name, pages, opts) {
  opts = opts || {};
  const isZip = !!opts.zip;
  if (result && result.url) URL.revokeObjectURL(result.url);
  const url = URL.createObjectURL(blob);
  result = { blob, url, name, pages, size: blob.size };
  $('result-empty').classList.add('hidden');
  $('result-loading').classList.add('hidden');
  $('result-body').classList.remove('hidden');
  // ZIP แสดงตัวอย่างในเบราว์เซอร์ไม่ได้ — ซ่อน iframe แล้วโชว์ป้าย ZIP แทน
  $('result-stage').classList.toggle('hidden', isZip);
  $('result-zip').classList.toggle('hidden', !isZip);
  if (isZip) {
    $('result-zip-text').textContent = 'ไฟล์ ZIP พร้อมดาวน์โหลด — มี ' + pages + ' ไฟล์ (ไฟล์ละหน้า)';
    $('result-pages').textContent = pages + ' ไฟล์';
    $('btn-download-label').textContent = 'ดาวน์โหลด ZIP';
  } else {
    $('result-frame').src = url;
    $('result-pages').textContent = pages + ' หน้า';
    $('btn-download-label').textContent = 'ดาวน์โหลด PDF';
  }
  $('result-name').textContent = name;
  $('result-size').textContent = formatBytes(blob.size);
  $('btn-reset').disabled = false;
}

function showLoading() {
  $('result-empty').classList.add('hidden');
  $('result-body').classList.add('hidden');
  $('result-loading').classList.remove('hidden');
}

function showEmpty() {
  if (result && result.url) URL.revokeObjectURL(result.url);
  result = null;
  $('result-loading').classList.add('hidden');
  $('result-body').classList.add('hidden');
  $('result-empty').classList.remove('hidden');
  $('btn-reset').disabled = true;
}

async function runWithButton(btn, fn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/></svg>กำลังประมวลผล...';
  showLoading();
  try {
    await fn();
  } catch (e) {
    toast((e && e.message) ? e.message : 'ประมวลผลไม่สำเร็จ', 'err');
    showEmpty();
  } finally {
    btn.disabled = false;
    btn.innerHTML = orig;
  }
}

/* ---------- tab switching ---------- */
function switchTab(tab) {
  activeTab = tab;
  ['img2pdf', 'merge', 'split', 'extract'].forEach(t => {
    $('tab-' + t).classList.toggle('hidden', t !== tab);
  });
}

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

bindSeg('pdf-tabs', switchTab);
bindSeg('i2p-pagesize', v => { pageSizeMode = v; });

/* ---------- drop zone wiring ---------- */
function wireDropZone(dropId, inputId, onFiles) {
  const dropEl = $(dropId), inputEl = $(inputId);
  dropEl.addEventListener('click', () => inputEl.click());
  ['dragenter', 'dragover'].forEach(evt => dropEl.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation(); dropEl.classList.add('drag');
  }));
  ['dragleave', 'drop'].forEach(evt => dropEl.addEventListener(evt, e => {
    e.preventDefault(); e.stopPropagation(); dropEl.classList.remove('drag');
  }));
  dropEl.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) onFiles(dt.files);
  });
  inputEl.addEventListener('change', e => {
    if (e.target.files && e.target.files.length) onFiles(e.target.files);
    e.target.value = '';
  });
}

wireDropZone('i2p-drop', 'i2p-input', addImages);
wireDropZone('mg-drop', 'mg-input', addMergeFiles);
wireDropZone('sp-drop', 'sp-input', fl => sp.setFile(fl[0]));
wireDropZone('ex-drop', 'ex-input', fl => ex.setFile(fl[0]));

// เว้นว่างได้ (= ทุกหน้า) ขอแค่มีไฟล์และอ่านไฟล์เสร็จแล้ว
$('sp-range').addEventListener('input', () => { $('sp-btn').disabled = !sp.file || sp.loading; });
$('ex-range').addEventListener('input', () => { $('ex-btn').disabled = !ex.file || ex.loading; });

/* ---------- action buttons ---------- */
$('i2p-btn').onclick = () => runWithButton($('i2p-btn'), async () => {
  if (!i2p.items.length) { toast('กรุณาเลือกรูปอย่างน้อย 1 ไฟล์', 'err'); return; }
  const blob = await imagesToPdf(i2p.items, pageSizeMode);
  showResult(blob, 'images.pdf', i2p.items.length);
  toast('รวมเป็น PDF สำเร็จ (' + i2p.items.length + ' หน้า)');
});

$('mg-btn').onclick = () => runWithButton($('mg-btn'), async () => {
  if (mg.items.length < 2) { toast('กรุณาเลือกไฟล์ PDF อย่างน้อย 2 ไฟล์', 'err'); return; }
  const blob = await mergePdfs(mg.items);
  const doc = await PDFDocument.load(await blob.arrayBuffer());
  const pages = doc.getPageCount();
  showResult(blob, 'merged.pdf', pages);
  toast('รวม PDF สำเร็จ (' + pages + ' หน้า)');
});

// แท็บ "แยกหน้า" → แยกเป็น PDF ไฟล์ละหน้า รวมมาเป็น .zip
$('sp-btn').onclick = () => runWithButton($('sp-btn'), async () => {
  if (!sp.file) { toast('กรุณาเลือกไฟล์ PDF ก่อน', 'err'); return; }
  const base = (sp.name || 'document').replace(/\.pdf$/i, '');
  const { blob, count } = await splitPdfSeparate(sp.file, $('sp-range').value, base);
  showResult(blob, base + '_pages.zip', count, { zip: true });
  toast('แยกหน้าสำเร็จ — ได้ ' + count + ' ไฟล์ (ไฟล์ละหน้า)');
});

// แท็บ "ดึงหน้า" → เลือกเฉพาะบางหน้ารวมเป็น PDF ไฟล์เดียว
$('ex-btn').onclick = () => runWithButton($('ex-btn'), async () => {
  if (!ex.file) { toast('กรุณาเลือกไฟล์ PDF ก่อน', 'err'); return; }
  const base = (ex.name || 'document').replace(/\.pdf$/i, '');
  const { blob, count } = await splitPdfCombine(ex.file, $('ex-range').value);
  showResult(blob, base + '_extract.pdf', count);
  toast('ดึงหน้าที่เลือกสำเร็จ (' + count + ' หน้า)');
});

$('btn-download').onclick = () => {
  if (!result) return;
  downloadBlob(result.blob, result.name);
  toast('ดาวน์โหลดแล้ว: ' + result.name);
};

$('btn-reset').onclick = () => { showEmpty(); };

if (!PDFDocument) {
  toast('โหลดไลบรารี PDF ไม่สำเร็จ กรุณารีเฟรชหน้า', 'err');
}
})();
