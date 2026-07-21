/* ================= QR Studio — bulk page ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);
const DOT = '#000000';
const DEFAULT_CATEGORY = 'เอกสารทั่วไป';
let rows = [];

/* ---- CSV parsing (quote-aware, one line) ---- */
function splitCSVLine(line) {
  const out = [];
  let cur = '', inQuotes = false;
  for (let i = 0; i < line.length; i++) {
    const c = line[i];
    if (inQuotes) {
      if (c === '"') {
        if (line[i + 1] === '"') { cur += '"'; i++; }
        else inQuotes = false;
      } else cur += c;
    } else {
      if (c === '"') inQuotes = true;
      else if (c === ',') { out.push(cur); cur = ''; }
      else cur += c;
    }
  }
  out.push(cur);
  return out;
}

/* Derive a display name from a bare URL (strip protocol, cap length). */
function deriveName(url) {
  return String(url).replace(/^https?:\/\//, '').slice(0, 60);
}

/* fields: array of raw (untrimmed) strings in [name, url, category] order
   (or just [url] when the line had no comma). Returns {name,url,category}
   or null if there's no usable url. */
function rowFromFields(fields) {
  fields = fields.map(f => (f === null || f === undefined) ? '' : String(f).trim());
  if (fields.length <= 1) {
    const url = fields[0] || '';
    if (!url) return null;
    return { name: deriveName(url), url: url, category: '' };
  }
  const url = fields[1] || '';
  if (!url) return null;
  const rawName = fields[0] || '';
  return { name: rawName || deriveName(url), url: url, category: fields[2] || '' };
}

/* Parse raw pasted/CSV/TXT text into [{name,url,category}]. One record per
   line: "url" OR "name,url" OR "name,url,category" (quoted fields with
   embedded commas are supported). Blank lines are ignored. Both the paste
   textarea and file-import paths funnel through this single function. */
function parseRows(text, skipHeader) {
  let lines = String(text || '').replace(/\r\n?/g, '\n').split('\n');
  if (skipHeader && lines.length) lines = lines.slice(1);
  const out = [];
  for (const raw of lines) {
    const line = raw.trim();
    if (!line) continue;
    const row = rowFromFields(splitCSVLine(line));
    if (row) out.push(row);
  }
  return out;
}

/* Turn a sheet_to_json({header:1}) array-of-arrays back into CSV-ish text
   so the .xlsx path can funnel through the same parseRows(). */
function toCSVField(v) {
  const s = (v === null || v === undefined) ? '' : String(v);
  if (/[",\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
  return s;
}
function aoaToText(aoa) {
  return aoa
    .filter(r => Array.isArray(r) && r.length)
    .map(r => r.map(toCSVField).join(','))
    .join('\n');
}

function sanitizeFilename(s) {
  return String(s).replace(/[^a-z0-9]/gi, '_').slice(0, 40) || 'qr';
}

function optsFor(url, size) {
  return {
    width: size, height: size, type: 'svg', data: url,
    dotsOptions: { color: DOT, type: 'rounded' },
    backgroundOptions: { color: '#ffffff' },
    cornersSquareOptions: { type: 'extra-rounded', color: DOT }
  };
}

function currentRows() {
  return parseRows($('bulk-text').value, $('bulk-skip-header').checked);
}

/* ---- file import (CSV/TXT/XLSX) → populates the textarea ---- */
$('bulk-file-btn').onclick = () => $('bulk-file').click();
$('bulk-file').onchange = async (e) => {
  const file = e.target.files && e.target.files[0];
  if (!file) return;
  $('bulk-file-name').textContent = file.name;
  const ext = (file.name.split('.').pop() || '').toLowerCase();
  try {
    if (ext === 'xlsx') {
      if (typeof XLSX === 'undefined') { toast('โหลดไลบรารี Excel ไม่สำเร็จ', 'err'); return; }
      const buf = await file.arrayBuffer();
      const wb = XLSX.read(buf, { type: 'array' });
      const sheet = wb.Sheets[wb.SheetNames[0]];
      const aoa = XLSX.utils.sheet_to_json(sheet, { header: 1 });
      $('bulk-text').value = aoaToText(aoa);
    } else {
      $('bulk-text').value = await file.text();
    }
    toast('นำเข้าไฟล์แล้ว: ' + file.name);
  } catch (err) {
    toast('อ่านไฟล์ไม่สำเร็จ', 'err');
  }
  e.target.value = '';
};

$('bulk-gen').onclick = () => {
  rows = currentRows();
  const out = $('bulk-out'); out.innerHTML = '';
  $('bulk-count').textContent = `(${rows.length} รายการ)`;
  if (!rows.length) { out.innerHTML = '<div class="bulk-empty">ยังไม่มีลิงก์</div>'; return; }
  rows.forEach(r => {
    const item = document.createElement('div');
    item.className = 'bulk-item';
    const q = document.createElement('div'); q.className = 'bq';
    const box = document.createElement('div'); q.appendChild(box);
    const name = document.createElement('div'); name.className = 'bl'; name.textContent = r.name;
    const dl = document.createElement('button');
    dl.type = 'button'; dl.className = 'link-btn'; dl.textContent = 'ดาวน์โหลด';
    dl.style.marginTop = '6px'; dl.style.fontSize = '12px';
    const qrc = new QRCodeStyling(optsFor(r.url, 128));
    qrc.append(box);
    dl.onclick = () => new QRCodeStyling(optsFor(r.url, 1024)).download({ name: sanitizeFilename(r.name), extension: 'png' });
    item.append(q, name, dl);
    out.appendChild(item);
  });
};

$('bulk-zip').onclick = async () => {
  const list = currentRows();
  if (!list.length) { toast('ยังไม่มีลิงก์', 'err'); return; }
  if (typeof JSZip === 'undefined') { toast('โหลดไลบรารี ZIP ไม่สำเร็จ', 'err'); return; }
  const btn = $('bulk-zip'); btn.disabled = true; const orig = btn.innerHTML;
  btn.innerHTML = '<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.2-8.5" stroke-linecap="round"/></svg>กำลังสร้าง...';
  try {
    const zip = new JSZip();
    const used = {};
    for (let i = 0; i < list.length; i++) {
      const blob = await new QRCodeStyling(optsFor(list[i].url, 1024)).getRawData('png');
      let base = sanitizeFilename(list[i].name);
      let nm = base, k = 2; while (used[nm]) nm = base + '_' + (k++); used[nm] = 1;
      zip.file((i + 1) + '_' + nm + '.png', blob);
    }
    const out = await zip.generateAsync({ type: 'blob' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(out);
    a.download = 'qrcodes_' + list.length + '.zip';
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1500);
    toast(`ดาวน์โหลด ZIP แล้ว (${list.length} ไฟล์)`);
  } catch (e) {
    toast('สร้าง ZIP ไม่สำเร็จ', 'err');
  }
  btn.disabled = false; btn.innerHTML = orig;
};

$('bulk-save').onclick = async () => {
  const list = currentRows();
  if (!list.length) { toast('ยังไม่มีลิงก์', 'err'); return; }
  const btn = $('bulk-save'); btn.disabled = true;
  let ok = 0;
  for (const r of list) {
    try {
      const j = await (await fetch('api/save.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf: window.APP.csrf, name: r.name.slice(0, 60),
          category: r.category || DEFAULT_CATEGORY, type: 'static', destination_url: r.url,
          style_json: { dotColor: DOT, bgColor: '#ffffff', dotType: 'rounded', ec: 'M', logoSize: 40, size: 1024 }
        })
      })).json();
      if (j.ok) ok++;
    } catch (e) { /* skip */ }
  }
  btn.disabled = false;
  toast(`บันทึกลงประวัติแล้ว ${ok}/${list.length} รายการ`);
};
})();
