/* สร้างรหัสผ่านปลอดภัย (Password generator) + Passphrase
 * ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์
 * ความสุ่มทั้งหมดมาจาก crypto.getRandomValues (ไม่ใช้ Math.random) พร้อมตัดอคติแบบ modulo
 * ด้วยเทคนิค rejection sampling — ไฟล์นี้เป็นโมดูลอิสระ ไม่แชร์โค้ดกับ random.js
 * (จงใจเขียนซ้ำแพตเทิร์นเดียวกันเพื่อความสม่ำเสมอ แต่ให้ไฟล์นี้ทำงานได้ในตัวเอง)
 */
(function () {
  'use strict';

  function byId(id) { return document.getElementById(id); }

  /* =========================================================
   * ตัวสุ่มที่ปลอดภัยและไม่มีอคติ (crypto.getRandomValues)
   * ========================================================= */

  /**
   * สุ่มจำนวนเต็มแบบ unbiased ในช่วง [min, max] (รวมทั้งสองปลาย)
   * ใช้ rejection sampling บน Uint32 เพื่อตัดอคติที่เกิดจาก modulo ตรงๆ
   * (การเอาไบต์สุ่ม % range ตรงๆ จะทำให้ค่าท้ายๆ ของช่วงมีโอกาสออกน้อยกว่าค่าต้นๆ
   *  เมื่อ range ไม่ใช่ตัวหารของ 2^32 พอดี)
   */
  function randomInt(min, max) {
    min = Math.floor(min);
    max = Math.floor(max);
    if (max < min) { const t = min; min = max; max = t; }
    const range = max - min + 1;
    if (range <= 1) return min;

    const MAX_UINT32 = 0x100000000; // 2^32
    const limit = Math.floor(MAX_UINT32 / range) * range;
    const buf = new Uint32Array(1);
    let x;
    do {
      crypto.getRandomValues(buf);
      x = buf[0];
    } while (x >= limit);
    return min + (x % range);
  }

  /** สุ่มเลือก 1 ตัวอักษรจากสตริง แบบ unbiased */
  function randomChar(str) {
    return str.charAt(randomInt(0, str.length - 1));
  }

  /** Fisher–Yates shuffle ในที่ (in-place) โดยใช้ randomInt ที่ไม่มีอคติ */
  function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = randomInt(0, i);
      const t = arr[i]; arr[i] = arr[j]; arr[j] = t;
    }
    return arr;
  }

  /* ---------- generic segmented-tab helper (same pattern as calc.js / random.js) ---------- */
  function wireSeg(tabsEl, onChange) {
    tabsEl.querySelectorAll('button').forEach(function (b) {
      b.onclick = function () {
        tabsEl.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on');
        onChange(b.dataset.v);
      };
    });
  }
  function segValue(tabsEl) {
    const on = tabsEl.querySelector('button.on');
    return on ? on.dataset.v : null;
  }
  function copyText(text, okMsg) {
    if (!text) { window.toast('ไม่มีรหัสผ่านให้คัดลอก', 'err'); return; }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        window.toast(okMsg || 'คัดลอกแล้ว', 'ok');
      }).catch(function () {
        window.toast('คัดลอกไม่สำเร็จ', 'err');
      });
    } else {
      window.toast('เบราว์เซอร์นี้ไม่รองรับการคัดลอกอัตโนมัติ', 'err');
    }
  }

  /* =========================================================
   * ชุดตัวอักษร + ตัวกำกวมที่ต้องเลี่ยง
   * ========================================================= */
  const CHARSET = {
    upper:  'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lower:  'abcdefghijklmnopqrstuvwxyz',
    number: '0123456789',
    symbol: '!@#$%^&*()_+-=[]{};:,.<>?/~',
  };
  // ตัวอักษร/ตัวเลขที่หน้าตาคล้ายกันจนสับสนง่าย (เลขศูนย์-ตัวโอ, เลขหนึ่ง-แอลตัวเล็ก-ไอตัวใหญ่)
  const AMBIGUOUS = '0O1lI';

  function stripAmbiguous(str) {
    let out = '';
    for (let i = 0; i < str.length; i++) {
      if (AMBIGUOUS.indexOf(str.charAt(i)) === -1) out += str.charAt(i);
    }
    return out;
  }

  /* =========================================================
   * ตัววัดความแข็งแรง (entropy-based)
   * ========================================================= */
  /**
   * ประมาณ entropy เป็นบิต: length * log2(ขนาดชุดตัวอักษรที่เป็นไปได้)
   * แล้วจัดกลุ่มเป็น 4 ระดับ — เกณฑ์บิตนี้เป็นการประมาณคร่าวๆ ที่ใช้กันทั่วไป
   * ไม่ใช่การวิเคราะห์แบบ crack-time ที่แม่นยำ แต่เพียงพอสำหรับให้ผู้ใช้เทียบความยาว/ความหลากหลาย
   */
  function estimateEntropyBits(length, poolSize) {
    if (poolSize <= 1 || length <= 0) return 0;
    return length * Math.log2(poolSize);
  }
  function bucketStrength(bits) {
    if (bits < 40) return { label: 'อ่อนแอ', cls: 'weak' };
    if (bits < 60) return { label: 'ปานกลาง', cls: 'medium' };
    if (bits < 80) return { label: 'แข็งแรง', cls: 'strong' };
    return { label: 'แข็งแรงมาก', cls: 'very-strong' };
  }
  function updateMeter(bits) {
    const fillEl = byId('pwd-meter-fill');
    const tagEl = byId('pwd-meter-tag');
    const bitsEl = byId('pwd-meter-bits');
    const s = bucketStrength(bits);
    const pct = Math.max(4, Math.min(100, (bits / 128) * 100));
    fillEl.className = 'pwd-meter-fill ' + s.cls;
    fillEl.style.width = pct + '%';
    tagEl.className = 'pml-tag ' + s.cls;
    tagEl.textContent = s.label;
    bitsEl.textContent = 'ประมาณ ' + Math.round(bits) + ' บิตของ entropy';
  }

  /* =========================================================
   * MODE SWITCHER
   * ========================================================= */
  const modeTabs = byId('mode-tabs');
  let mode = 'password';
  function showMode(m) {
    mode = m;
    byId('panel-password').classList.toggle('hidden', m !== 'password');
    byId('panel-passphrase').classList.toggle('hidden', m !== 'passphrase');
    generate();
  }
  wireSeg(modeTabs, showMode);

  /* =========================================================
   * (ก) รหัสผ่านสุ่ม
   * ========================================================= */
  const pwLengthEl = byId('pw-length');
  const pwLengthValEl = byId('pw-length-val');
  const pwUpperToggle = byId('pw-upper-toggle');
  const pwLowerToggle = byId('pw-lower-toggle');
  const pwNumberToggle = byId('pw-number-toggle');
  const pwSymbolToggle = byId('pw-symbol-toggle');
  const pwAmbiguousToggle = byId('pw-ambiguous-toggle');

  [pwUpperToggle, pwLowerToggle, pwNumberToggle, pwSymbolToggle, pwAmbiguousToggle].forEach(function (t) {
    t.onclick = function () { t.classList.toggle('on'); generate(); };
  });
  pwLengthEl.addEventListener('input', function () {
    pwLengthValEl.textContent = pwLengthEl.value;
    generate();
  });

  /** สร้าง pool ของแต่ละชุดที่ถูกเลือก (หลังตัดตัวกำกวมถ้าเปิดสวิตช์) */
  function buildPools() {
    const avoidAmbiguous = pwAmbiguousToggle.classList.contains('on');
    const pools = [];
    if (pwUpperToggle.classList.contains('on')) {
      pools.push(avoidAmbiguous ? stripAmbiguous(CHARSET.upper) : CHARSET.upper);
    }
    if (pwLowerToggle.classList.contains('on')) {
      pools.push(avoidAmbiguous ? stripAmbiguous(CHARSET.lower) : CHARSET.lower);
    }
    if (pwNumberToggle.classList.contains('on')) {
      pools.push(avoidAmbiguous ? stripAmbiguous(CHARSET.number) : CHARSET.number);
    }
    if (pwSymbolToggle.classList.contains('on')) {
      pools.push(CHARSET.symbol); // ไม่มีสัญลักษณ์ใดอยู่ในรายการตัวกำกวมที่กำหนด
    }
    return pools;
  }

  /**
   * สุ่มรหัสผ่านความยาว length จาก pools (array ของสตริงชุดตัวอักษรที่เลือกไว้)
   * การันตีว่ามีตัวอักษรจากทุก pool ที่เลือกอย่างน้อย pool ละ 1 ตัว (ไม่ใช่แค่สุ่มจาก pool รวม
   * ตรงๆ ซึ่งอาจสุ่มได้รหัสผ่านที่ไม่มีตัวเลขเลยทั้งที่เปิด toggle ตัวเลขไว้)
   */
  function generatePassword(length, pools) {
    if (pools.length === 0) return null;
    // 1) การันตีตัวแทนอย่างน้อย 1 ตัวจากแต่ละชุดที่เลือก
    const guaranteed = pools.map(function (p) { return randomChar(p); });
    // 2) เติมตำแหน่งที่เหลือด้วยการสุ่มจาก pool รวมของทุกชุดที่เลือก
    const combined = pools.join('');
    const chars = guaranteed.slice();
    for (let i = chars.length; i < length; i++) {
      chars.push(randomChar(combined));
    }
    // 3) สลับตำแหน่งทั้งหมดเพื่อไม่ให้ตัวอักษรที่การันตีไว้อยู่ต้นสตริงเสมอ
    shuffle(chars);
    return chars.join('');
  }

  function renderPassword() {
    const length = Math.max(4, Math.min(64, parseInt(pwLengthEl.value, 10) || 20));
    pwLengthEl.value = String(length);
    pwLengthValEl.textContent = String(length);
    const pools = buildPools();
    const outEl = byId('pwd-output');

    if (pools.length === 0) {
      outEl.textContent = 'เลือกอย่างน้อย 1 ชุดตัวอักษร';
      outEl.classList.add('empty');
      updateMeter(0);
      return null;
    }
    const pw = generatePassword(length, pools);
    outEl.classList.remove('empty');
    outEl.textContent = pw;
    const poolSize = Array.from(new Set(pools.join('').split(''))).length;
    updateMeter(estimateEntropyBits(length, poolSize));
    return pw;
  }

  /* =========================================================
   * (ข) Passphrase
   * ========================================================= */
  // รายการคำง่ายๆ ทั่วไป สะกดง่าย จำง่าย (สไตล์ correct-horse-battery-staple)
  const WORDLIST = [
    'apple', 'river', 'mountain', 'forest', 'garden', 'ocean', 'candle', 'window',
    'coffee', 'winter', 'summer', 'autumn', 'spring', 'purple', 'yellow', 'orange',
    'guitar', 'piano', 'violin', 'rocket', 'planet', 'comet', 'galaxy', 'desert',
    'jungle', 'canyon', 'island', 'bridge', 'castle', 'temple', 'harbor', 'meadow',
    'valley', 'shadow', 'sunset', 'sunrise', 'cloudy', 'stormy', 'breeze', 'thunder',
    'silver', 'golden', 'copper', 'bronze', 'marble', 'velvet', 'cotton', 'linen',
    'pencil', 'ladder', 'basket', 'bottle', 'blanket', 'pillow', 'mirror', 'lantern',
    'wander', 'gallop', 'whisper', 'sparkle', 'flicker', 'rustle', 'tumble', 'wobble',
    'brave', 'quiet', 'gentle', 'clever', 'happy', 'lucky', 'quick', 'sturdy',
    'tiger', 'falcon', 'dolphin', 'panda', 'rabbit', 'turtle', 'otter', 'sparrow',
    'maple', 'willow', 'cedar', 'birch', 'walnut', 'cherry', 'lemon', 'mango',
    'pepper', 'ginger', 'cinnamon', 'vanilla', 'almond', 'hazel', 'clover', 'lotus',
    'jasmine', 'orchid', 'tulip', 'daisy', 'violet', 'lily', 'poppy', 'iris',
    'compass', 'anchor', 'lagoon', 'voyage', 'journey', 'horizon', 'meteor', 'nebula',
    'crystal', 'diamond', 'emerald', 'sapphire', 'granite', 'pebble', 'boulder', 'gravel',
    'trumpet', 'cymbal', 'melody', 'rhythm', 'harmony', 'chorus', 'ballad', 'anthem',
  ];

  const ppWordsEl = byId('pp-words');
  const ppWordsValEl = byId('pp-words-val');
  const ppSepEl = byId('pp-sep');
  const ppCapToggle = byId('pp-cap-toggle');
  const ppNumberToggle = byId('pp-number-toggle');

  ppWordsEl.addEventListener('input', function () {
    ppWordsValEl.textContent = ppWordsEl.value;
    generate();
  });
  ppSepEl.addEventListener('change', generate);
  [ppCapToggle, ppNumberToggle].forEach(function (t) {
    t.onclick = function () { t.classList.toggle('on'); generate(); };
  });

  function generatePassphrase() {
    const wordCount = Math.max(3, Math.min(10, parseInt(ppWordsEl.value, 10) || 4));
    ppWordsValEl.textContent = String(wordCount);
    const sep = ppSepEl.value;
    const capitalize = ppCapToggle.classList.contains('on');
    const appendNumber = ppNumberToggle.classList.contains('on');

    const words = [];
    for (let i = 0; i < wordCount; i++) {
      let w = WORDLIST[randomInt(0, WORDLIST.length - 1)];
      if (capitalize) w = w.charAt(0).toUpperCase() + w.slice(1);
      words.push(w);
    }
    let out = words.join(sep);
    if (appendNumber) out += sep + String(randomInt(0, 99)).padStart(2, '0');

    // entropy: คำแต่ละคำสุ่มอิสระจากคลังคำ (log2(ขนาดคลัง) ต่อคำ) บวกตัวเลขท้ายถ้าเปิดใช้
    let bits = wordCount * Math.log2(WORDLIST.length);
    if (appendNumber) bits += Math.log2(100);

    const outEl = byId('pwd-output');
    outEl.classList.remove('empty');
    outEl.textContent = out;
    updateMeter(bits);
    return out;
  }

  /* =========================================================
   * generate() ทางเข้าเดียวตาม mode ปัจจุบัน + ปุ่ม/คัดลอก
   * ========================================================= */
  let lastGenerated = '';
  function generate() {
    const result = mode === 'passphrase' ? generatePassphrase() : renderPassword();
    lastGenerated = result || '';
  }

  byId('pwd-generate-btn').onclick = generate;
  byId('pwd-copy-btn').onclick = function () {
    copyText(byId('pwd-output').classList.contains('empty') ? '' : lastGenerated, 'คัดลอกรหัสผ่านแล้ว');
  };

  /* expose for manual console verification if needed */
  window.__pwdInternals = { randomInt: randomInt, shuffle: shuffle, generatePassword: generatePassword, buildPools: buildPools };

  generate();
})();
