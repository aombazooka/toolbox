/* ================= QR Studio — create page ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);

const S = {
  name: '', link: 'https://example.com/document', category: 'เอกสารทั่วไป',
  type: 'static', code: '',
  dotColor: '#000000', bgColor: '#ffffff', dotType: 'rounded', ec: 'M',
  logo: null, logoSize: 40, size: 512, expiresAt: ''
};
let savedId = null;                 // id of the last saved record (null = unsaved)
let editMode = false, editId = null; // editing an existing record (qr.php?edit=ID)
const isGuest = !!(window.APP && window.APP.isGuest); // no login: static create + download only

function randCode(len) {
  const a = '23456789abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
  const b = new Uint8Array(len || 7); crypto.getRandomValues(b);
  let s = ''; for (const n of b) s += a[n % a.length]; return s;
}
/* DB "YYYY-MM-DD HH:MM:SS" -> datetime-local value "YYYY-MM-DDTHH:MM" */
function dbDateToLocalInput(dt) {
  if (!dt) return '';
  const m = String(dt).match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
  return m ? m[1] + 'T' + m[2] : '';
}
const dynLink = () => (window.APP.baseUrl) + '/r.php?c=' + S.code;
const qrData  = () => S.type === 'dynamic' ? dynLink() : (S.link || 'https://example.com');

function opts(sz) {
  const s = sz || S.size;
  return {
    width: s, height: s, type: 'svg',
    data: qrData(),
    image: S.logo || undefined,
    dotsOptions: { color: S.dotColor, type: S.dotType },
    backgroundOptions: { color: S.bgColor },
    cornersSquareOptions: { type: S.dotType === 'square' ? 'square' : 'extra-rounded', color: S.dotColor },
    cornersDotOptions: { color: S.dotColor },
    imageOptions: { margin: 5, imageSize: S.logoSize / 100, hideBackgroundDots: true },
    qrOptions: { errorCorrectionLevel: S.logo ? 'H' : S.ec }
  };
}
const qr = new QRCodeStyling(opts(225));
qr.append($('qr-preview'));

function previewPx() { return Math.round(Math.min(300, Math.max(180, 140 + S.size / 6))); }
function render() {
  qr.update(opts(previewPx()));
  $('pm-name').textContent = S.name || 'ยังไม่มีชื่อ';
  $('pm-link').textContent = qrData();
  if (S.type === 'dynamic') {
    $('dyn-link-box').classList.remove('hidden');
    $('dyn-link').value = dynLink();
  } else {
    $('dyn-link-box').classList.add('hidden');
  }
}

/* ---- QR type ---- */
document.querySelectorAll('#qrtype button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#qrtype button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  S.type = b.dataset.v;
  if (S.type === 'dynamic' && !S.code) S.code = randCode();
  const note = $('type-note'), span = note.querySelector('span');
  if (S.type === 'dynamic') {
    note.classList.remove('static-note');
    span.textContent = 'สแกนแล้วเด้งผ่านระบบ เปลี่ยนปลายทางได้ภายหลังโดยไม่ต้องพิมพ์ใหม่ และนับยอดสแกนได้ — ลิงก์ใช้งานได้หลังดาวน์โหลดครั้งแรก (บันทึกอัตโนมัติ)';
  } else {
    note.classList.add('static-note');
    span.textContent = 'ลิงก์ฝังในโค้ดโดยตรง สแกนแล้วเปิดทันที — แก้ปลายทางภายหลังไม่ได้';
  }
  $('expiry-field').style.display = S.type === 'dynamic' ? 'block' : 'none';
  savedId = null;
  render();
});

/* ---- text inputs ---- */
$('f-name').addEventListener('input', e => { S.name = e.target.value; savedId = null; $('pm-name').textContent = S.name || 'ยังไม่มีชื่อ'; });
$('f-link').addEventListener('input', e => { S.link = e.target.value; savedId = null; render(); });
$('f-cat').addEventListener('change', e => { S.category = e.target.value; savedId = null; });
$('f-expiry').addEventListener('input', e => { S.expiresAt = e.target.value; savedId = null; });

/* ---- colours ---- */
$('f-dotcolor').addEventListener('input', e => { S.dotColor = e.target.value; $('dotcolor-val').textContent = e.target.value; syncSwatch(); render(); });
$('f-bgcolor').addEventListener('input', e => { S.bgColor = e.target.value; $('bgcolor-val').textContent = e.target.value; render(); });
$('f-logosize').addEventListener('input', e => { S.logoSize = +e.target.value; $('logo-size-val').textContent = e.target.value + '%'; render(); });

/* ---- dot style / EC ---- */
document.querySelectorAll('#dotstyle button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#dotstyle button').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); S.dotType = b.dataset.v; clearPreset(); render();
});
document.querySelectorAll('#eclevel button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#eclevel button').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); S.ec = b.dataset.v; render();
});

/* ---- output size ---- */
function applySize(px) {
  S.size = px;
  $('size-val').textContent = px + ' × ' + px + ' px';
  $('f-qsize').value = px;
  document.querySelectorAll('#qsize button').forEach(x => x.classList.toggle('on', +x.dataset.v === px));
  render();
}
document.querySelectorAll('#qsize button').forEach(b => b.onclick = () => applySize(+b.dataset.v));
$('f-qsize').addEventListener('input', e => applySize(+e.target.value));

/* ---- swatches ---- */
const palette = ['#000000', '#f4a15c', '#30a46c', '#e5484d', '#e8830c', '#0891b2', '#7c3aed', '#db2777'];
const swBox = $('swatches');
palette.forEach(c => {
  const s = document.createElement('button'); s.type = 'button'; s.className = 'sw'; s.style.background = c; s.dataset.c = c;
  s.onclick = () => { S.dotColor = c; $('f-dotcolor').value = c; $('dotcolor-val').textContent = c; syncSwatch(); render(); };
  swBox.appendChild(s);
});
function syncSwatch() { document.querySelectorAll('.sw').forEach(s => s.classList.toggle('on', s.dataset.c.toLowerCase() === S.dotColor.toLowerCase())); }

/* ---- presets ---- */
const presets = [
  { n: 'มาตรฐาน', dot: '#000000', bg: '#ffffff', type: 'rounded' },
  { n: 'ส้มพาสเทล', dot: '#f4a15c', bg: '#ffffff', type: 'extra-rounded' },
  { n: 'เขียว', dot: '#30a46c', bg: '#ffffff', type: 'dots' },
  { n: 'กลางคืน', dot: '#e9ebf0', bg: '#16181e', type: 'classy' },
  { n: 'ส้มอบอุ่น', dot: '#e8830c', bg: '#fff8f0', type: 'rounded' }
];
const pBox = $('presets');
presets.forEach((p, i) => {
  const el = document.createElement('button'); el.type = 'button'; el.className = 'preset'; el.title = p.n;
  const r = p.type === 'dots' ? 2 : 1;
  el.innerHTML = `<svg viewBox="0 0 24 24"><rect width="24" height="24" rx="4" fill="${p.bg}" stroke="${p.dot}22"/>
    <g fill="${p.dot}"><rect x="4" y="4" width="4" height="4" rx="${r}"/><rect x="10" y="4" width="4" height="4" rx="${r}"/><rect x="16" y="4" width="4" height="4" rx="${r}"/>
    <rect x="4" y="10" width="4" height="4" rx="${r}"/><rect x="16" y="10" width="4" height="4" rx="${r}"/>
    <rect x="4" y="16" width="4" height="4" rx="${r}"/><rect x="10" y="16" width="4" height="4" rx="${r}"/><rect x="16" y="16" width="4" height="4" rx="${r}"/></g></svg>`;
  el.onclick = () => {
    S.dotColor = p.dot; S.bgColor = p.bg; S.dotType = p.type;
    $('f-dotcolor').value = p.dot; $('dotcolor-val').textContent = p.dot;
    $('f-bgcolor').value = p.bg; $('bgcolor-val').textContent = p.bg;
    document.querySelectorAll('#dotstyle button').forEach(x => x.classList.toggle('on', x.dataset.v === p.type));
    document.querySelectorAll('.preset').forEach(x => x.classList.remove('on')); el.classList.add('on');
    syncSwatch(); render();
  };
  pBox.appendChild(el);
  if (i === 0) el.classList.add('on');
});
function clearPreset() { document.querySelectorAll('.preset').forEach(x => x.classList.remove('on')); }

/* ---- style templates (save current style / apply a saved one) ---- */
const tplSelect = $('tpl-select');
if (tplSelect) { // template feature is hidden for guests — skip wiring it up entirely
async function loadTemplates(selectId) {
  try {
    const j = await (await fetch('api/templates.php')).json();
    if (!j.ok) return;
    tplSelect.innerHTML = '<option value="">— สไตล์ที่บันทึก —</option>';
    j.items.forEach(t => {
      const o = document.createElement('option');
      o.value = t.id;
      o.textContent = t.name + (t.has_logo ? ' (มีโลโก้)' : '');
      tplSelect.appendChild(o);
    });
    if (selectId) tplSelect.value = String(selectId);
  } catch (err) { /* silent — dropdown just stays empty */ }
}

function applyTemplateStyle(item) {
  const st = item.style || {};
  S.dotColor = st.dotColor || S.dotColor;
  S.bgColor  = st.bgColor  || S.bgColor;
  S.dotType  = st.dotType  || S.dotType;
  S.ec       = st.ec       || S.ec;
  S.logoSize = st.logoSize || S.logoSize;
  S.size     = st.size     || S.size;

  /* colours */
  $('f-dotcolor').value = S.dotColor; $('dotcolor-val').textContent = S.dotColor;
  $('f-bgcolor').value = S.bgColor; $('bgcolor-val').textContent = S.bgColor;
  syncSwatch();

  /* dot style / EC segmented controls */
  document.querySelectorAll('#dotstyle button').forEach(x => x.classList.toggle('on', x.dataset.v === S.dotType));
  document.querySelectorAll('#eclevel button').forEach(x => x.classList.toggle('on', x.dataset.v === S.ec));
  clearPreset();

  /* output size (segmented + slider + label) */
  applySize(S.size);

  /* logo, only if the template has one */
  if (item.logo_data) {
    S.logo = item.logo_data;
    $('logo-img').src = S.logo; $('logo-name').textContent = 'โลโก้จากเทมเพลต';
    logoDrop.style.display = 'none'; logoPrev.classList.add('show'); logoSizeField.style.display = 'block';
    $('f-logosize').value = S.logoSize; $('logo-size-val').textContent = S.logoSize + '%';
  }

  render();
}

$('tpl-save').onclick = async () => {
  const name = prompt('ตั้งชื่อเทมเพลตสไตล์นี้');
  if (name === null) return;
  const trimmed = name.trim();
  if (!trimmed) { toast('กรุณากรอกชื่อเทมเพลต', 'err'); return; }
  const payload = {
    csrf: window.APP.csrf, action: 'add', name: trimmed,
    style_json: { dotColor: S.dotColor, bgColor: S.bgColor, dotType: S.dotType, ec: S.ec, logoSize: S.logoSize, size: S.size },
    logo_data: S.logo
  };
  try {
    const j = await (await fetch('api/templates.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    })).json();
    if (!j.ok) { toast(j.error || 'บันทึกไม่สำเร็จ', 'err'); return; }
    await loadTemplates(j.item.id);
    toast('บันทึกเทมเพลตแล้ว');
  } catch (err) {
    toast('เชื่อมต่อไม่สำเร็จ', 'err');
  }
};

$('tpl-apply').onclick = async () => {
  const id = tplSelect.value;
  if (!id) { toast('กรุณาเลือกเทมเพลตก่อน', 'err'); return; }
  try {
    const j = await (await fetch('api/templates.php?id=' + encodeURIComponent(id))).json();
    if (!j.ok) { toast(j.error || 'ไม่พบเทมเพลต', 'err'); return; }
    applyTemplateStyle(j.item);
    toast('ใช้สไตล์แล้ว');
  } catch (err) {
    toast('เชื่อมต่อไม่สำเร็จ', 'err');
  }
};

$('tpl-delete').onclick = async () => {
  const id = tplSelect.value;
  if (!id) { toast('กรุณาเลือกเทมเพลตก่อน', 'err'); return; }
  const label = tplSelect.options[tplSelect.selectedIndex].textContent;
  if (!confirm('ลบเทมเพลต "' + label + '" ?')) return;
  try {
    const j = await (await fetch('api/templates.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: window.APP.csrf, action: 'delete', id: +id })
    })).json();
    if (!j.ok) { toast(j.error || 'ลบไม่สำเร็จ', 'err'); return; }
    await loadTemplates();
    toast('ลบเทมเพลตแล้ว');
  } catch (err) {
    toast('เชื่อมต่อไม่สำเร็จ', 'err');
  }
};
  loadTemplates();
} // end template feature

/* ---- logo upload ---- */
const logoDrop = $('logo-drop'), logoFile = $('logo-file'), logoPrev = $('logo-preview'), logoSizeField = $('logo-size-field');
logoDrop.onclick = () => logoFile.click();
logoFile.onchange = e => {
  const f = e.target.files[0]; if (!f) return;
  if (f.size > 1.5 * 1024 * 1024) { toast('ไฟล์โลโก้ควรเล็กกว่า 1.5MB', 'err'); return; }
  const r = new FileReader();
  r.onload = ev => {
    S.logo = ev.target.result;
    $('logo-img').src = S.logo; $('logo-name').textContent = f.name;
    logoDrop.style.display = 'none'; logoPrev.classList.add('show'); logoSizeField.style.display = 'block';
    render();
  };
  r.readAsDataURL(f);
};
$('logo-remove').onclick = () => {
  S.logo = null; logoFile.value = '';
  logoDrop.style.display = 'block'; logoPrev.classList.remove('show'); logoSizeField.style.display = 'none';
  render();
};

/* ---- edit mode: prefill from window.APP.edit and lock the QR type ---- */
function initEditMode(edit) {
  editMode = true;
  editId = edit.id;
  const st = edit.style || {};

  S.name     = edit.name || '';
  S.link     = edit.destination_url || '';
  S.category = edit.category || S.category;
  S.type     = edit.type || 'static';
  S.code     = edit.short_code || '';
  S.dotColor = st.dotColor || S.dotColor;
  S.bgColor  = st.bgColor || S.bgColor;
  S.dotType  = st.dotType || S.dotType;
  S.ec       = st.ec || S.ec;
  S.logoSize = st.logoSize || S.logoSize;
  S.size     = st.size || S.size;
  S.logo     = edit.logo_data || null;
  S.expiresAt = dbDateToLocalInput(edit.expires_at);

  /* name / link */
  $('f-name').value = S.name;
  $('f-link').value = S.link;

  /* category (add it as an option if it isn't one of the fixed presets) */
  const catSel = $('f-cat');
  if (S.category && ![...catSel.options].some(o => o.value === S.category)) {
    const o = document.createElement('option'); o.value = S.category; o.textContent = S.category;
    catSel.insertBefore(o, catSel.firstChild);
  }
  if (S.category) catSel.value = S.category;

  /* colours */
  $('f-dotcolor').value = S.dotColor; $('dotcolor-val').textContent = S.dotColor;
  $('f-bgcolor').value = S.bgColor; $('bgcolor-val').textContent = S.bgColor;
  syncSwatch();

  /* dot style / EC segmented controls */
  document.querySelectorAll('#dotstyle button').forEach(x => x.classList.toggle('on', x.dataset.v === S.dotType));
  document.querySelectorAll('#eclevel button').forEach(x => x.classList.toggle('on', x.dataset.v === S.ec));
  clearPreset();

  /* output size (segmented + slider + label) */
  applySize(S.size);

  /* QR type: reflect + lock (short_code integrity depends on type never changing) */
  document.querySelectorAll('#qrtype button').forEach(b => {
    b.classList.toggle('on', b.dataset.v === S.type);
    b.disabled = true;
    b.style.pointerEvents = 'none';
    b.style.opacity = '0.6';
  });
  const note = $('type-note'), span = note.querySelector('span');
  if (S.type === 'dynamic') {
    note.classList.remove('static-note');
    span.textContent = 'สแกนแล้วเด้งผ่านระบบ เปลี่ยนปลายทางได้ภายหลังโดยไม่ต้องพิมพ์ใหม่ และนับยอดสแกนได้';
  } else {
    note.classList.add('static-note');
    span.textContent = 'ลิงก์ฝังในโค้ดโดยตรง สแกนแล้วเปิดทันที — แก้ปลายทางภายหลังไม่ได้';
  }

  /* expiry (dynamic only) */
  $('f-expiry').value = S.expiresAt;
  $('expiry-field').style.display = S.type === 'dynamic' ? 'block' : 'none';

  /* logo preview */
  if (S.logo) {
    $('logo-img').src = S.logo; $('logo-name').textContent = 'โลโก้ปัจจุบัน';
    logoDrop.style.display = 'none'; logoPrev.classList.add('show'); logoSizeField.style.display = 'block';
    $('f-logosize').value = S.logoSize; $('logo-size-val').textContent = S.logoSize + '%';
  }

  render();
}

/* ---- persist edits to the SAME record (UPDATE, keeps type + short_code) ---- */
async function updateRecord() {
  const dest = (S.link || '').trim();
  const payload = {
    csrf: window.APP.csrf,
    id: editId,
    name: S.name, category: S.category, type: S.type,
    destination_url: dest,
    short_code: S.code,
    style_json: { dotColor: S.dotColor, bgColor: S.bgColor, dotType: S.dotType, ec: S.ec, logoSize: S.logoSize, size: S.size },
    logo_data: S.logo,
    expires_at: S.expiresAt || null
  };
  try {
    const j = await (await fetch('api/save.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    })).json();
    if (!j.ok) { toast(j.error || 'บันทึกไม่สำเร็จ', 'err'); return false; }
    toast('บันทึกการแก้ไขแล้ว');
    return true;
  } catch (err) {
    toast('เชื่อมต่อไม่สำเร็จ', 'err');
    return false;
  }
}

/* ---- auto-save (only the first download of a given QR persists it) ---- */
async function saveIfNeeded() {
  if (isGuest) return true;                        // privacy: guests never persist — create+download only, nothing is saved
  if (savedId !== null) return true;              // already saved this QR — don't duplicate
  const dest = (S.link || '').trim();
  if (!dest) return false;                          // nothing meaningful to save
  const payload = {
    csrf: window.APP.csrf,
    name: S.name, category: S.category, type: S.type,
    destination_url: dest,
    short_code: S.type === 'dynamic' ? S.code : null,
    style_json: { dotColor: S.dotColor, bgColor: S.bgColor, dotType: S.dotType, ec: S.ec, logoSize: S.logoSize, size: S.size },
    logo_data: S.logo,
    expires_at: S.expiresAt || null
  };
  try {
    const j = await (await fetch('api/save.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
    })).json();
    if (!j.ok) { toast(j.error || 'บันทึกไม่สำเร็จ', 'err'); return false; }
    savedId = j.id;
    if (S.type === 'dynamic' && j.short_code && j.short_code !== S.code) { S.code = j.short_code; render(); }
    if (!isGuest) toast('บันทึกลงประวัติแล้ว'); // guests can't view history — save silently
    return true;
  } catch (err) {
    toast('เชื่อมต่อไม่สำเร็จ', 'err');
    return false;
  }
}

/* ---- downloads: edit mode persists via UPDATE, new mode auto-saves via INSERT ---- */
const fname = () => (S.name || 'qr').replace(/\s+/g, '_') + '_' + S.size;
async function persist() { return editMode ? updateRecord() : saveIfNeeded(); }
$('dl-png').onclick = async () => { await persist(); new QRCodeStyling(opts(S.size)).download({ name: fname(), extension: 'png' }); };
$('dl-svg').onclick = async () => { await persist(); new QRCodeStyling(opts(S.size)).download({ name: fname(), extension: 'svg' }); };

/* ---- dynamic link copy (also persists first, so the link is live) ---- */
$('dyn-copy').onclick = async () => {
  await persist();
  navigator.clipboard.writeText($('dyn-link').value).then(() => toast('คัดลอกลิงก์แล้ว'));
};

/* ---- explicit "save edit" button (edit mode only — see qr.php) ---- */
if ($('edit-save-btn')) $('edit-save-btn').onclick = () => updateRecord();

/* ---- init ---- */
if (window.APP && window.APP.edit) initEditMode(window.APP.edit);
syncSwatch();
render();
})();
