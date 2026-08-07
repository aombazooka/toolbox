/* ================= Toolbox — promptpay.php (PromptPay / EMVCo QR payload) ================= */
(function () {
'use strict';
const $ = id => document.getElementById(id);

const S = {
  mode: 'phone',       // 'phone' | 'citizen'
  phone: '',
  citizen: '',
  amount: '',
  dotColor: '#000000', bgColor: '#ffffff', dotType: 'rounded', size: 512
};

/* ---------------------------------------------------------------------
 * CRC-16/CCITT-FALSE: poly 0x1021, init 0xFFFF, no reflect, no final xor.
 * Returns 4-hex-digit uppercase string.
 * ------------------------------------------------------------------- */
function crc16ccitt(str) {
  let crc = 0xFFFF;
  for (let i = 0; i < str.length; i++) {
    crc ^= (str.charCodeAt(i) & 0xFF) << 8;
    for (let b = 0; b < 8; b++) {
      crc = (crc & 0x8000) ? ((crc << 1) ^ 0x1021) : (crc << 1);
      crc &= 0xFFFF;
    }
  }
  return crc.toString(16).toUpperCase().padStart(4, '0');
}

/* ---- TLV helper: 2-digit tag + 2-digit zero-padded byte-length + value ---- */
function tlv(tag, value) {
  const len = String(value.length).padStart(2, '0');
  return tag + len + value;
}

/* ---- normalize a Thai mobile number to the 13-digit "0066xxxxxxxxx" proxy ---- */
function normalizePhone(raw) {
  let digits = String(raw || '').replace(/\D/g, '');
  if (digits.charAt(0) === '0') digits = digits.slice(1);
  const withCc = '66' + digits;
  return withCc.padStart(13, '0');
}

/* ---- Thai national ID check digit (mod-11) ---- */
function citizenCheckDigitValid(digits) {
  if (!/^\d{13}$/.test(digits)) return false;
  let sum = 0;
  for (let i = 0; i < 12; i++) sum += Number(digits[i]) * (13 - i);
  const check = (11 - (sum % 11)) % 10;
  return check === Number(digits[12]);
}

function phoneDigitsRaw() { return String(S.phone || '').replace(/\D/g, ''); }
function phoneValid() {
  const d = phoneDigitsRaw();
  return d.length === 9 || d.length === 10;
}
function citizenDigitsRaw() { return String(S.citizen || '').replace(/\D/g, ''); }
function citizenValid() { return /^\d{13}$/.test(citizenDigitsRaw()); }

function amountValue() {
  const v = parseFloat(S.amount);
  return (S.amount !== '' && !isNaN(v) && v > 0) ? v : null;
}

/* ---------------------------------------------------------------------
 * Build the PromptPay / EMVCo payload.
 * Field order (matches the de-facto standard promptpay-qr library):
 * 00 (format) · 01 (init method) · 29 (merchant acct info, nested AID +
 * proxy) · 58 (country TH) · 53 (currency THB) · 54 (amount, optional) ·
 * 63 (CRC). Field 58 is required — omitting it breaks scanning in several
 * Thai bank apps.
 * ------------------------------------------------------------------- */
function buildPayload() {
  const useCitizen = S.mode === 'citizen';
  if (useCitizen ? !citizenValid() : !phoneValid()) return '';

  const amount = amountValue();

  let out = '';
  out += tlv('00', '01');                                    // payload format indicator
  out += tlv('01', amount !== null ? '12' : '11');            // point of initiation: dynamic if amount set

  let merchantInfo = tlv('00', 'A000000677010111');           // AID
  if (useCitizen) {
    merchantInfo += tlv('02', citizenDigitsRaw());             // national ID proxy
  } else {
    merchantInfo += tlv('01', normalizePhone(S.phone));        // mobile proxy
  }
  out += tlv('29', merchantInfo);

  out += tlv('58', 'TH');                                     // country code
  out += tlv('53', '764');                                    // currency: THB
  if (amount !== null) out += tlv('54', amount.toFixed(2));   // amount, 2 decimals

  out += '6304';                                               // tag 63 header, CRC computed over this
  out += crc16ccitt(out);
  return out;
}

/* ---- QR drawing (same opts pattern as qr-presets.js / create.js) ---- */
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

function updateFieldHints() {
  const phoneHint = $('phone-hint');
  if (S.phone === '') {
    phoneHint.textContent = 'กรอกเบอร์มือถือ 9–10 หลัก (มีหรือไม่มีเลข 0 นำหน้าก็ได้)';
    phoneHint.style.color = '';
  } else if (!phoneValid()) {
    phoneHint.textContent = 'เบอร์โทรศัพท์ไม่ถูกต้อง — ต้องมี 9–10 หลัก';
    phoneHint.style.color = 'var(--danger)';
  } else {
    phoneHint.textContent = 'พร้อมเพย์: ' + normalizePhone(S.phone);
    phoneHint.style.color = 'var(--ok)';
  }

  const citizenHint = $('citizen-hint');
  const cd = citizenDigitsRaw();
  if (cd === '') {
    citizenHint.textContent = 'กรอกเลขบัตรประชาชน 13 หลัก';
    citizenHint.style.color = '';
  } else if (!citizenValid()) {
    citizenHint.textContent = 'ต้องเป็นตัวเลข 13 หลัก';
    citizenHint.style.color = 'var(--danger)';
  } else if (!citizenCheckDigitValid(cd)) {
    citizenHint.textContent = 'รูปแบบครบ 13 หลัก แต่เลขหลักตรวจสอบ (check digit) ไม่ตรง — ยังสร้าง QR ได้ แต่โปรดตรวจสอบเลขอีกครั้ง';
    citizenHint.style.color = 'var(--danger)';
  } else {
    citizenHint.textContent = 'เลขบัตรประชาชนถูกต้องตามรูปแบบ';
    citizenHint.style.color = 'var(--ok)';
  }
}

function render() {
  updateFieldHints();
  const data = buildPayload();
  const stage = $('qr-preview'), empty = $('qr-empty');
  const amount = amountValue();

  $('pm-name').textContent = S.mode === 'citizen' ? 'พร้อมเพย์ (เลขบัตรประชาชน)' : 'พร้อมเพย์ (เบอร์โทรศัพท์)';
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
  $('pm-link').textContent = (amount !== null ? ('จำนวนเงิน ' + amount.toFixed(2) + ' บาท · ') : 'ไม่ระบุจำนวนเงิน · ') +
    (S.mode === 'citizen' ? 'เลขบัตรประชาชน ' + citizenDigitsRaw() : 'เบอร์ ' + normalizePhone(S.phone));

  const sz = previewPx();
  if (!qr) {
    qr = new QRCodeStyling(opts(sz, data));
    qr.append(stage);
  } else {
    qr.update(opts(sz, data));
  }
}

/* ---- mode toggle ---- */
document.querySelectorAll('#pp-mode button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#pp-mode button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  S.mode = b.dataset.v;
  $('field-phone').classList.toggle('hidden', S.mode !== 'phone');
  $('field-citizen').classList.toggle('hidden', S.mode !== 'citizen');
  render();
});

/* ---- inputs ---- */
$('pp-phone').addEventListener('input', e => { S.phone = e.target.value; render(); });
$('pp-citizen').addEventListener('input', e => { S.citizen = e.target.value; render(); });
$('pp-amount').addEventListener('input', e => { S.amount = e.target.value; render(); });

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
const fname = () => 'promptpay_' + S.mode + '_' + S.size;
$('dl-png').onclick = () => {
  const data = buildPayload(); if (!data) { toast('กรุณากรอกเบอร์โทรศัพท์หรือเลขบัตรประชาชนก่อน', 'err'); return; }
  new QRCodeStyling(opts(S.size, data)).download({ name: fname(), extension: 'png' });
  toast('ดาวน์โหลด PNG แล้ว');
};
$('dl-svg').onclick = () => {
  const data = buildPayload(); if (!data) { toast('กรุณากรอกเบอร์โทรศัพท์หรือเลขบัตรประชาชนก่อน', 'err'); return; }
  new QRCodeStyling(opts(S.size, data)).download({ name: fname(), extension: 'svg' });
  toast('ดาวน์โหลด SVG แล้ว');
};
$('raw-copy').onclick = () => {
  const data = buildPayload(); if (!data) { toast('ยังไม่มีข้อมูลให้คัดลอก', 'err'); return; }
  navigator.clipboard.writeText(data).then(() => toast('คัดลอก Payload แล้ว'));
};

/* ---- init ---- */
syncSwatch();
render();
})();
