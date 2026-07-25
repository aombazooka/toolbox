/* ================= Toolbox — qr-presets.php (WiFi / vCard / email / SMS / tel QR) ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);

const S = {
  tab: 'wifi',
  dotColor: '#000000', bgColor: '#ffffff', dotType: 'rounded', size: 512,
  wifi:  { ssid: '', pass: '', sec: 'WPA', hidden: false },
  tel:   { num: '' },
  sms:   { num: '', msg: '' },
  email: { to: '', subject: '', body: '' },
  vcard: { name: '', phone: '', email: '', company: '', address: '', website: '' }
};

const tabTitles = { wifi: 'WiFi', tel: 'โทร', sms: 'SMS', email: 'อีเมล', vcard: 'vCard' };

/* ---- WiFi QR spec escaping: backslash first, then ; , : ---- */
function escWifi(s) {
  return String(s || '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/:/g, '\\:');
}

function buildWifi() {
  const w = S.wifi;
  if (!w.ssid.trim()) return '';
  const t = w.sec === 'nopass' ? 'nopass' : w.sec;
  const pass = w.sec === 'nopass' ? '' : escWifi(w.pass);
  return 'WIFI:T:' + t + ';S:' + escWifi(w.ssid) + ';P:' + pass + ';H:' + (w.hidden ? 'true' : 'false') + ';;';
}

function buildTel() {
  const num = S.tel.num.trim();
  if (!num) return '';
  return 'tel:' + num;
}

function buildSms() {
  const num = S.sms.num.trim();
  if (!num) return '';
  return 'SMSTO:' + num + ':' + S.sms.msg;
}

function buildEmail() {
  const to = S.email.to.trim();
  if (!to) return '';
  return 'mailto:' + to + '?subject=' + encodeURIComponent(S.email.subject) + '&body=' + encodeURIComponent(S.email.body);
}

function buildVcard() {
  const v = S.vcard;
  if (!v.name.trim()) return '';
  const lines = ['BEGIN:VCARD', 'VERSION:3.0', 'N:' + v.name.trim() + ';;;;', 'FN:' + v.name.trim()];
  if (v.company.trim()) lines.push('ORG:' + v.company.trim());
  if (v.phone.trim()) lines.push('TEL;TYPE=CELL:' + v.phone.trim());
  if (v.email.trim()) lines.push('EMAIL:' + v.email.trim());
  if (v.address.trim()) lines.push('ADR:;;' + v.address.trim() + ';;;;');
  if (v.website.trim()) lines.push('URL:' + v.website.trim());
  lines.push('END:VCARD');
  return lines.join('\n');
}

const builders = { wifi: buildWifi, tel: buildTel, sms: buildSms, email: buildEmail, vcard: buildVcard };
function currentData() { return builders[S.tab](); }

/* ---- QR drawing (same opts pattern as create.js) ---- */
function opts(sz, data) {
  return {
    width: sz, height: sz, type: 'svg',
    data: data || ' ',
    dotsOptions: { color: S.dotColor, type: S.dotType },
    backgroundOptions: { color: S.bgColor },
    cornersSquareOptions: { type: S.dotType === 'square' ? 'square' : 'extra-rounded', color: S.dotColor },
    cornersDotOptions: { color: S.dotColor },
    qrOptions: { errorCorrectionLevel: 'M' }
  };
}
let qr = null;

function previewPx() { return Math.round(Math.min(300, Math.max(180, 140 + S.size / 6))); }

function render() {
  const data = currentData();
  const stage = $('qr-preview'), empty = $('qr-empty');
  $('pm-name').textContent = tabTitles[S.tab];
  $('raw-data').value = data;
  $('dl-png').disabled = !data;
  $('dl-svg').disabled = !data;

  if (!data) {
    stage.classList.add('hidden');
    empty.classList.remove('hidden');
    $('pm-link').textContent = '—';
    return;
  }
  stage.classList.remove('hidden');
  empty.classList.add('hidden');
  $('pm-link').textContent = data.length > 90 ? data.slice(0, 90) + '…' : data;

  const sz = previewPx();
  if (!qr) {
    qr = new QRCodeStyling(opts(sz, data));
    qr.append(stage);
  } else {
    qr.update(opts(sz, data));
  }
}

/* ---- tab switching ---- */
document.querySelectorAll('#preset-tabs button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#preset-tabs button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  S.tab = b.dataset.v;
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
  $('tab-' + S.tab).classList.remove('hidden');
  render();
});

/* ---- WiFi fields ---- */
$('wifi-ssid').addEventListener('input', e => { S.wifi.ssid = e.target.value; render(); });
$('wifi-pass').addEventListener('input', e => { S.wifi.pass = e.target.value; render(); });
$('wifi-hidden').addEventListener('change', e => { S.wifi.hidden = e.target.checked; render(); });
document.querySelectorAll('#wifi-sec button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#wifi-sec button').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); S.wifi.sec = b.dataset.v; render();
});

/* ---- Tel ---- */
$('tel-num').addEventListener('input', e => { S.tel.num = e.target.value; render(); });

/* ---- SMS ---- */
$('sms-num').addEventListener('input', e => { S.sms.num = e.target.value; render(); });
$('sms-msg').addEventListener('input', e => { S.sms.msg = e.target.value; render(); });

/* ---- Email ---- */
$('email-to').addEventListener('input', e => { S.email.to = e.target.value; render(); });
$('email-subject').addEventListener('input', e => { S.email.subject = e.target.value; render(); });
$('email-body').addEventListener('input', e => { S.email.body = e.target.value; render(); });

/* ---- vCard ---- */
$('vc-name').addEventListener('input', e => { S.vcard.name = e.target.value; render(); });
$('vc-phone').addEventListener('input', e => { S.vcard.phone = e.target.value; render(); });
$('vc-email').addEventListener('input', e => { S.vcard.email = e.target.value; render(); });
$('vc-company').addEventListener('input', e => { S.vcard.company = e.target.value; render(); });
$('vc-address').addEventListener('input', e => { S.vcard.address = e.target.value; render(); });
$('vc-website').addEventListener('input', e => { S.vcard.website = e.target.value; render(); });

/* ---- colours ---- */
$('f-dotcolor').addEventListener('input', e => { S.dotColor = e.target.value; $('dotcolor-val').textContent = e.target.value; syncSwatch(); render(); });
$('f-bgcolor').addEventListener('input', e => { S.bgColor = e.target.value; $('bgcolor-val').textContent = e.target.value; render(); });

/* ---- dot style ---- */
document.querySelectorAll('#dotstyle button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#dotstyle button').forEach(x => x.classList.remove('on'));
  b.classList.add('on'); S.dotType = b.dataset.v; render();
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

/* ---- downloads / copy ---- */
const fname = () => 'qr_' + S.tab + '_' + S.size;
$('dl-png').onclick = () => {
  const data = currentData(); if (!data) { toast('กรุณากรอกข้อมูลก่อน', 'err'); return; }
  new QRCodeStyling(opts(S.size, data)).download({ name: fname(), extension: 'png' });
  toast('ดาวน์โหลด PNG แล้ว');
};
$('dl-svg').onclick = () => {
  const data = currentData(); if (!data) { toast('กรุณากรอกข้อมูลก่อน', 'err'); return; }
  new QRCodeStyling(opts(S.size, data)).download({ name: fname(), extension: 'svg' });
  toast('ดาวน์โหลด SVG แล้ว');
};
$('raw-copy').onclick = () => {
  const data = currentData(); if (!data) { toast('ยังไม่มีข้อมูลให้คัดลอก', 'err'); return; }
  navigator.clipboard.writeText(data).then(() => toast('คัดลอกแล้ว'));
};

/* ---- init ---- */
syncSwatch();
render();
})();
