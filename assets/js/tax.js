/* คำนวณภาษีเงินได้บุคคลธรรมดา — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์
 *
 * อัตราภาษีเงินได้บุคคลธรรมดาแบบขั้นบันได (มาตรฐานปีภาษี 2568/2025) และเพดานค่าลดหย่อนหลัก
 * โปรดดูป้ายหมายเหตุบนหน้าเว็บ — เกณฑ์เปลี่ยนแปลงได้รายปี ควรตรวจกับกรมสรรพากรก่อนใช้จริง
 */
(function () {
  'use strict';

  /* =========================================================
   * ส่วนคำนวณล้วน (pure) — ไม่แตะ DOM เลย เพื่อให้ทดสอบแยกได้ง่าย
   * ========================================================= */

  // [ขั้นต่ำ(รวม), ขั้นสูง(ไม่รวม), อัตรา]
  var BRACKETS = [
    [0, 150000, 0],
    [150000, 300000, 0.05],
    [300000, 500000, 0.10],
    [500000, 750000, 0.15],
    [750000, 1000000, 0.20],
    [1000000, 2000000, 0.25],
    [2000000, 5000000, 0.30],
    [5000000, Infinity, 0.35],
  ];

  function round2(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
  }
  function clamp(n, min, max) {
    if (!isFinite(n)) n = 0;
    if (n < min) return min;
    if (n > max) return max;
    return n;
  }

  /** ภาษีแบบขั้นบันได (marginal) จากเงินได้สุทธิ — คืนภาษีรวม + รายละเอียดแต่ละขั้น */
  function taxFromNet(net) {
    net = Math.max(0, net);
    var rows = [];
    var total = 0;
    for (var i = 0; i < BRACKETS.length; i++) {
      var min = BRACKETS[i][0], max = BRACKETS[i][1], rate = BRACKETS[i][2];
      var amount = Math.max(0, Math.min(net, max) - min);
      var tax = round2(amount * rate);
      rows.push({ min: min, max: max, rate: rate, amount: round2(amount), tax: tax });
      total += tax;
    }
    return { total: round2(total), rows: rows };
  }

  /**
   * คำนวณภาษีทั้งชุดจากเงินได้ + ค่าลดหย่อนต่างๆ (ค่าดิบที่ผู้ใช้กรอก จะถูกครอบเพดานในนี้)
   * input: { income, spouse(bool), children, parents, social, life, health, ssf, rmf, mortgage, donation }
   */
  function computeTax(input) {
    var income = Math.max(0, isFinite(input.income) ? input.income : 0);

    var expense = round2(Math.min(income * 0.5, 100000));
    var personal = 60000;
    var spouseAmt = input.spouse ? 60000 : 0;

    var childrenCount = Math.max(0, Math.round(isFinite(input.children) ? input.children : 0));
    var childrenAmt = childrenCount * 30000;

    var parentsRaw = Math.max(0, Math.round(isFinite(input.parents) ? input.parents : 0));
    var parentsCount = Math.min(parentsRaw, 4);
    var parentsAmt = parentsCount * 30000;

    var socialCapped = round2(clamp(input.social, 0, 9000));

    var lifeCapped = round2(clamp(input.life, 0, 100000));
    var healthCapped = round2(clamp(input.health, 0, 25000));
    var insuranceCombined = round2(Math.min(lifeCapped + healthCapped, 100000));

    var ssfLimit = Math.min(200000, income * 0.3);
    var ssfCapped = round2(clamp(input.ssf, 0, ssfLimit));

    var rmfLimit = Math.min(500000, income * 0.3);
    var rmfCapped = round2(clamp(input.rmf, 0, rmfLimit));

    var mortgageCapped = round2(clamp(input.mortgage, 0, 100000));

    var preDonationDeductions = round2(
      expense + personal + spouseAmt + childrenAmt + parentsAmt +
      socialCapped + insuranceCombined + ssfCapped + rmfCapped + mortgageCapped
    );
    var incomeAfterOther = Math.max(0, round2(income - preDonationDeductions));
    var donationCap = round2(incomeAfterOther * 0.10);
    var donationCapped = round2(clamp(input.donation, 0, donationCap));

    var totalDeductions = round2(preDonationDeductions + donationCapped);
    var netIncome = Math.max(0, round2(income - totalDeductions));

    var taxResult = taxFromNet(netIncome);
    var effectiveRate = income > 0 ? taxResult.total / income : 0;

    return {
      income: income,
      expense: expense,
      personal: personal,
      spouseAmt: spouseAmt,
      childrenCount: childrenCount,
      childrenAmt: childrenAmt,
      parentsRaw: parentsRaw,
      parentsCount: parentsCount,
      parentsAmt: parentsAmt,
      socialCapped: socialCapped,
      lifeCapped: lifeCapped,
      healthCapped: healthCapped,
      insuranceCombined: insuranceCombined,
      ssfCapped: ssfCapped,
      ssfLimit: round2(ssfLimit),
      rmfCapped: rmfCapped,
      rmfLimit: round2(rmfLimit),
      mortgageCapped: mortgageCapped,
      donationCap: donationCap,
      donationCapped: donationCapped,
      totalDeductions: totalDeductions,
      netIncome: netIncome,
      tax: taxResult.total,
      bracketRows: taxResult.rows,
      effectiveRate: effectiveRate,
    };
  }

  // เผยแพร่ไว้บน window เผื่อทดสอบ/ดีบักผ่านคอนโซล
  window.__taxCompute = computeTax;

  /* =========================================================
   * DOM wiring
   * ========================================================= */
  function byId(id) { return document.getElementById(id); }

  function parseNumber(str) {
    if (str == null) return NaN;
    var cleaned = String(str).replace(/,/g, '').trim();
    if (cleaned === '') return NaN;
    var n = Number(cleaned);
    return isFinite(n) ? n : NaN;
  }
  function numOr0(str) {
    var n = parseNumber(str);
    return isFinite(n) ? n : 0;
  }
  function fmtPlain(n) {
    return (isFinite(n) ? n : 0).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }
  function fmtBaht(n) {
    return fmtPlain(n) + ' บาท';
  }
  function fmtOnBlur(input) {
    var n = parseNumber(input.value);
    if (isFinite(n)) {
      input.value = n.toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }
  }

  var elIncome = byId('t-income');
  var elChildren = byId('t-children');
  var elParents = byId('t-parents');
  var elSocial = byId('t-social');
  var elLife = byId('t-life');
  var elHealth = byId('t-health');
  var elSsf = byId('t-ssf');
  var elRmf = byId('t-rmf');
  var elMortgage = byId('t-mortgage');
  var elDonation = byId('t-donation');
  var spouseSeg = byId('t-spouse-seg');

  var expenseHint = byId('t-expense-hint');
  var parentsHint = byId('t-parents-hint');
  var socialHint = byId('t-social-hint');
  var insuranceHint = byId('t-insurance-hint');
  var fundHint = byId('t-fund-hint');
  var otherHint = byId('t-other-hint');

  var taxTotalEl = byId('t-tax-total');
  var netIncomeEl = byId('t-net-income');
  var effRateEl = byId('t-effective-rate');
  var bracketBody = byId('t-bracket-body');

  var spouseOn = false;

  function fmtRange(min, max) {
    if (max === Infinity) return fmtPlain(min + 1) + ' ขึ้นไป';
    if (min === 0) return '0 – ' + fmtPlain(max);
    return fmtPlain(min + 1) + ' – ' + fmtPlain(max);
  }

  function render() {
    var input = {
      income: parseNumber(elIncome.value),
      spouse: spouseOn,
      children: numOr0(elChildren.value),
      parents: numOr0(elParents.value),
      social: numOr0(elSocial.value),
      life: numOr0(elLife.value),
      health: numOr0(elHealth.value),
      ssf: numOr0(elSsf.value),
      rmf: numOr0(elRmf.value),
      mortgage: numOr0(elMortgage.value),
      donation: numOr0(elDonation.value),
    };
    if (!isFinite(input.income)) input.income = 0;

    var r = computeTax(input);

    // เงินได้/ค่าใช้จ่าย
    expenseHint.textContent = 'ค่าใช้จ่าย (หักอัตโนมัติ 50% ไม่เกิน 100,000 บาท): ' + fmtBaht(r.expense);

    // บิดามารดา
    if (r.parentsRaw > 4) {
      parentsHint.textContent = 'ลดหย่อนบิดามารดาได้สูงสุด 4 คน — ใช้สิทธิ 4 คน (' + fmtBaht(r.parentsAmt) + ')';
    } else if (r.parentsCount > 0) {
      parentsHint.textContent = 'ใช้สิทธิ ' + r.parentsCount + ' คน (' + fmtBaht(r.parentsAmt) + ')';
    } else {
      parentsHint.textContent = '';
    }

    // ประกันสังคม
    var socialRaw = numOr0(elSocial.value);
    socialHint.textContent = socialRaw > 9000
      ? 'เกินเพดาน — หักได้จริง: ' + fmtBaht(r.socialCapped)
      : 'หักได้จริง: ' + fmtBaht(r.socialCapped);

    // ประกันชีวิต + สุขภาพ (เพดานรวม)
    var lifeRaw = numOr0(elLife.value), healthRaw = numOr0(elHealth.value);
    var insCapNote = (lifeRaw > 100000 || healthRaw > 25000 || (lifeRaw + healthRaw) > 100000) ? ' (ครอบเพดานแล้ว)' : '';
    insuranceHint.textContent = 'รวมประกันชีวิต+สุขภาพหักได้ไม่เกิน 100,000 บาท — หักได้จริงรวม: ' + fmtBaht(r.insuranceCombined) + insCapNote;

    // SSF / RMF
    var ssfRaw = numOr0(elSsf.value), rmfRaw = numOr0(elRmf.value);
    var ssfNote = ssfRaw > r.ssfLimit ? ' (ครอบเพดาน ' + fmtBaht(r.ssfLimit) + ')' : '';
    var rmfNote = rmfRaw > r.rmfLimit ? ' (ครอบเพดาน ' + fmtBaht(r.rmfLimit) + ')' : '';
    fundHint.textContent = 'SSF หักได้จริง: ' + fmtBaht(r.ssfCapped) + ssfNote + ' · RMF หักได้จริง: ' + fmtBaht(r.rmfCapped) + rmfNote;

    // ดอกเบี้ยบ้าน + บริจาค
    var mortRaw = numOr0(elMortgage.value), donRaw = numOr0(elDonation.value);
    var mortNote = mortRaw > 100000 ? ' (ครอบเพดาน)' : '';
    var donNote = donRaw > r.donationCap ? ' (ครอบเพดาน 10% = ' + fmtBaht(r.donationCap) + ')' : '';
    otherHint.textContent = 'ดอกเบี้ยบ้านหักได้จริง: ' + fmtBaht(r.mortgageCapped) + mortNote +
      ' · บริจาคหักได้จริง: ' + fmtBaht(r.donationCapped) + donNote;

    // ผลลัพธ์หลัก
    taxTotalEl.textContent = fmtBaht(r.tax);
    netIncomeEl.textContent = fmtBaht(r.netIncome);
    effRateEl.textContent = (r.effectiveRate * 100).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';

    // ตารางขั้นบันได
    var frag = document.createDocumentFragment();
    r.bracketRows.forEach(function (row) {
      var tr = document.createElement('tr');
      if (row.amount > 0) tr.className = 'amort-last';
      tr.innerHTML =
        '<td>' + fmtRange(row.min, row.max) + '</td>' +
        '<td>' + (row.rate * 100) + '%</td>' +
        '<td>' + fmtPlain(row.amount) + '</td>' +
        '<td>' + fmtPlain(row.tax) + '</td>';
      frag.appendChild(tr);
    });
    bracketBody.innerHTML = '';
    bracketBody.appendChild(frag);
  }

  /* ---------- events ---------- */
  [elIncome, elChildren, elParents, elSocial, elLife, elHealth, elSsf, elRmf, elMortgage, elDonation].forEach(function (el) {
    el.addEventListener('input', render);
    el.addEventListener('blur', function () { fmtOnBlur(el); });
  });

  spouseSeg.querySelectorAll('button').forEach(function (b) {
    b.onclick = function () {
      spouseSeg.querySelectorAll('button').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
      spouseOn = b.dataset.v === '1';
      render();
    };
  });

  render();
})();
