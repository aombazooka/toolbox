/* ชุดเครื่องมือข้อความ: นับคำ/ตัวอักษร · แปลงตัวพิมพ์ · ลบ/จัดระเบียบ · เรียงบรรทัด ·
 * แปลงเลขไทย↔อารบิก · ตรวจเลขบัตรประชาชน — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์
 */
(function () {
  'use strict';

  function byId(id) { return document.getElementById(id); }

  /* =========================================================
   * PURE LOGIC FUNCTIONS (no DOM access) — kept isolated at the top
   * so they can be copy-tested standalone (see testing notes for T14).
   * ========================================================= */

  /** Unicode-code-point-aware length (spread iterates by code point, not UTF-16 unit,
   *  so surrogate pairs stay intact — Thai combining vowels/tone marks are still
   *  separate code points and are counted individually, which is the documented
   *  behaviour for the "ตัวอักษร" counters). */
  function charCount(str) { return Array.from(str).length; }
  function charCountNoSpace(str) { return Array.from(str.replace(/\s/g, '')).length; }

  /** Word count = whitespace-separated token count. Works well for English/space-
   *  delimited text; Thai has no inter-word spacing so this under-counts Thai prose
   *  (see the on-page hint) — the character count is the meaningful metric there. */
  function wordCount(str) {
    var t = str.trim();
    if (!t) return 0;
    return t.split(/\s+/).length;
  }

  function lineCount(str) {
    if (str === '') return 0;
    return str.split(/\r\n|\r|\n/).length;
  }

  /** Paragraphs = blocks of text separated by one-or-more blank lines. */
  function paragraphCount(str) {
    var blocks = str.split(/(?:\r\n|\r|\n){2,}/)
      .map(function (b) { return b.trim(); })
      .filter(function (b) { return b.length > 0; });
    return blocks.length;
  }

  function splitLines(str) { return str.split(/\r\n|\r|\n/); }

  /* ---------- case conversions ---------- */
  function toUpperCase(str) { return str.toUpperCase(); }
  function toLowerCase(str) { return str.toLowerCase(); }

  function toTitleCase(str) {
    return str.replace(/\S+/g, function (w) {
      var chars = Array.from(w);
      if (chars.length === 0) return w;
      return chars[0].toUpperCase() + chars.slice(1).join('').toLowerCase();
    });
  }

  function toSentenceCase(str) {
    var lower = str.toLowerCase();
    // Uppercase: the first word character at the very start of the string,
    // and the first word character following a sentence terminator (. ! ?)
    // plus whitespace. \w only matches Latin letters/digits — Thai characters
    // pass through toUpperCase()/toLowerCase() unchanged (Thai has no case),
    // so this only visibly affects Latin-script sentences, as intended.
    return lower
      .replace(/(^\s*)([a-z])/, function (m, pre, c) { return pre + c.toUpperCase(); })
      .replace(/([.!?]+\s+)([a-z])/g, function (m, pre, c) { return pre + c.toUpperCase(); });
  }

  function toToggleCase(str) {
    return Array.from(str).map(function (c) {
      var up = c.toUpperCase(), low = c.toLowerCase();
      if (up === low) return c; // no case concept (e.g. Thai, digits, symbols) — unchanged
      return c === up ? low : up;
    }).join('');
  }

  /* ---------- clean / organize ---------- */
  function removeDuplicateLines(str) {
    var seen = Object.create(null);
    var out = [];
    splitLines(str).forEach(function (line) {
      if (!Object.prototype.hasOwnProperty.call(seen, line)) {
        seen[line] = true;
        out.push(line);
      }
    });
    return out.join('\n');
  }

  function removeEmptyLines(str) {
    return splitLines(str).filter(function (l) { return l.trim() !== ''; }).join('\n');
  }

  function trimEachLine(str) {
    return splitLines(str).map(function (l) { return l.trim(); }).join('\n');
  }

  /** Collapses runs of 2+ spaces/tabs into a single space (newlines untouched). */
  function collapseSpaces(str) {
    return str.replace(/[ \t]{2,}/g, ' ');
  }

  /** Removes all space/tab characters (newlines untouched, so line structure survives). */
  function removeAllSpaces(str) {
    return str.replace(/[ \t]+/g, '');
  }

  /* ---------- sort lines ---------- */
  function dedupeLines(lines) {
    var seen = Object.create(null);
    var out = [];
    lines.forEach(function (l) {
      if (!Object.prototype.hasOwnProperty.call(seen, l)) { seen[l] = true; out.push(l); }
    });
    return out;
  }
  function sortLinesAsc(lines) { return lines.slice().sort(function (a, b) { return a.localeCompare(b, 'th'); }); }
  function sortLinesDesc(lines) { return lines.slice().sort(function (a, b) { return b.localeCompare(a, 'th'); }); }
  function reverseLines(lines) { return lines.slice().reverse(); }

  /* ---------- unbiased crypto shuffle (same rejection-sampling pattern as random.js) ---------- */
  function randomInt(min, max) {
    var range = max - min + 1;
    if (range <= 1) return min;
    var MAX_UINT32 = 0x100000000;
    var limit = Math.floor(MAX_UINT32 / range) * range;
    var buf = new Uint32Array(1);
    var x;
    do {
      crypto.getRandomValues(buf);
      x = buf[0];
    } while (x >= limit);
    return min + (x % range);
  }
  function shuffleLines(lines) {
    var arr = lines.slice();
    for (var i = arr.length - 1; i > 0; i--) {
      var j = randomInt(0, i);
      var t = arr[i]; arr[i] = arr[j]; arr[j] = t;
    }
    return arr;
  }

  /* ---------- Thai <-> Arabic numerals ---------- */
  var THAI_DIGITS = ['๐', '๑', '๒', '๓', '๔', '๕', '๖', '๗', '๘', '๙'];
  var ARABIC_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

  function thaiToArabicDigits(str) {
    return str.replace(/[๐-๙]/g, function (c) {
      var idx = THAI_DIGITS.indexOf(c);
      return idx === -1 ? c : ARABIC_DIGITS[idx];
    });
  }
  function arabicToThaiDigits(str) {
    return str.replace(/[0-9]/g, function (c) {
      var idx = ARABIC_DIGITS.indexOf(c);
      return idx === -1 ? c : THAI_DIGITS[idx];
    });
  }

  /* ---------- Thai national ID checksum ----------
   * check digit = (11 − (sum(digit[i] * (13 − i)) for i=0..11) mod 11) mod 10
   * valid iff it equals digit[12].
   */
  function validateThaiNationalId(idStr) {
    var digitsOnly = String(idStr || '').replace(/\D/g, '');
    if (digitsOnly.length !== 13) {
      return { status: 'incomplete', digitsEntered: digitsOnly.length };
    }
    var d = digitsOnly.split('').map(Number);
    var sum = 0;
    for (var i = 0; i < 12; i++) sum += d[i] * (13 - i);
    var expected = (11 - (sum % 11)) % 10;
    return { status: expected === d[12] ? 'valid' : 'invalid', expected: expected, actual: d[12] };
  }

  /* expose for manual console verification if needed (same pattern as random.js) */
  window.__ttInternals = {
    charCount: charCount, charCountNoSpace: charCountNoSpace, wordCount: wordCount,
    lineCount: lineCount, paragraphCount: paragraphCount,
    toUpperCase: toUpperCase, toLowerCase: toLowerCase, toTitleCase: toTitleCase,
    toSentenceCase: toSentenceCase, toToggleCase: toToggleCase,
    removeDuplicateLines: removeDuplicateLines, removeEmptyLines: removeEmptyLines,
    trimEachLine: trimEachLine, collapseSpaces: collapseSpaces, removeAllSpaces: removeAllSpaces,
    sortLinesAsc: sortLinesAsc, sortLinesDesc: sortLinesDesc, reverseLines: reverseLines,
    shuffleLines: shuffleLines, thaiToArabicDigits: thaiToArabicDigits, arabicToThaiDigits: arabicToThaiDigits,
    validateThaiNationalId: validateThaiNationalId,
  };

  /* =========================================================
   * DOM WIRING
   * ========================================================= */
  var textEl = byId('tt-text');
  var undoBtn = byId('tt-undo-btn');
  var copyBtn = byId('tt-copy-btn');
  var clearBtn = byId('tt-clear-btn');

  var statChars = byId('tt-count-chars');
  var statCharsNoSpace = byId('tt-count-chars-nospace');
  var statWords = byId('tt-count-words');
  var statLines = byId('tt-count-lines');
  var statParas = byId('tt-count-paras');

  var undoStack = [];
  var UNDO_LIMIT = 50;

  function fmtNum(n) { return n.toLocaleString('th-TH'); }

  function renderStats() {
    var v = textEl.value;
    statChars.textContent = fmtNum(charCount(v));
    statCharsNoSpace.textContent = fmtNum(charCountNoSpace(v));
    statWords.textContent = fmtNum(wordCount(v));
    statLines.textContent = fmtNum(lineCount(v));
    statParas.textContent = fmtNum(paragraphCount(v));
  }

  function updateUndoBtn() {
    undoBtn.disabled = undoStack.length === 0;
  }

  /** Applies a transform result to the textarea, pushing the previous value onto
   *  the undo stack first (unless the text didn't actually change). */
  function applyTransform(newText, okMsg) {
    var current = textEl.value;
    if (newText === current) {
      window.toast('ข้อความไม่เปลี่ยนแปลง', 'err');
      return;
    }
    undoStack.push(current);
    if (undoStack.length > UNDO_LIMIT) undoStack.shift();
    textEl.value = newText;
    updateUndoBtn();
    renderStats();
    renderIdCheck(); // ID field is independent, but keep in sync just in case
    if (okMsg) window.toast(okMsg, 'ok');
  }

  undoBtn.onclick = function () {
    if (undoStack.length === 0) return;
    textEl.value = undoStack.pop();
    updateUndoBtn();
    renderStats();
    window.toast('ย้อนกลับแล้ว', 'ok');
  };

  copyBtn.onclick = function () {
    var v = textEl.value;
    if (!v) { window.toast('ไม่มีข้อความให้คัดลอก', 'err'); return; }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(v).then(function () {
        window.toast('คัดลอกข้อความแล้ว', 'ok');
      }).catch(function () {
        window.toast('คัดลอกไม่สำเร็จ', 'err');
      });
    } else {
      window.toast('เบราว์เซอร์นี้ไม่รองรับการคัดลอกอัตโนมัติ', 'err');
    }
  };

  clearBtn.onclick = function () {
    if (!textEl.value) return;
    applyTransform('', 'ล้างข้อความแล้ว');
  };

  textEl.addEventListener('input', renderStats);

  /* ---------- case buttons ---------- */
  var CASE_FN = {
    upper: toUpperCase, lower: toLowerCase, title: toTitleCase,
    sentence: toSentenceCase, toggle: toToggleCase,
  };
  var CASE_LABEL = {
    upper: 'แปลงเป็นตัวพิมพ์ใหญ่แล้ว', lower: 'แปลงเป็นตัวพิมพ์เล็กแล้ว', title: 'แปลงเป็น Title Case แล้ว',
    sentence: 'แปลงเป็น Sentence case แล้ว', toggle: 'สลับตัวพิมพ์แล้ว',
  };
  Array.prototype.forEach.call(document.querySelectorAll('[data-case]'), function (btn) {
    btn.onclick = function () {
      var key = btn.dataset.case;
      if (!textEl.value) { window.toast('กรอกข้อความก่อน', 'err'); return; }
      applyTransform(CASE_FN[key](textEl.value), CASE_LABEL[key]);
    };
  });

  /* ---------- clean/organize buttons ---------- */
  var CLEAN_FN = {
    dedupe: removeDuplicateLines, empty: removeEmptyLines, trim: trimEachLine,
    collapse: collapseSpaces, nospace: removeAllSpaces,
  };
  var CLEAN_LABEL = {
    dedupe: 'ลบบรรทัดซ้ำแล้ว', empty: 'ลบบรรทัดว่างแล้ว', trim: 'ตัดช่องว่างหัว-ท้ายแต่ละบรรทัดแล้ว',
    collapse: 'ยุบช่องว่างซ้ำแล้ว', nospace: 'ลบช่องว่างทั้งหมดแล้ว',
  };
  Array.prototype.forEach.call(document.querySelectorAll('[data-clean]'), function (btn) {
    btn.onclick = function () {
      var key = btn.dataset.clean;
      if (!textEl.value) { window.toast('กรอกข้อความก่อน', 'err'); return; }
      applyTransform(CLEAN_FN[key](textEl.value), CLEAN_LABEL[key]);
    };
  });

  /* ---------- sort buttons ---------- */
  var sortDedupeToggle = byId('tt-sort-dedupe-toggle');
  sortDedupeToggle.onclick = function () { sortDedupeToggle.classList.toggle('on'); };

  var SORT_FN = { asc: sortLinesAsc, desc: sortLinesDesc, reverse: reverseLines, shuffle: shuffleLines };
  var SORT_LABEL = {
    asc: 'เรียงบรรทัด ก→ฮ / A→Z แล้ว', desc: 'เรียงบรรทัด ฮ→ก / Z→A แล้ว',
    reverse: 'กลับด้านลำดับบรรทัดแล้ว', shuffle: 'สุ่มลำดับบรรทัดแล้ว',
  };
  Array.prototype.forEach.call(document.querySelectorAll('[data-sort]'), function (btn) {
    btn.onclick = function () {
      var key = btn.dataset.sort;
      if (!textEl.value) { window.toast('กรอกข้อความก่อน', 'err'); return; }
      var lines = splitLines(textEl.value);
      if (sortDedupeToggle.classList.contains('on')) lines = dedupeLines(lines);
      applyTransform(SORT_FN[key](lines).join('\n'), SORT_LABEL[key]);
    };
  });

  /* ---------- Thai/Arabic numeral buttons ---------- */
  var NUM_FN = { th2ar: thaiToArabicDigits, ar2th: arabicToThaiDigits };
  var NUM_LABEL = { th2ar: 'แปลงเลขไทยเป็นอารบิกแล้ว', ar2th: 'แปลงเลขอารบิกเป็นไทยแล้ว' };
  Array.prototype.forEach.call(document.querySelectorAll('[data-num]'), function (btn) {
    btn.onclick = function () {
      var key = btn.dataset.num;
      if (!textEl.value) { window.toast('กรอกข้อความก่อน', 'err'); return; }
      applyTransform(NUM_FN[key](textEl.value), NUM_LABEL[key]);
    };
  });

  /* ---------- Thai national ID checker ---------- */
  var idInput = byId('tt-id-input');
  var idResultEl = byId('tt-id-result');

  function renderIdCheck() {
    var raw = idInput.value;
    var digitsOnly = raw.replace(/\D/g, '').slice(0, 13);
    if (digitsOnly !== raw) idInput.value = digitsOnly;

    var result = validateThaiNationalId(digitsOnly);
    idResultEl.classList.remove('empty');
    if (result.status === 'incomplete') {
      if (result.digitsEntered === 0) {
        idResultEl.textContent = 'กรอกเลข 13 หลักด้านบน';
        idResultEl.classList.add('empty');
      } else {
        idResultEl.textContent = 'กรอกแล้ว ' + result.digitsEntered + '/13 หลัก';
        idResultEl.style.color = '';
      }
    } else if (result.status === 'valid') {
      idResultEl.textContent = '✓ ถูกต้อง — เลขบัตรประชาชนนี้ผ่านการตรวจสอบ checksum';
      idResultEl.style.color = 'var(--ok)';
    } else {
      idResultEl.textContent = '✗ ไม่ถูกต้อง — หลักตรวจสอบ (หลักที่ 13) ที่ถูกต้องคือ ' + result.expected;
      idResultEl.style.color = 'var(--danger)';
    }
  }
  idInput.addEventListener('input', renderIdCheck);

  /* ---------- init ---------- */
  renderStats();
  renderIdCheck();
  updateUndoBtn();
})();
