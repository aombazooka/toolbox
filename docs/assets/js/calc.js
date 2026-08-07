/* ชุดเครื่องคำนวณ: BMI · ส่วนลด · VAT · เปอร์เซ็นต์ · ทิป — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์ */
(function () {
  /* ---------- number helpers ---------- */
  function parseNumber(str) {
    if (str == null) return NaN;
    const cleaned = String(str).replace(/,/g, '').trim();
    if (cleaned === '') return NaN;
    const n = Number(cleaned);
    return isFinite(n) ? n : NaN;
  }
  function round2(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
  }
  function fmtMoney(n) {
    return round2(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
  }
  function fmtNum(n, digits) {
    digits = digits == null ? 2 : digits;
    return round2(n).toLocaleString('th-TH', { minimumFractionDigits: digits, maximumFractionDigits: digits });
  }
  function fmtPercent(n) {
    return fmtNum(n, 2) + '%';
  }
  function fmtOnBlur(input) {
    const n = parseNumber(input.value);
    if (isFinite(n)) {
      input.value = n.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
  }
  function byId(id) { return document.getElementById(id); }

  /* ---------- generic segmented-tab wiring ----------
   * tabsEl: the .seg container; onChange(value) called after class toggle.
   */
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

  /* =========================================================
   * MODE SWITCHER (main 5 tabs)
   * ========================================================= */
  const modeTabs = byId('mode-tabs');
  const MODES = ['bmi', 'discount', 'vat', 'percent', 'tip'];
  let mode = 'bmi';

  function showMode(m) {
    mode = m;
    MODES.forEach(function (mm) {
      const panel = byId('panel-' + mm);
      const result = byId('result-' + mm);
      if (panel) panel.classList.toggle('hidden', mm !== m);
      if (result) result.classList.toggle('hidden', mm !== m);
    });
    renderAll();
  }
  wireSeg(modeTabs, showMode);

  /* =========================================================
   * BMI — เกณฑ์เอเชีย/กระทรวงสาธารณสุขไทย (ต่างจากเกณฑ์สากล 25/30)
   * <18.5 ต่ำกว่าเกณฑ์ · 18.5-22.9 สมส่วน · 23-24.9 ท้วม · 25-29.9 อ้วน · >=30 อ้วนมาก
   * ========================================================= */
  const bmiWeight = byId('bmi-weight');
  const bmiHeight = byId('bmi-height');
  const bmiValueEl = byId('bmi-value');
  const bmiCategoryEl = byId('bmi-category');
  const bmiDetailEl = byId('bmi-detail');

  function bmiCategory(bmi) {
    if (bmi < 18.5) return { label: 'ต่ำกว่าเกณฑ์', detail: 'น้ำหนักน้อยกว่าเกณฑ์มาตรฐาน ควรเพิ่มน้ำหนักให้อยู่ในเกณฑ์สมส่วน' };
    if (bmi < 23) return { label: 'สมส่วน', detail: 'น้ำหนักอยู่ในเกณฑ์ปกติ สมส่วนดีแล้ว' };
    if (bmi < 25) return { label: 'ท้วม', detail: 'น้ำหนักเกินเล็กน้อย (ท้วม) ควรระวังเรื่องอาหารและออกกำลังกาย' };
    if (bmi < 30) return { label: 'อ้วน', detail: 'น้ำหนักเกินเกณฑ์ (อ้วน) ควรควบคุมอาหารและออกกำลังกายเพิ่มขึ้น' };
    return { label: 'อ้วนมาก', detail: 'อ้วนระดับอันตราย ควรปรึกษาแพทย์เพื่อวางแผนลดน้ำหนัก' };
  }

  function renderBmi() {
    const w = parseNumber(bmiWeight.value);
    const hCm = parseNumber(bmiHeight.value);
    if (!isFinite(w) || w <= 0 || !isFinite(hCm) || hCm <= 0) {
      bmiValueEl.textContent = '—';
      bmiCategoryEl.textContent = '—';
      bmiDetailEl.textContent = 'กรอกน้ำหนักและส่วนสูงด้านซ้ายเพื่อดูผล BMI';
      return;
    }
    const hM = hCm / 100;
    const bmi = w / (hM * hM);
    const cat = bmiCategory(bmi);
    bmiValueEl.textContent = fmtNum(bmi, 2);
    bmiCategoryEl.textContent = cat.label;
    bmiDetailEl.textContent = cat.detail + ' (BMI = น้ำหนัก(กก.) ÷ ส่วนสูง(ม.)²)';
  }

  /* =========================================================
   * ส่วนลด (Discount)
   * ========================================================= */
  const discPrice = byId('disc-price');
  const discPercent = byId('disc-percent');
  const discFinalEl = byId('disc-final');
  const discSavedEl = byId('disc-saved');
  const discDetailEl = byId('disc-detail');

  function renderDiscount() {
    const price = parseNumber(discPrice.value);
    const pct = parseNumber(discPercent.value);
    if (!isFinite(price) || price < 0 || !isFinite(pct) || pct < 0) {
      discFinalEl.textContent = '—';
      discSavedEl.textContent = '—';
      discDetailEl.textContent = 'กรอกราคาและส่วนลดด้านซ้าย';
      return;
    }
    const saved = price * (pct / 100);
    const final = price - saved;
    discFinalEl.textContent = fmtMoney(final);
    discSavedEl.textContent = fmtMoney(saved);
    discDetailEl.textContent = 'ลด ' + fmtNum(pct, pct % 1 === 0 ? 0 : 2) + '% จากราคา ' + fmtMoney(price);
  }

  /* =========================================================
   * VAT 7% — บวก / ถอด
   * ถอด VAT ต้องหารด้วย 1.07 ก่อน แล้วค่อยคูณ 0.07 (ไม่ใช่เอายอดรวมคูณ 0.07 ตรงๆ)
   * ========================================================= */
  const VAT_RATE = 0.07;
  const vatDirTabs = byId('vat-dir');
  const vatAmountInput = byId('vat-amount');
  const vatAmountLabel = byId('vat-amount-label');
  const vatHint = byId('vat-hint');
  const vatPreEl = byId('vat-pre');
  const vatAmountOutEl = byId('vat-amount-out');
  const vatTotalEl = byId('vat-total');
  const vatDetailEl = byId('vat-detail');

  const VAT_LABELS = {
    add: { label: 'ราคาก่อน VAT (บาท)', hint: 'ราคาที่กรอกยังไม่รวม VAT — ระบบจะบวก VAT 7% ให้' },
    extract: { label: 'ราคารวม VAT แล้ว (บาท)', hint: 'ราคาที่กรอกรวม VAT อยู่แล้ว — ระบบจะถอด VAT 7% ออกมาให้' },
  };

  function renderVat() {
    const dir = segValue(vatDirTabs) || 'add';
    const cfg = VAT_LABELS[dir];
    vatAmountLabel.textContent = cfg.label;
    vatHint.textContent = cfg.hint;

    const amount = parseNumber(vatAmountInput.value);
    if (!isFinite(amount) || amount < 0) {
      vatPreEl.textContent = '—';
      vatAmountOutEl.textContent = '—';
      vatTotalEl.textContent = '—';
      vatDetailEl.textContent = 'กรอกจำนวนเงินด้านซ้าย';
      return;
    }

    let pre, vatAmt, total;
    if (dir === 'extract') {
      // amount is VAT-inclusive: pre = amount / 1.07, vat = pre * 0.07 (== amount/1.07*0.07)
      pre = amount / (1 + VAT_RATE);
      vatAmt = pre * VAT_RATE;
      total = amount;
      vatDetailEl.textContent = 'ถอด VAT จากยอดรวม ' + fmtMoney(amount) + ' → ราคาก่อน VAT ÷ 1.07 แล้วคูณ 7% เพื่อหา VAT (ไม่ใช่เอายอดรวม × 7% ตรงๆ)';
    } else {
      pre = amount;
      vatAmt = amount * VAT_RATE;
      total = amount + vatAmt;
      vatDetailEl.textContent = 'บวก VAT 7% เข้ากับราคา ' + fmtMoney(amount);
    }
    vatPreEl.textContent = fmtMoney(pre);
    vatAmountOutEl.textContent = fmtMoney(vatAmt);
    vatTotalEl.textContent = fmtMoney(total);
  }
  wireSeg(vatDirTabs, renderVat);

  /* =========================================================
   * เปอร์เซ็นต์ — 3 รูปแบบคำถามมาตรฐาน
   * ========================================================= */
  const percentModeTabs = byId('percent-mode');
  const percentFieldsRatio = byId('percent-fields-ratio');
  const percentFieldsChange = byId('percent-fields-change');
  const percentFieldsBase = byId('percent-fields-base');

  const prX = byId('pr-x'), prY = byId('pr-y');
  const pcY = byId('pc-y'), pcX = byId('pc-x'), pcDirTabs = byId('pc-dir');
  const pbX = byId('pb-x'), pbN = byId('pb-n');

  const percentValueEl = byId('percent-value');
  const percentLabelEl = byId('percent-label');
  const percentSecondaryEl = byId('percent-secondary');
  const percentSecondaryLabelEl = byId('percent-secondary-label');
  const percentDetailEl = byId('percent-detail');

  function showPercentFields(sub) {
    percentFieldsRatio.classList.toggle('hidden', sub !== 'ratio');
    percentFieldsChange.classList.toggle('hidden', sub !== 'change');
    percentFieldsBase.classList.toggle('hidden', sub !== 'base');
  }
  wireSeg(percentModeTabs, function (v) { showPercentFields(v); renderPercent(); });
  wireSeg(pcDirTabs, renderPercent);
  showPercentFields(segValue(percentModeTabs) || 'ratio');

  function renderPercent() {
    const sub = segValue(percentModeTabs) || 'ratio';

    if (sub === 'ratio') {
      const x = parseNumber(prX.value), y = parseNumber(prY.value);
      percentLabelEl.textContent = 'X เป็นกี่% ของ Y';
      percentSecondaryLabelEl.textContent = 'ส่วนต่าง (X - Y)';
      if (!isFinite(x) || !isFinite(y) || y === 0) {
        percentValueEl.textContent = '—';
        percentSecondaryEl.textContent = '—';
        percentDetailEl.textContent = y === 0 && isFinite(y) ? 'Y ต้องไม่เป็น 0' : 'กรอก X และ Y ด้านซ้าย';
        return;
      }
      const pct = (x / y) * 100;
      percentValueEl.textContent = fmtPercent(pct);
      percentSecondaryEl.textContent = fmtNum(x - y, 2);
      percentDetailEl.textContent = fmtNum(x, 2) + ' เป็น ' + fmtPercent(pct) + ' ของ ' + fmtNum(y, 2);
      return;
    }

    if (sub === 'change') {
      const y = parseNumber(pcY.value), pct = parseNumber(pcX.value);
      const dir = segValue(pcDirTabs) || 'inc';
      percentLabelEl.textContent = dir === 'inc' ? 'ค่าหลังเพิ่มขึ้น' : 'ค่าหลังลดลง';
      percentSecondaryLabelEl.textContent = 'จำนวนที่เปลี่ยน';
      if (!isFinite(y) || !isFinite(pct) || pct < 0) {
        percentValueEl.textContent = '—';
        percentSecondaryEl.textContent = '—';
        percentDetailEl.textContent = 'กรอกค่าเริ่มต้นและเปอร์เซ็นต์ด้านซ้าย';
        return;
      }
      const delta = y * (pct / 100);
      const result = dir === 'inc' ? y + delta : y - delta;
      percentValueEl.textContent = fmtNum(result, 2);
      percentSecondaryEl.textContent = fmtNum(delta, 2);
      percentDetailEl.textContent = (dir === 'inc' ? 'เพิ่ม ' : 'ลด ') + fmtNum(y, 2) + ' ด้วย ' + fmtPercent(pct) + ' → ' + fmtNum(result, 2);
      return;
    }

    // sub === 'base': X คือ N% ของเลขอะไร → whole = X / (N/100)
    const x = parseNumber(pbX.value), n = parseNumber(pbN.value);
    percentLabelEl.textContent = 'เลขเต็มที่หาได้';
    percentSecondaryLabelEl.textContent = 'ส่วนต่าง (เลขเต็ม - X)';
    if (!isFinite(x) || !isFinite(n) || n === 0) {
      percentValueEl.textContent = '—';
      percentSecondaryEl.textContent = '—';
      percentDetailEl.textContent = n === 0 && isFinite(n) ? 'N ต้องไม่เป็น 0' : 'กรอก X และ N ด้านซ้าย';
      return;
    }
    const whole = x / (n / 100);
    percentValueEl.textContent = fmtNum(whole, 2);
    percentSecondaryEl.textContent = fmtNum(whole - x, 2);
    percentDetailEl.textContent = fmtNum(x, 2) + ' คือ ' + fmtPercent(n) + ' ของ ' + fmtNum(whole, 2);
  }

  /* =========================================================
   * ทิป (Tip)
   * ========================================================= */
  const tipBill = byId('tip-bill');
  const tipPercent = byId('tip-percent');
  const tipSplit = byId('tip-split');
  const tipAmountEl = byId('tip-amount');
  const tipTotalEl = byId('tip-total');
  const tipPerPersonEl = byId('tip-per-person');
  const tipDetailEl = byId('tip-detail');

  function renderTip() {
    const bill = parseNumber(tipBill.value);
    const pct = parseNumber(tipPercent.value);
    const splitRaw = parseNumber(tipSplit.value);
    const split = Math.round(splitRaw);
    if (!isFinite(bill) || bill < 0 || !isFinite(pct) || pct < 0 || !isFinite(split) || split <= 0) {
      tipAmountEl.textContent = '—';
      tipTotalEl.textContent = '—';
      tipPerPersonEl.textContent = '—';
      tipDetailEl.textContent = 'กรอกยอดบิล เปอร์เซ็นต์ทิป และจำนวนคนด้านซ้าย';
      return;
    }
    const tipAmt = bill * (pct / 100);
    const total = bill + tipAmt;
    const perPerson = total / split;
    tipAmountEl.textContent = fmtMoney(tipAmt);
    tipTotalEl.textContent = fmtMoney(total);
    tipPerPersonEl.textContent = fmtMoney(perPerson);
    tipDetailEl.textContent = 'บิล ' + fmtMoney(bill) + ' + ทิป ' + fmtPercent(pct) + ' หาร ' + split + ' คน';
  }

  /* ---------- render dispatch ---------- */
  function renderAll() {
    if (mode === 'bmi') renderBmi();
    else if (mode === 'discount') renderDiscount();
    else if (mode === 'vat') renderVat();
    else if (mode === 'percent') renderPercent();
    else if (mode === 'tip') renderTip();
  }

  /* ---------- wire live-update inputs ---------- */
  const ALL_TEXT_INPUTS = [
    bmiWeight, bmiHeight,
    discPrice, discPercent,
    vatAmountInput,
    prX, prY, pcY, pcX, pbX, pbN,
    tipBill, tipPercent, tipSplit,
  ];
  ALL_TEXT_INPUTS.forEach(function (el) {
    el.addEventListener('input', renderAll);
  });
  // format-on-blur for the money-ish fields (thousands separators)
  [bmiWeight, bmiHeight, discPrice, vatAmountInput, prX, prY, pcY, tipBill].forEach(function (el) {
    el.addEventListener('blur', function () { fmtOnBlur(el); });
  });

  renderAll();
})();
