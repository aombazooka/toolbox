/* อายุ · นับวัน · บวก/ลบวัน · แปลง พ.ศ.↔ค.ศ. — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์ */
(function () {
  function byId(id) { return document.getElementById(id); }

  /* =========================================================
   * Thai calendar names — mirrors includes/helpers.php's thai_date()
   * convention (BE year = CE year + 543), but spelled out in FULL
   * (thai_date() uses abbreviated names like "ก.ค." — this tool needs
   * the full style, e.g. "25 กรกฎาคม 2569", so the full list is kept
   * here in JS rather than requiring the PHP helper client-side).
   * ========================================================= */
  var THAI_MONTHS_FULL = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
  var THAI_WEEKDAYS = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];

  /* ---------- date-string <-> parts helpers ----------
   * All arithmetic below works on plain integer {y, m, d} (m = 1-12)
   * or on UTC-midnight Date objects, and NEVER on local-time Date
   * objects — this sidesteps DST/timezone drift entirely and keeps
   * the day-count math exact across leap years.
   */
  function parseYmd(str) {
    // expects "YYYY-MM-DD" from <input type=date>
    if (!str) return null;
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(str);
    if (!m) return null;
    var y = parseInt(m[1], 10), mo = parseInt(m[2], 10), d = parseInt(m[3], 10);
    if (mo < 1 || mo > 12 || d < 1 || d > 31) return null;
    return { y: y, m: mo, d: d };
  }
  function isLeapYear(y) {
    return (y % 4 === 0 && y % 100 !== 0) || (y % 400 === 0);
  }
  /** Number of days in a given (1-indexed) month of a given year. */
  function daysInMonth(y, m) {
    return new Date(Date.UTC(y, m, 0)).getUTCDate();
  }
  function utcMs(p) { return Date.UTC(p.y, p.m - 1, p.d); }
  function fromUtcMs(ms) {
    var dt = new Date(ms);
    return { y: dt.getUTCFullYear(), m: dt.getUTCMonth() + 1, d: dt.getUTCDate() };
  }
  function todayParts() {
    var n = new Date();
    return { y: n.getFullYear(), m: n.getMonth() + 1, d: n.getDate() };
  }
  function todayInputValue() {
    var t = todayParts();
    return t.y + '-' + String(t.m).padStart(2, '0') + '-' + String(t.d).padStart(2, '0');
  }
  function fmtGregorian(p) {
    return String(p.d).padStart(2, '0') + '/' + String(p.m).padStart(2, '0') + '/' + p.y;
  }
  function thaiDateFull(p) {
    return p.d + ' ' + THAI_MONTHS_FULL[p.m] + ' ' + (p.y + 543);
  }
  function weekdayThai(p) {
    return THAI_WEEKDAYS[new Date(utcMs(p)).getUTCDay()];
  }
  function fmtNum(n) { return n.toLocaleString('th-TH'); }

  /* ---------- generic segmented-tab wiring (same pattern as calc.js) ---------- */
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
    var on = tabsEl.querySelector('button.on');
    return on ? on.dataset.v : null;
  }

  /* =========================================================
   * MODE SWITCHER (4 main tabs)
   * ========================================================= */
  var modeTabs = byId('mode-tabs');
  var MODES = ['age', 'between', 'addsub', 'convert'];
  var mode = 'age';

  function showMode(m) {
    mode = m;
    MODES.forEach(function (mm) {
      var panel = byId('panel-' + mm);
      var result = byId('result-' + mm);
      if (panel) panel.classList.toggle('hidden', mm !== m);
      if (result) result.classList.toggle('hidden', mm !== m);
    });
    renderAll();
  }
  wireSeg(modeTabs, showMode);

  /* =========================================================
   * อายุ (Age)
   * ---------------------------------------------------------
   * Convention for the missing Feb 29 in non-leap "anniversary" years:
   * this uses plain calendar-field subtraction (the same algorithm used
   * by PHP's DateInterval / Java's Period.between), which treats the
   * jump from Feb 28 -> Mar 1 as the point the age actually increments
   * in a non-leap year (i.e. someone born Feb 29 is considered to still
   * be their old age on Feb 28, and turns a year older on Mar 1). This
   * is deterministic, never goes negative, and is internally consistent
   * (verified: age on Feb 28 of a non-leap year + 1 day == age on Mar 1
   * of that year, exactly).
   * ========================================================= */
  var ageBirth = byId('age-birth');
  var ageAsof = byId('age-asof');
  var ageYearsEl = byId('age-years');
  var ageMonthsEl = byId('age-months');
  var ageDaysEl = byId('age-days');
  var ageNextEl = byId('age-next');
  var ageDetailEl = byId('age-detail');

  ageAsof.value = todayInputValue();

  /** Civil calendar-field age breakdown between two {y,m,d} points (to >= from is NOT required — caller ensures order). */
  function ageBreakdown(from, to) {
    var years = to.y - from.y;
    var months = to.m - from.m;
    var days = to.d - from.d;
    if (days < 0) {
      months -= 1;
      var borrowM = to.m - 1, borrowY = to.y;
      if (borrowM === 0) { borrowM = 12; borrowY -= 1; }
      days += daysInMonth(borrowY, borrowM);
    }
    if (months < 0) {
      years -= 1;
      months += 12;
    }
    return { years: years, months: months, days: days };
  }

  /** Days remaining until the next birthday on/after `from`. Feb-29 birthdays
   *  map to Mar-1 in non-leap years, consistent with ageBreakdown's convention. */
  function daysUntilNextBirthday(birth, from) {
    function occurrenceInYear(y) {
      if (birth.m === 2 && birth.d === 29 && !isLeapYear(y)) return { y: y, m: 3, d: 1 };
      return { y: y, m: birth.m, d: birth.d };
    }
    var candidate = occurrenceInYear(from.y);
    if (utcMs(candidate) < utcMs(from)) {
      candidate = occurrenceInYear(from.y + 1);
    }
    return Math.round((utcMs(candidate) - utcMs(from)) / 86400000);
  }

  function renderAge() {
    var birth = parseYmd(ageBirth.value);
    var asof = parseYmd(ageAsof.value) || todayParts();
    if (!birth) {
      ageYearsEl.textContent = '—'; ageMonthsEl.textContent = '—'; ageDaysEl.textContent = '—'; ageNextEl.textContent = '—';
      ageDetailEl.textContent = 'กรอกวันเกิดด้านซ้ายเพื่อดูผลลัพธ์';
      return;
    }
    if (utcMs(birth) > utcMs(asof)) {
      ageYearsEl.textContent = '—'; ageMonthsEl.textContent = '—'; ageDaysEl.textContent = '—'; ageNextEl.textContent = '—';
      ageDetailEl.textContent = 'วันเกิดต้องไม่มาหลังวันที่ใช้คำนวณ ("คำนวณ ณ วันที่")';
      return;
    }
    var b = ageBreakdown(birth, asof);
    var nextDays = daysUntilNextBirthday(birth, asof);
    ageYearsEl.textContent = fmtNum(b.years);
    ageMonthsEl.textContent = fmtNum(b.months);
    ageDaysEl.textContent = fmtNum(b.days);
    ageNextEl.textContent = nextDays === 0 ? 'วันนี้!' : fmtNum(nextDays);
    ageDetailEl.textContent = 'เกิดวันที่ ' + thaiDateFull(birth) + ' อายุ ณ วันที่ ' + thaiDateFull(asof) +
      ' คือ ' + fmtNum(b.years) + ' ปี ' + fmtNum(b.months) + ' เดือน ' + fmtNum(b.days) + ' วัน';
  }

  /* =========================================================
   * นับวันระหว่าง 2 วันที่ (Days between)
   * ========================================================= */
  var betweenA = byId('between-a');
  var betweenB = byId('between-b');
  var betweenDaysEl = byId('between-days');
  var betweenWeeksEl = byId('between-weeks');
  var betweenDetailEl = byId('between-detail');

  function renderBetween() {
    var a = parseYmd(betweenA.value);
    var b = parseYmd(betweenB.value);
    if (!a || !b) {
      betweenDaysEl.textContent = '—'; betweenWeeksEl.textContent = '—';
      betweenDetailEl.textContent = 'กรอกวันที่ A และ B ด้านซ้าย';
      return;
    }
    var diffDays = Math.abs(Math.round((utcMs(b) - utcMs(a)) / 86400000));
    var weeks = Math.floor(diffDays / 7);
    var rem = diffDays % 7;
    betweenDaysEl.textContent = fmtNum(diffDays) + ' วัน';
    betweenWeeksEl.textContent = fmtNum(weeks) + ' สัปดาห์ ' + fmtNum(rem) + ' วัน';
    betweenDetailEl.textContent = 'ระหว่าง ' + thaiDateFull(a) + ' กับ ' + thaiDateFull(b) + ' ห่างกัน ' + fmtNum(diffDays) + ' วัน';
  }

  /* =========================================================
   * บวก/ลบวัน (Add/subtract days) — pure UTC-millisecond arithmetic,
   * so month/year rollovers (incl. leap-day Feb 29) resolve automatically.
   * ========================================================= */
  var addStart = byId('add-start');
  var addDays = byId('add-days');
  var addResultDateEl = byId('add-result-date');
  var addDetailEl = byId('add-detail');

  function renderAddSub() {
    var start = parseYmd(addStart.value);
    var n = parseInt(String(addDays.value).replace(/[^\-0-9]/g, ''), 10);
    if (!start || !isFinite(n) || isNaN(n)) {
      addResultDateEl.textContent = '—';
      addResultDateEl.classList.add('empty');
      addDetailEl.textContent = 'กรอกวันที่เริ่มต้นและจำนวนวันด้านซ้าย';
      return;
    }
    var resultMs = utcMs(start) + n * 86400000;
    var result = fromUtcMs(resultMs);
    addResultDateEl.classList.remove('empty');
    addResultDateEl.textContent = thaiDateFull(result) + ' (' + fmtGregorian(result) + ')';
    addDetailEl.textContent = weekdayThai(result) + ' — จาก ' + thaiDateFull(start) + ' ' +
      (n >= 0 ? 'บวก ' : 'ลบ ') + fmtNum(Math.abs(n)) + ' วัน';
  }

  /* =========================================================
   * แปลง พ.ศ.↔ค.ศ.
   * ========================================================= */
  var convDirTabs = byId('conv-dir');
  var convYearInput = byId('conv-year');
  var convYearLabel = byId('conv-year-label');
  var convYearOutEl = byId('conv-year-out');
  var convYearOutLabelEl = byId('conv-year-out-label');
  var convYearDetailEl = byId('conv-year-detail');
  var convDateInput = byId('conv-date');
  var convDateOutEl = byId('conv-date-out');
  var convDateDetailEl = byId('conv-date-detail');

  var CONV_LABELS = {
    ce2be: { label: 'ปี ค.ศ.', outLabel: 'ปี พ.ศ.' },
    be2ce: { label: 'ปี พ.ศ.', outLabel: 'ปี ค.ศ.' },
  };

  function renderConvert() {
    var dir = segValue(convDirTabs) || 'ce2be';
    var cfg = CONV_LABELS[dir];
    convYearLabel.textContent = cfg.label;
    convYearOutLabelEl.textContent = cfg.outLabel;

    var y = parseInt(String(convYearInput.value).replace(/[^\-0-9]/g, ''), 10);
    if (!isFinite(y) || isNaN(y) || y <= 0) {
      convYearOutEl.textContent = '—';
      convYearDetailEl.textContent = 'กรอกปีด้านซ้าย';
    } else if (dir === 'ce2be') {
      var be = y + 543;
      convYearOutEl.textContent = fmtNum(be);
      convYearDetailEl.textContent = 'ค.ศ. ' + fmtNum(y) + ' ตรงกับ พ.ศ. ' + fmtNum(be);
    } else {
      var ce = y - 543;
      convYearOutEl.textContent = fmtNum(ce);
      convYearDetailEl.textContent = 'พ.ศ. ' + fmtNum(y) + ' ตรงกับ ค.ศ. ' + fmtNum(ce);
    }

    var dp = parseYmd(convDateInput.value);
    if (!dp) {
      convDateOutEl.textContent = '—';
      convDateOutEl.classList.add('empty');
      convDateDetailEl.textContent = 'เลือกวันที่ด้านซ้ายเพื่อแปลงเป็นรูปแบบไทย';
    } else {
      convDateOutEl.classList.remove('empty');
      convDateOutEl.textContent = thaiDateFull(dp);
      convDateDetailEl.textContent = weekdayThai(dp) + ' ที่ ' + thaiDateFull(dp) + ' (ตรงกับ ' + fmtGregorian(dp) + ' แบบ ค.ศ.)';
    }
  }
  wireSeg(convDirTabs, renderConvert);
  convYearInput.value = todayParts().y;

  /* ---------- render dispatch ---------- */
  function renderAll() {
    if (mode === 'age') renderAge();
    else if (mode === 'between') renderBetween();
    else if (mode === 'addsub') renderAddSub();
    else if (mode === 'convert') renderConvert();
  }

  /* ---------- wire live-update inputs ---------- */
  [ageBirth, ageAsof, betweenA, betweenB, addStart, addDays, convYearInput, convDateInput].forEach(function (el) {
    el.addEventListener('input', renderAll);
    el.addEventListener('change', renderAll);
  });

  renderAll();
})();
