/* เครื่องมือสุ่ม: สุ่มรายชื่อ / วงล้อสุ่ม / สุ่มเลข / ลูกเต๋า-เหรียญ
 * ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์
 * ความสุ่มทั้งหมดมาจาก crypto.getRandomValues (ไม่ใช้ Math.random) พร้อมตัดอคติแบบ modulo
 * ด้วยเทคนิค rejection sampling
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
    // ตัดส่วนเกินที่ไม่ใช่จำนวนเท่าของ range ทิ้ง (rejection) เพื่อไม่ให้ค่าที่เหลือมีอคติ
    const limit = Math.floor(MAX_UINT32 / range) * range;
    const buf = new Uint32Array(1);
    let x;
    do {
      crypto.getRandomValues(buf);
      x = buf[0];
    } while (x >= limit);
    return min + (x % range);
  }

  /** Fisher–Yates shuffle ในที่ (in-place) โดยใช้ randomInt ที่ไม่มีอคติ */
  function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = randomInt(0, i);
      const t = arr[i]; arr[i] = arr[j]; arr[j] = t;
    }
    return arr;
  }

  /**
   * สุ่ม k ตัวเลขที่ไม่ซ้ำกันจากช่วง [min, max] (0-indexed ภายในเป็น [0, range-1])
   * ใช้อัลกอริทึมของ Floyd (partial Fisher–Yates แบบไม่ต้องสร้าง array เต็มช่วง)
   * ทำงานเร็วแม้ range จะใหญ่มาก เพราะ time complexity ~ O(k)
   */
  function sampleUniqueInRange(min, max, k) {
    const range = max - min + 1;
    if (k > range) k = range;
    if (k <= 0) return [];
    const selected = new Set();
    for (let j = range - k; j < range; j++) {
      const t = randomInt(0, j);
      if (selected.has(t)) selected.add(j);
      else selected.add(t);
    }
    const out = Array.from(selected, function (v) { return v + min; });
    return shuffle(out);
  }

  /* expose for manual console verification if needed */
  window.__rndInternals = { randomInt: randomInt, shuffle: shuffle, sampleUniqueInRange: sampleUniqueInRange };

  /* ---------- generic segmented-tab helper (same pattern as calc.js) ---------- */
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
  function parseListLines(text) {
    return String(text || '')
      .split(/\r?\n/)
      .map(function (s) { return s.trim(); })
      .filter(function (s) { return s.length > 0; });
  }
  function parseIntSafe(str, fallback) {
    const n = parseInt(String(str || '').replace(/,/g, '').trim(), 10);
    return isFinite(n) ? n : fallback;
  }
  function copyText(text, okMsg) {
    if (!text) { window.toast('ไม่มีผลลัพธ์ให้คัดลอก', 'err'); return; }
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
   * MODE SWITCHER (main 4 tabs)
   * ========================================================= */
  const modeTabs = byId('mode-tabs');
  const MODES = ['name', 'wheel', 'number', 'dice'];
  let mode = 'name';

  function showMode(m) {
    mode = m;
    MODES.forEach(function (mm) {
      const panel = byId('panel-' + mm);
      const result = byId('result-' + mm);
      if (panel) panel.classList.toggle('hidden', mm !== m);
      if (result) result.classList.toggle('hidden', mm !== m);
    });
  }
  wireSeg(modeTabs, showMode);

  /* =========================================================
   * (ก) สุ่มรายชื่อ (Name picker)
   * ========================================================= */
  const nameListEl = byId('name-list');
  const nameCountEl = byId('name-count');
  const nameRepeatToggle = byId('name-repeat-toggle');
  const nameEmptyEl = byId('name-empty');
  const nameWinnersEl = byId('name-winners');
  const nameDrawBtn = byId('name-draw-btn');
  const nameCopyBtn = byId('name-copy-btn');
  let lastNameWinners = [];

  nameRepeatToggle.onclick = function () { nameRepeatToggle.classList.toggle('on'); };

  function drawNames() {
    const list = parseListLines(nameListEl.value);
    if (list.length === 0) {
      window.toast('กรุณาวางรายชื่ออย่างน้อย 1 ชื่อ', 'err');
      return;
    }
    let count = parseIntSafe(nameCountEl.value, 1);
    if (count < 1) count = 1;
    const allowRepeat = nameRepeatToggle.classList.contains('on');

    let winners;
    if (allowRepeat) {
      if (count > 500) { count = 500; nameCountEl.value = '500'; window.toast('จำกัดสูงสุด 500 ครั้งต่อการสุ่ม', 'err'); }
      winners = [];
      for (let i = 0; i < count; i++) {
        winners.push(list[randomInt(0, list.length - 1)]);
      }
    } else {
      if (count > list.length) {
        count = list.length;
        nameCountEl.value = String(count);
        window.toast('มีรายชื่อไม่พอ ปรับจำนวนที่สุ่มเป็น ' + count + ' อัตโนมัติ', 'err');
      }
      const idx = shuffle(list.map(function (_, i) { return i; }));
      winners = idx.slice(0, count).map(function (i) { return list[i]; });
    }

    lastNameWinners = winners;
    nameEmptyEl.classList.add('hidden');
    nameWinnersEl.classList.remove('hidden');
    nameWinnersEl.innerHTML = '';
    winners.forEach(function (name, i) {
      const chip = document.createElement('span');
      chip.className = 'chip win';
      chip.innerHTML = '<span class="chip-n">' + (i + 1) + '.</span> ' + escapeHtml(name);
      nameWinnersEl.appendChild(chip);
    });
    window.toast(winners.length === 1 ? ('ผู้ถูกสุ่มเลือกคือ ' + winners[0]) : ('สุ่มได้ ' + winners.length + ' รายชื่อ'), 'ok');
  }
  nameDrawBtn.onclick = drawNames;
  nameCopyBtn.onclick = function () { copyText(lastNameWinners.join('\n'), 'คัดลอกรายชื่อแล้ว'); };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* =========================================================
   * (ข) วงล้อสุ่ม (Spinning wheel)
   * ========================================================= */
  const wheelListEl = byId('wheel-list');
  const wheelSvgWrap = byId('wheel-svg-wrap');
  const wheelEmptyEl = byId('wheel-empty');
  const wheelRotor = byId('wheel-rotor');
  const wheelSpinBtn = byId('wheel-spin-btn');
  const wheelWinnerEl = byId('wheel-winner');
  const wheelWinnerLabelEl = byId('wheel-winner-label');

  const SVG_NS = 'http://www.w3.org/2000/svg';
  const WHEEL_COLORS = [
    '#f4a15c', '#5b8def', '#30a46c', '#e5484d', '#a970ff', '#f5c518',
    '#34c3c9', '#ff7ab6', '#7c8cff', '#ffa94d', '#4fd1c5', '#c084fc',
  ];

  let wheelItems = [];
  let wheelTotalRotation = 0;
  let wheelSpinning = false;

  function polarPoint(angleDeg, r) {
    const rad = (angleDeg * Math.PI) / 180;
    return { x: 150 + r * Math.sin(rad), y: 150 - r * Math.cos(rad) };
  }

  function truncateLabel(s, max) {
    s = String(s);
    return s.length > max ? s.slice(0, max - 1) + '…' : s;
  }

  function renderWheel(items) {
    wheelItems = items.slice();
    wheelRotor.innerHTML = '';
    const n = wheelItems.length;
    wheelSpinBtn.disabled = n < 2;

    if (n === 0) {
      wheelSvgWrap.classList.add('hidden');
      wheelEmptyEl.classList.remove('hidden');
      return;
    }
    wheelSvgWrap.classList.remove('hidden');
    wheelEmptyEl.classList.add('hidden');

    const sliceAngle = 360 / n;
    const fontSize = Math.max(8, Math.min(14, 190 / n));
    const r = 140;

    for (let i = 0; i < n; i++) {
      const start = i * sliceAngle;
      const end = start + sliceAngle;
      const mid = start + sliceAngle / 2;
      const large = sliceAngle > 180 ? 1 : 0;

      const path = document.createElementNS(SVG_NS, 'path');
      let d;
      if (n === 1) {
        d = 'M150,10 A140,140 0 1,1 150,290 A140,140 0 1,1 150,10 Z';
      } else {
        const p1 = polarPoint(start, r);
        const p2 = polarPoint(end, r);
        d = 'M150,150 L' + p1.x.toFixed(2) + ',' + p1.y.toFixed(2) +
          ' A140,140 0 ' + large + ',1 ' + p2.x.toFixed(2) + ',' + p2.y.toFixed(2) + ' Z';
      }
      path.setAttribute('d', d);
      path.setAttribute('fill', WHEEL_COLORS[i % WHEEL_COLORS.length]);
      path.setAttribute('class', 'wheel-slice');
      path.dataset.index = String(i);
      wheelRotor.appendChild(path);

      const g = document.createElementNS(SVG_NS, 'g');
      g.setAttribute('transform', 'rotate(' + mid + ' 150 150)');
      const text = document.createElementNS(SVG_NS, 'text');
      text.setAttribute('x', '150');
      text.setAttribute('y', '40');
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('class', 'wheel-label');
      text.setAttribute('style', 'font-size:' + fontSize + 'px');
      if (mid > 90 && mid < 270) text.setAttribute('transform', 'rotate(180 150 40)');
      text.textContent = truncateLabel(wheelItems[i], 16);
      g.appendChild(text);
      wheelRotor.appendChild(g);
    }
  }

  function spinWheel() {
    if (wheelSpinning) return;
    const items = wheelItems;
    if (items.length < 2) {
      window.toast('ใส่รายการอย่างน้อย 2 รายการก่อนหมุนวงล้อ', 'err');
      return;
    }
    wheelSpinning = true;
    wheelSpinBtn.disabled = true;
    wheelWinnerEl.textContent = '';
    wheelWinnerLabelEl.textContent = '';
    Array.prototype.forEach.call(wheelRotor.querySelectorAll('.wheel-slice'), function (p) {
      p.classList.remove('winner');
    });

    const n = items.length;
    const sliceAngle = 360 / n;
    const winnerIndex = randomInt(0, n - 1);
    const centerAngle = winnerIndex * sliceAngle + sliceAngle / 2;

    const currentEffective = ((wheelTotalRotation % 360) + 360) % 360;
    let targetEffective = (360 - centerAngle) % 360;
    const jitterMax = sliceAngle * 0.32;
    const jitter = (randomInt(0, 2000) / 1000 - 1) * jitterMax;
    targetEffective = ((targetEffective + jitter) % 360 + 360) % 360;

    let delta = targetEffective - currentEffective;
    if (delta < 10) delta += 360;
    const extraSpins = randomInt(6, 9);
    wheelTotalRotation += extraSpins * 360 + delta;

    const SPIN_DURATION_MS = 4600;
    wheelRotor.style.transition = 'transform ' + (SPIN_DURATION_MS / 1000) + 's cubic-bezier(.13,.72,.14,1)';
    wheelRotor.style.transform = 'rotate(' + wheelTotalRotation + 'deg)';

    let finished = false;
    function finish() {
      if (finished) return;
      finished = true;
      wheelRotor.removeEventListener('transitionend', onEnd);
      clearTimeout(fallbackTimer);
      wheelSpinning = false;
      wheelSpinBtn.disabled = wheelItems.length < 2;
      const winnerName = items[winnerIndex];
      wheelWinnerLabelEl.textContent = 'ผู้ชนะ';
      wheelWinnerEl.textContent = winnerName;
      const slicePath = wheelRotor.querySelector('.wheel-slice[data-index="' + winnerIndex + '"]');
      if (slicePath) slicePath.classList.add('winner');
      window.toast('ผู้ชนะคือ ' + winnerName, 'ok');
    }
    function onEnd(e) {
      if (e && e.target !== wheelRotor) return;
      if (e && e.propertyName && e.propertyName !== 'transform') return;
      finish();
    }
    wheelRotor.addEventListener('transitionend', onEnd);
    // Fallback: some browsers/tabs (e.g. backgrounded/hidden tabs) throttle or skip
    // firing transitionend even though the transition itself completes — a timer
    // guarantees the winner is always revealed once the spin duration has elapsed.
    const fallbackTimer = setTimeout(finish, SPIN_DURATION_MS + 250);
  }

  wheelListEl.addEventListener('input', function () {
    renderWheel(parseListLines(wheelListEl.value));
  });
  wheelSpinBtn.onclick = spinWheel;
  renderWheel(parseListLines(wheelListEl.value));

  /* =========================================================
   * (ค) สุ่มเลข (Number picker)
   * ========================================================= */
  const numMinEl = byId('num-min');
  const numMaxEl = byId('num-max');
  const numCountEl = byId('num-count');
  const numUniqueToggle = byId('num-unique-toggle');
  const numEmptyEl = byId('num-empty');
  const numWinnersEl = byId('num-winners');
  const numDrawBtn = byId('num-draw-btn');
  const numCopyBtn = byId('num-copy-btn');
  let lastNumWinners = [];

  numUniqueToggle.onclick = function () { numUniqueToggle.classList.toggle('on'); };

  function drawNumbers() {
    let min = parseIntSafe(numMinEl.value, 1);
    let max = parseIntSafe(numMaxEl.value, 100);
    if (max < min) { const t = min; min = max; max = t; numMinEl.value = String(min); numMaxEl.value = String(max); }
    let count = parseIntSafe(numCountEl.value, 1);
    if (count < 1) count = 1;
    const unique = numUniqueToggle.classList.contains('on');
    const rangeSize = max - min + 1;

    if (unique && count > rangeSize) {
      count = rangeSize;
      numCountEl.value = String(count);
      window.toast('ช่วงตัวเลขมีแค่ ' + rangeSize + ' ค่า ปรับจำนวนที่สุ่มให้อัตโนมัติ', 'err');
    }
    if (!unique && count > 2000) {
      count = 2000;
      numCountEl.value = '2000';
      window.toast('จำกัดสูงสุด 2000 ตัวเลขต่อการสุ่ม', 'err');
    }

    let results;
    if (unique) {
      results = sampleUniqueInRange(min, max, count);
    } else {
      results = [];
      for (let i = 0; i < count; i++) results.push(randomInt(min, max));
    }

    lastNumWinners = results;
    numEmptyEl.classList.add('hidden');
    numWinnersEl.classList.remove('hidden');
    numWinnersEl.innerHTML = '';
    results.forEach(function (n) {
      const chip = document.createElement('span');
      chip.className = 'chip win';
      chip.textContent = n;
      numWinnersEl.appendChild(chip);
    });
    window.toast('สุ่มได้ ' + results.length + ' ตัวเลข', 'ok');
  }
  numDrawBtn.onclick = drawNumbers;
  numCopyBtn.onclick = function () { copyText(lastNumWinners.join(', '), 'คัดลอกตัวเลขแล้ว'); };

  /* =========================================================
   * (ง) ลูกเต๋า / เหรียญ
   * ========================================================= */
  const diceSubTabs = byId('dice-sub-tabs');
  const diceCountEl = byId('dice-count');
  const diceCountLabel = diceCountEl.previousElementSibling;
  const diceEmptyEl = byId('dice-empty');
  const diceResultsEl = byId('dice-results');
  const diceSumEl = byId('dice-sum');
  const diceRollBtn = byId('dice-roll-btn');

  const DIE_PATTERNS = {
    1: [4],
    2: [0, 8],
    3: [0, 4, 8],
    4: [0, 2, 6, 8],
    5: [0, 2, 4, 6, 8],
    6: [0, 2, 3, 5, 6, 8],
  };

  function renderDieFace(value) {
    const el = document.createElement('div');
    el.className = 'die-face';
    for (let i = 0; i < 9; i++) {
      const dot = document.createElement('i');
      if (DIE_PATTERNS[value].indexOf(i) !== -1) dot.classList.add('on');
      el.appendChild(dot);
    }
    return el;
  }
  function renderCoinFace(isHeads) {
    const el = document.createElement('div');
    el.className = 'coin-face' + (isHeads ? '' : ' tails');
    el.textContent = isHeads ? 'หัว' : 'ก้อย';
    return el;
  }

  function updateDiceSubUI(sub) {
    if (sub === 'die') {
      diceCountLabel.textContent = 'จำนวนลูกเต๋า';
      diceCountEl.value = diceCountEl.value || '5';
      diceRollBtn.textContent = 'ทอยลูกเต๋า';
    } else {
      diceCountLabel.textContent = 'จำนวนเหรียญ';
      diceRollBtn.textContent = 'โยนเหรียญ';
    }
  }
  wireSeg(diceSubTabs, updateDiceSubUI);
  updateDiceSubUI(segValue(diceSubTabs) || 'die');

  function rollDice() {
    const sub = segValue(diceSubTabs) || 'die';
    let count = parseIntSafe(diceCountEl.value, sub === 'die' ? 5 : 10);
    if (count < 1) count = 1;
    if (count > 50) { count = 50; diceCountEl.value = '50'; window.toast('จำกัดสูงสุด 50 ต่อครั้ง', 'err'); }

    diceEmptyEl.classList.add('hidden');
    diceResultsEl.classList.remove('hidden');
    diceResultsEl.innerHTML = '';

    if (sub === 'die') {
      let sum = 0;
      for (let i = 0; i < count; i++) {
        const v = randomInt(1, 6);
        sum += v;
        diceResultsEl.appendChild(renderDieFace(v));
      }
      diceSumEl.classList.remove('hidden');
      diceSumEl.textContent = 'ผลรวม: ' + sum;
      window.toast('ทอยลูกเต๋า ' + count + ' ลูกแล้ว', 'ok');
    } else {
      let heads = 0;
      for (let i = 0; i < count; i++) {
        const isHeads = randomInt(0, 1) === 0;
        if (isHeads) heads++;
        diceResultsEl.appendChild(renderCoinFace(isHeads));
      }
      diceSumEl.classList.remove('hidden');
      diceSumEl.textContent = 'หัว ' + heads + ' ครั้ง · ก้อย ' + (count - heads) + ' ครั้ง';
      window.toast('โยนเหรียญ ' + count + ' ครั้งแล้ว', 'ok');
    }
  }
  diceRollBtn.onclick = rollDice;
})();
