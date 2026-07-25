/* แปลงหน่วย · อุณหภูมิ · สกุลเงิน — ทำงานฝั่งเบราว์เซอร์ล้วน ยกเว้นสกุลเงินที่เรียก api/rates.php (แคชฝั่งเซิร์ฟเวอร์) */
(function () {
  function byId(id) { return document.getElementById(id); }

  /* ---------- number helpers ---------- */
  function parseNumber(str) {
    if (str == null) return NaN;
    const cleaned = String(str).replace(/,/g, '').trim();
    if (cleaned === '') return NaN;
    const n = Number(cleaned);
    return isFinite(n) ? n : NaN;
  }
  function fmtResult(n) {
    if (!isFinite(n)) return '';
    if (n === 0) return '0';
    const abs = Math.abs(n);
    if (abs >= 1e15 || abs < 1e-9) return n.toExponential(6);
    const rounded = Number(n.toPrecision(10));
    return rounded.toLocaleString('th-TH', { maximumFractionDigits: 8, useGrouping: true });
  }
  function fmtOnBlur(input) {
    const n = parseNumber(input.value);
    if (isFinite(n)) input.value = fmtResult(n);
  }

  /* ---------- temperature: real formulas, not a factor multiply ---------- */
  function toCelsius(v, unit) {
    if (unit === 'c') return v;
    if (unit === 'f') return (v - 32) * 5 / 9;
    if (unit === 'k') return v - 273.15;
    return NaN;
  }
  function fromCelsius(c, unit) {
    if (unit === 'c') return c;
    if (unit === 'f') return c * 9 / 5 + 32;
    if (unit === 'k') return c + 273.15;
    return NaN;
  }

  /* ---------- Thai-friendly names for common currency codes (frankfurter gives codes only) ---------- */
  const CURRENCY_NAMES = {
    THB: 'บาทไทย', USD: 'ดอลลาร์สหรัฐ', EUR: 'ยูโร', GBP: 'ปอนด์สเตอร์ลิง', JPY: 'เยนญี่ปุ่น',
    CNY: 'หยวนจีน', SGD: 'ดอลลาร์สิงคโปร์', AUD: 'ดอลลาร์ออสเตรเลีย', HKD: 'ดอลลาร์ฮ่องกง',
    KRW: 'วอนเกาหลีใต้', INR: 'รูปีอินเดีย', MYR: 'ริงกิตมาเลเซีย', IDR: 'รูเปียห์อินโดนีเซีย',
    PHP: 'เปโซฟิลิปปินส์', VND: 'ดองเวียดนาม', CAD: 'ดอลลาร์แคนาดา', NZD: 'ดอลลาร์นิวซีแลนด์',
    CHF: 'ฟรังก์สวิส', SEK: 'โครนาสวีเดน', NOK: 'โครนนอร์เวย์', DKK: 'โครนเดนมาร์ก',
    ILS: 'เชเกลอิสราเอล', MXN: 'เปโซเม็กซิโก', ZAR: 'แรนด์แอฟริกาใต้', BRL: 'เรียลบราซิล',
    TRY: 'ลีราตุรกี', RUB: 'รูเบิลรัสเซีย', PLN: 'ซลอตีโปแลนด์', CZK: 'โครูนาเช็ก', HUF: 'ฟอรินต์ฮังการี',
    BGN: 'เลฟบัลแกเรีย', RON: 'ลิวโรมาเนีย', ISK: 'โครนาไอซ์แลนด์',
  };
  function currencyLabel(code) {
    const name = CURRENCY_NAMES[code];
    return name ? name + ' (' + code + ')' : code;
  }

  /* ---------- category definitions ----------
   * type "factor": convert(v, from, to) = v * units[from].factor / units[to].factor
   *   (factor = how many "base units" 1 of this unit equals — same trick used for currency,
   *   with THB as the implicit base since api/rates.php always fetches from=THB)
   * type "temperature": non-linear, uses toCelsius/fromCelsius above
   */
  const CATEGORIES = {
    length: {
      title: 'ความยาว', type: 'factor',
      note: 'แปลงหน่วยความยาว: มิลลิเมตร ซม. เมตร กม. นิ้ว ฟุต หลา ไมล์ — คำนวณสดในเบราว์เซอร์',
      units: {
        mm:  { label: 'มิลลิเมตร (mm)', factor: 0.001 },
        cm:  { label: 'เซนติเมตร (cm)', factor: 0.01 },
        m:   { label: 'เมตร (m)', factor: 1 },
        km:  { label: 'กิโลเมตร (km)', factor: 1000 },
        inch:{ label: 'นิ้ว (inch)', factor: 0.0254 },
        foot:{ label: 'ฟุต (foot)', factor: 0.3048 },
        yard:{ label: 'หลา (yard)', factor: 0.9144 },
        mile:{ label: 'ไมล์ (mile)', factor: 1609.344 },
      },
      def: { from: 'm', to: 'km' },
    },
    weight: {
      title: 'น้ำหนัก', type: 'factor',
      note: 'แปลงหน่วยน้ำหนัก: มิลลิกรัม กรัม กก. ตัน ออนซ์ ปอนด์',
      units: {
        mg:    { label: 'มิลลิกรัม (mg)', factor: 0.001 },
        g:     { label: 'กรัม (g)', factor: 1 },
        kg:    { label: 'กิโลกรัม (kg)', factor: 1000 },
        tonne: { label: 'ตัน (tonne)', factor: 1000000 },
        oz:    { label: 'ออนซ์ (oz)', factor: 28.349523125 },
        lb:    { label: 'ปอนด์ (lb)', factor: 453.59237 },
      },
      def: { from: 'kg', to: 'lb' },
    },
    area: {
      title: 'พื้นที่', type: 'factor',
      note: 'แปลงหน่วยพื้นที่ ตร.ม. ตร.กม. ไร่ งาน ตารางวา เอเคอร์ — ใช้อัตราไทยแท้: 1 ไร่ = 4 งาน = 400 ตารางวา = 1,600 ตร.ม.',
      units: {
        m2:   { label: 'ตารางเมตร (m²)', factor: 1 },
        km2:  { label: 'ตารางกิโลเมตร (km²)', factor: 1000000 },
        wah2: { label: 'ตารางวา', factor: 4 },
        ngan: { label: 'งาน', factor: 400 },
        rai:  { label: 'ไร่', factor: 1600 },
        acre: { label: 'เอเคอร์ (acre)', factor: 4046.8564224 },
      },
      def: { from: 'rai', to: 'wah2' },
    },
    volume: {
      title: 'ปริมาตร', type: 'factor',
      note: 'แปลงหน่วยปริมาตร มล. ลิตร ลบ.ม. แกลลอน ควอร์ต ไพน์ต — ใช้หน่วยแบบสหรัฐอเมริกา (US) ไม่ใช่แบบอังกฤษ (UK/Imperial)',
      units: {
        ml:        { label: 'มิลลิลิตร (mL)', factor: 0.001 },
        l:         { label: 'ลิตร (L)', factor: 1 },
        m3:        { label: 'ลูกบาศก์เมตร (m³)', factor: 1000 },
        cup_us:    { label: 'ถ้วยตวง (US cup)', factor: 0.2365882365 },
        pint_us:   { label: 'ไพน์ต (US pint)', factor: 0.473176473 },
        quart_us:  { label: 'ควอร์ต (US quart)', factor: 0.946352946 },
        gallon_us: { label: 'แกลลอน (US gallon)', factor: 3.785411784 },
      },
      def: { from: 'gallon_us', to: 'l' },
    },
    speed: {
      title: 'ความเร็ว', type: 'factor',
      note: 'แปลงหน่วยความเร็ว กม./ชม. ม./วินาที ไมล์/ชม. นอต',
      units: {
        kmh:  { label: 'กม./ชม. (km/h)', factor: 1000 / 3600 },
        ms:   { label: 'ม./วินาที (m/s)', factor: 1 },
        mph:  { label: 'ไมล์/ชม. (mph)', factor: 1609.344 / 3600 },
        knot: { label: 'นอต (knot)', factor: 1852 / 3600 },
      },
      def: { from: 'kmh', to: 'ms' },
    },
    digital: {
      title: 'ข้อมูล', type: 'factor',
      note: 'แปลงหน่วยข้อมูลดิจิทัล — ใช้ฐานทวิภาค (Binary): 1 KB = 1,024 ไบต์, 1 MB = 1,024 KB ... (ไม่ใช่ฐาน 1,000 แบบผู้ผลิตฮาร์ดดิสก์บางราย) ใช้อัตราเดียวกันตลอดทั้งบันได',
      units: {
        bit:  { label: 'บิต (bit)', factor: 1 / 8 },
        byte: { label: 'ไบต์ (byte)', factor: 1 },
        kb:   { label: 'กิโลไบต์ (KB, 1024B)', factor: 1024 },
        mb:   { label: 'เมกะไบต์ (MB, 1024KB)', factor: Math.pow(1024, 2) },
        gb:   { label: 'กิกะไบต์ (GB, 1024MB)', factor: Math.pow(1024, 3) },
        tb:   { label: 'เทราไบต์ (TB, 1024GB)', factor: Math.pow(1024, 4) },
      },
      def: { from: 'mb', to: 'kb' },
    },
    time: {
      title: 'เวลา', type: 'factor',
      note: 'แปลงหน่วยเวลา วินาที นาที ชั่วโมง วัน สัปดาห์',
      units: {
        sec:  { label: 'วินาที', factor: 1 },
        min:  { label: 'นาที', factor: 60 },
        hour: { label: 'ชั่วโมง', factor: 3600 },
        day:  { label: 'วัน', factor: 86400 },
        week: { label: 'สัปดาห์', factor: 604800 },
      },
      def: { from: 'day', to: 'hour' },
    },
    temperature: {
      title: 'อุณหภูมิ', type: 'temperature',
      note: 'แปลงหน่วยอุณหภูมิด้วยสูตรแปลงที่ถูกต้อง (ไม่ใช่การคูณด้วยตัวเลขคงที่แบบหน่วยอื่น) — °C ↔ °F ↔ K',
      units: {
        c: { label: 'องศาเซลเซียส (°C)' },
        f: { label: 'องศาฟาเรนไฮต์ (°F)' },
        k: { label: 'เคลวิน (K)' },
      },
      def: { from: 'c', to: 'f' },
    },
    currency: {
      title: 'สกุลเงิน', type: 'currency',
      note: '',
      units: {}, loaded: false,
      def: { from: 'THB', to: 'USD' },
    },
  };
  const POPULAR_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'CNY', 'SGD', 'AUD', 'HKD', 'KRW', 'MYR'];

  /* ---------- generic conversion engine ---------- */
  function convertValue(value, fromKey, toKey, cat) {
    if (cat.type === 'temperature') return fromCelsius(toCelsius(value, fromKey), toKey);
    const u = cat.units;
    if (!u[fromKey] || !u[toKey]) return NaN;
    return (value * u[fromKey].factor) / u[toKey].factor;
  }

  /* ---------- DOM refs ---------- */
  const catTabs        = byId('cat-tabs');
  const catNoteEl       = byId('cat-note');
  const fromValueEl     = byId('from-value');
  const toValueEl       = byId('to-value');
  const fromUnitEl      = byId('from-unit');
  const toUnitEl        = byId('to-unit');
  const swapBtn         = byId('swap-btn');
  const currencyStatusEl= byId('currency-status');
  const gridEl          = byId('all-units-grid');
  const gridEmptyEl     = byId('all-units-empty');

  let currentCat = 'length';
  let lastEdited = 'from'; // 'from' | 'to' — which field the user is actively driving

  function buildUnitSelect(selectEl, unitsObj, selectedKey) {
    selectEl.innerHTML = '';
    Object.keys(unitsObj).forEach(function (key) {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = unitsObj[key].label;
      if (key === selectedKey) opt.selected = true;
      selectEl.appendChild(opt);
    });
  }

  function renderAllUnitsGrid() {
    const cat = CATEGORIES[currentCat];
    const v = parseNumber(fromValueEl.value);
    gridEl.innerHTML = '';
    if (!isFinite(v) || !Object.keys(cat.units).length) {
      gridEmptyEl.classList.remove('hidden');
      return;
    }
    gridEmptyEl.classList.add('hidden');
    let keys = Object.keys(cat.units);
    if (cat.type === 'currency') {
      keys = POPULAR_CURRENCIES.filter(function (k) { return cat.units[k]; });
      if (keys.indexOf('THB') === -1) keys.unshift('THB');
    }
    keys.forEach(function (key) {
      const val = convertValue(v, fromUnitEl.value, key, cat);
      const div = document.createElement('div');
      div.className = 'stat';
      const sv = document.createElement('div'); sv.className = 'sv'; sv.textContent = fmtResult(val);
      const sl = document.createElement('div'); sl.className = 'sl'; sl.textContent = cat.units[key].label;
      div.appendChild(sv); div.appendChild(sl);
      gridEl.appendChild(div);
    });
  }

  function renderCurrent() {
    const cat = CATEGORIES[currentCat];
    if (lastEdited === 'to') {
      const v = parseNumber(toValueEl.value);
      fromValueEl.value = isFinite(v) ? fmtResult(convertValue(v, toUnitEl.value, fromUnitEl.value, cat)) : '';
    } else {
      const v = parseNumber(fromValueEl.value);
      toValueEl.value = isFinite(v) ? fmtResult(convertValue(v, fromUnitEl.value, toUnitEl.value, cat)) : '';
    }
    renderAllUnitsGrid();
  }

  function switchCategory(key) {
    currentCat = key;
    const cat = CATEGORIES[key];
    currencyStatusEl.classList.toggle('hidden', key !== 'currency');

    if (key === 'currency') {
      catNoteEl.textContent = 'ดึงอัตราแลกเปลี่ยนล่าสุดจาก frankfurter.app ผ่านตัวกลางฝั่งเซิร์ฟเวอร์ของเรา (แคชไว้ 1 วัน) — ใช้เพื่อการอ้างอิงเท่านั้น ไม่ใช่อัตราซื้อขายจริงจากธนาคาร/ร้านแลกเงิน';
      if (!cat.loaded) { loadCurrency(); return; }
      buildUnitSelect(fromUnitEl, cat.units, cat.def.from);
      buildUnitSelect(toUnitEl, cat.units, cat.def.to);
      fromValueEl.value = '100';
      lastEdited = 'from';
      renderCurrent();
      return;
    }

    catNoteEl.textContent = cat.note;
    buildUnitSelect(fromUnitEl, cat.units, cat.def.from);
    buildUnitSelect(toUnitEl, cat.units, cat.def.to);
    fromValueEl.value = '1';
    lastEdited = 'from';
    renderCurrent();
  }

  async function loadCurrency() {
    const cat = CATEGORIES.currency;
    currencyStatusEl.classList.remove('hidden');
    currencyStatusEl.textContent = 'กำลังโหลดอัตราแลกเปลี่ยน...';
    fromUnitEl.innerHTML = '';
    toUnitEl.innerHTML = '';
    toValueEl.value = '';
    gridEl.innerHTML = '';
    gridEmptyEl.classList.add('hidden');
    try {
      const res = await fetch('api/rates.php');
      const data = await res.json();
      if (!data || !data.ok) {
        currencyStatusEl.textContent = (data && data.error) ? data.error : 'ดึงอัตราแลกเปลี่ยนไม่สำเร็จ กรุณาลองใหม่ภายหลัง';
        if (window.toast) window.toast('ดึงอัตราแลกเปลี่ยนไม่สำเร็จ', 'err');
        return;
      }
      const units = { THB: { label: currencyLabel('THB'), factor: 1 } };
      Object.keys(data.rates || {}).forEach(function (code) {
        const rate = Number(data.rates[code]); // rate = amount of `code` per 1 THB
        if (isFinite(rate) && rate > 0) units[code] = { label: currencyLabel(code), factor: 1 / rate };
      });
      cat.units = units;
      cat.loaded = true;
      cat.def.to = units.USD ? 'USD' : (Object.keys(units).filter(function (k) { return k !== 'THB'; })[0] || 'THB');

      buildUnitSelect(fromUnitEl, units, 'THB');
      buildUnitSelect(toUnitEl, units, cat.def.to);
      fromValueEl.value = '100';
      lastEdited = 'from';
      renderCurrent();

      let msg = 'อัตราอ้างอิงวันที่ ' + data.date + (data.cached ? ' (จากแคช อัปเดตเมื่อ ' + data.fetched_at + ')' : ' (ดึงสดจาก frankfurter.app เมื่อ ' + data.fetched_at + ')');
      if (data.stale) msg += ' — เครือข่ายขัดข้องขณะนี้ ใช้ข้อมูลแคชเก่าไปก่อน';
      currencyStatusEl.textContent = msg;
    } catch (e) {
      currencyStatusEl.textContent = 'เชื่อมต่อ API ไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่';
      if (window.toast) window.toast('เชื่อมต่อ API ไม่สำเร็จ', 'err');
    }
  }

  /* ---------- wire up ---------- */
  catTabs.querySelectorAll('button').forEach(function (b) {
    b.onclick = function () {
      catTabs.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
      switchCategory(b.dataset.v);
    };
  });

  fromValueEl.addEventListener('input', function () { lastEdited = 'from'; renderCurrent(); });
  toValueEl.addEventListener('input', function () { lastEdited = 'to'; renderCurrent(); });
  fromUnitEl.addEventListener('change', function () { lastEdited = 'from'; renderCurrent(); });
  toUnitEl.addEventListener('change', function () { lastEdited = 'from'; renderCurrent(); });
  [fromValueEl, toValueEl].forEach(function (el) {
    el.addEventListener('blur', function () { fmtOnBlur(el); });
  });

  swapBtn.addEventListener('click', function () {
    const fu = fromUnitEl.value, tu = toUnitEl.value;
    fromUnitEl.value = tu; toUnitEl.value = fu;
    fromValueEl.value = toValueEl.value;
    lastEdited = 'from';
    renderCurrent();
  });

  switchCategory(currentCat);
})();
