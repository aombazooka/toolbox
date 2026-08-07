/* คำนวณผ่อน/สินเชื่อ — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์ */
(function () {
  const elPrincipal = document.getElementById('f-principal');
  const elRate       = document.getElementById('f-rate');
  const elMonths     = document.getElementById('f-months');
  const methodTabs   = document.getElementById('method-tabs');
  const methodHint   = document.getElementById('method-hint');

  const stPayment  = document.getElementById('st-payment');
  const stInterest = document.getElementById('st-interest');
  const stTotal    = document.getElementById('st-total');

  const amortBody  = document.getElementById('amort-body');
  const amortEmpty = document.getElementById('amort-empty');
  const amortTable = document.getElementById('amort-table');

  const MAX_MONTHS = 600; // 50 ปี — กันตารางยาวเกินจนหน่วงเบราว์เซอร์

  const HINTS = {
    reducing: 'ดอกเบี้ยคิดจากยอดคงเหลือแต่ละงวด (แบบที่สินเชื่อบ้าน/รถส่วนใหญ่ใช้) — ค่างวดคงที่ทุกเดือน',
    flat: 'ดอกเบี้ยคิดจากเงินต้นเริ่มต้นตลอดสัญญา (มักใช้กับสินเชื่อเช่าซื้อ) — ค่างวดคงที่ แต่ดอกเบี้ยรวมมักสูงกว่าลดต้นลดดอก',
  };

  let method = 'reducing';

  /* ---------- number helpers ---------- */
  function parseNumber(str) {
    if (str == null) return NaN;
    const cleaned = String(str).replace(/,/g, '').trim();
    if (cleaned === '') return NaN;
    const n = Number(cleaned);
    return isFinite(n) ? n : NaN;
  }
  function round2(n) {
    // avoid classic floating point round issues (e.g. 1.005 -> 1) by nudging with a tiny epsilon
    return Math.round((n + Number.EPSILON) * 100) / 100;
  }
  function fmtMoney(n) {
    return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
  }
  function fmtPlain(n) {
    return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function fmtOnBlur(input) {
    const n = parseNumber(input.value);
    if (isFinite(n)) {
      input.value = n.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
  }

  /* ---------- core calculation ----------
   * Builds the amortization table using a "cumulative rounding" technique:
   * we compute exact (unrounded) cumulative principal/interest paid through
   * each installment, round *those cumulative totals* to the cent, then take
   * successive differences for the per-row display values. This guarantees:
   *   - the principal column sums exactly to the (rounded) original principal
   *   - the final remaining balance is exactly 0.00
   *   - the interest column sums exactly to the (rounded) total interest
   * regardless of how many rows/roundings are involved.
   */
  function buildRows(principal, cumPrincipalExact, cumInterestExact, months) {
    const principalR = round2(principal);
    const rows = [];
    let prevPD = 0; // previous cumulative principal, rounded
    let prevID = 0; // previous cumulative interest, rounded
    for (let i = 1; i <= months; i++) {
      const cpd = round2(cumPrincipalExact[i]);
      const cid = round2(cumInterestExact[i]);
      const principalDisp = round2(cpd - prevPD);
      const interestDisp = round2(cid - prevID);
      const paymentDisp = round2(principalDisp + interestDisp);
      const balanceDisp = round2(principalR - cpd);
      rows.push({
        n: i,
        payment: paymentDisp,
        principal: principalDisp,
        interest: interestDisp,
        balance: Math.max(balanceDisp, 0),
      });
      prevPD = cpd;
      prevID = cid;
    }
    return rows;
  }

  function calcReducing(principal, annualRatePct, months) {
    const r = (annualRatePct / 100) / 12;
    const payment = r === 0 ? principal / months : (principal * r) / (1 - Math.pow(1 + r, -months));

    const cumPrincipalExact = new Array(months + 1).fill(0);
    const cumInterestExact = new Array(months + 1).fill(0);
    let balance = principal;
    for (let i = 1; i <= months; i++) {
      const interest = balance * r;
      const principalPortion = payment - interest;
      balance -= principalPortion;
      cumPrincipalExact[i] = cumPrincipalExact[i - 1] + principalPortion;
      cumInterestExact[i] = cumInterestExact[i - 1] + interest;
    }
    const rows = buildRows(principal, cumPrincipalExact, cumInterestExact, months);
    const totalInterest = round2(cumInterestExact[months]);
    const totalPayment = round2(principal) + totalInterest;
    return { payment: round2(payment), totalInterest, totalPayment, rows };
  }

  function calcFlat(principal, annualRatePct, months) {
    const years = months / 12;
    const totalInterestExact = principal * (annualRatePct / 100) * years;
    const payment = (principal + totalInterestExact) / months;

    const cumPrincipalExact = new Array(months + 1).fill(0);
    const cumInterestExact = new Array(months + 1).fill(0);
    for (let i = 1; i <= months; i++) {
      cumPrincipalExact[i] = (principal * i) / months;
      cumInterestExact[i] = (totalInterestExact * i) / months;
    }
    const rows = buildRows(principal, cumPrincipalExact, cumInterestExact, months);
    const totalInterest = round2(cumInterestExact[months]);
    const totalPayment = round2(principal) + totalInterest;
    return { payment: round2(payment), totalInterest, totalPayment, rows };
  }

  /* ---------- render ---------- */
  function renderEmpty() {
    stPayment.textContent = '—';
    stInterest.textContent = '—';
    stTotal.textContent = '—';
    amortBody.innerHTML = '';
    amortTable.classList.add('hidden');
    amortEmpty.classList.remove('hidden');
  }

  function render() {
    const principal = parseNumber(elPrincipal.value);
    const rate = parseNumber(elRate.value);
    const monthsRaw = parseNumber(elMonths.value);
    const months = Math.round(monthsRaw);

    if (!isFinite(principal) || principal <= 0 ||
        !isFinite(rate) || rate < 0 ||
        !isFinite(months) || months <= 0) {
      renderEmpty();
      return;
    }
    if (months > MAX_MONTHS) {
      renderEmpty();
      amortEmpty.textContent = 'จำนวนงวดสูงสุดที่รองรับคือ ' + MAX_MONTHS + ' งวด';
      return;
    }
    amortEmpty.textContent = 'กรอกข้อมูลด้านซ้ายให้ครบเพื่อดูตารางผ่อนชำระ';

    const result = method === 'flat'
      ? calcFlat(principal, rate, months)
      : calcReducing(principal, rate, months);

    stPayment.textContent = fmtMoney(result.payment);
    stInterest.textContent = fmtMoney(result.totalInterest);
    stTotal.textContent = fmtMoney(result.totalPayment);

    amortTable.classList.remove('hidden');
    amortEmpty.classList.add('hidden');

    const frag = document.createDocumentFragment();
    result.rows.forEach(row => {
      const tr = document.createElement('tr');
      if (row.n === months) tr.className = 'amort-last';
      tr.innerHTML =
        '<td>' + row.n + '</td>' +
        '<td>' + fmtPlain(row.payment) + '</td>' +
        '<td>' + fmtPlain(row.principal) + '</td>' +
        '<td>' + fmtPlain(row.interest) + '</td>' +
        '<td>' + fmtPlain(row.balance) + '</td>';
      frag.appendChild(tr);
    });
    amortBody.innerHTML = '';
    amortBody.appendChild(frag);
  }

  /* ---------- events ---------- */
  [elPrincipal, elRate, elMonths].forEach(el => {
    el.addEventListener('input', render);
  });
  elPrincipal.addEventListener('blur', () => { fmtOnBlur(elPrincipal); });
  elRate.addEventListener('blur', () => { fmtOnBlur(elRate); });

  methodTabs.querySelectorAll('button').forEach(b => {
    b.onclick = () => {
      methodTabs.querySelectorAll('button').forEach(x => x.classList.remove('on'));
      b.classList.add('on');
      method = b.dataset.v;
      methodHint.textContent = HINTS[method] || '';
      render();
    };
  });

  render();
})();
