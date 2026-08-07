/* อ่านจำนวนเงินเป็นตัวอักษรไทย (บาทถ้วน) — ทำงานฝั่งเบราว์เซอร์ล้วน ไม่มีการส่งข้อมูลขึ้นเซิร์ฟเวอร์
 *
 * หลักการอ่านตัวเลขภาษาไทย (แบบเดียวกับ Excel BAHTTEXT / ราชบัณฑิตยสถาน):
 *  - หลักหน่วย: ๐-๙ อ่านตรงตัว (ศูนย์ หนึ่ง สอง ... เก้า)
 *  - หลักสิบ: เลข 1 อ่าน "สิบ" (ไม่ใช่ "หนึ่งสิบ"), เลข 2 อ่าน "ยี่สิบ" (ไม่ใช่ "สองสิบ"),
 *    เลข 3-9 อ่าน "สามสิบ".."เก้าสิบ" ตามปกติ
 *  - หลักหน่วยที่เป็นเลข 1 และมีหลักอื่นอยู่ข้างหน้า (ในกลุ่มหลักล้านเดียวกัน) → อ่าน "เอ็ด"
 *    (เช่น 11→สิบเอ็ด, 21→ยี่สิบเอ็ด, 101→หนึ่งร้อยเอ็ด, 1,000,001→...เอ็ด) ส่วนเลข 1 โดดๆ อ่าน "หนึ่ง"
 *  - ตัวเลขตั้งแต่ 7 หลักขึ้นไป ใช้หลัก "ล้าน" ซ้ำ (สิบล้าน, หนึ่งล้านล้าน ฯลฯ) — ทำโดย
 *    แบ่งเลขเป็นกลุ่มละ 6 หลักจากขวา แล้วอ่านซ้ำแบบ recursive คั่นด้วย "ล้าน"
 *
 * การเลือกกรณีพิเศษ (ระบุไว้ตามที่ผู้ใช้เห็นผลจริง):
 *  - 0 ถ้วน → "ศูนย์บาทถ้วน"
 *  - มีสตางค์แต่บาทเป็นศูนย์ (เช่น 0.25) → ใช้แบบ Excel BAHTTEXT คือ "ศูนย์บาทยี่สิบห้าสตางค์"
 *    (ไม่ตัด "ศูนย์บาท" ออก แม้บางที่จะอ่านแบบตัดคำนี้ทิ้งก็ได้)
 *  - จำนวนติดลบ → เติมคำว่า "ลบ" ไว้หน้าคำอ่านทั้งหมด
 *  - ปัดทศนิยมเหลือ 2 ตำแหน่ง (สตางค์) ด้วยการปัดเลขแบบมาตรฐาน (toFixed) ก่อนอ่าน
 */
(function () {
  'use strict';

  function byId(id) { return document.getElementById(id); }

  /* =========================================================
   * แกนกลาง: แปลงสตริงตัวเลข (จำนวนเต็มบวก, ไม่มีเครื่องหมาย) เป็นคำอ่านภาษาไทย
   * ========================================================= */
  var THAI_DIGIT = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
  // ตำแหน่งหลักภายในกลุ่ม 6 หลัก นับจากขวา: [หน่วย, สิบ, ร้อย, พัน, หมื่น, แสน]
  var THAI_PLACE = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];

  /**
   * อ่านสตริงตัวเลข (เฉพาะหลัก 0-9, ไม่มีเครื่องหมายลบ/จุดทศนิยม) เป็นคำอ่านภาษาไทย
   * รองรับความยาวเท่าใดก็ได้ — เกิน 6 หลักจะถูกแบ่งเป็นกลุ่มละ 6 หลักจากขวา
   * แล้วอ่านแบบ recursive คั่นด้วย "ล้าน" (ทำให้รองรับ "ล้านล้าน" สำหรับเลขใหญ่มากได้ด้วย)
   */
  function readDigits(numStr) {
    if (/^0+$/.test(numStr)) return ''; // ทั้งกลุ่มเป็นศูนย์ล้วน -> ไม่มีคำอ่าน (ไม่นับเป็น "ศูนย์" ตรงนี้)

    if (numStr.length > 6) {
      var splitAt = numStr.length - 6;
      var left = numStr.slice(0, splitAt);
      var right = numStr.slice(splitAt);
      return readDigits(left) + 'ล้าน' + readDigits(right);
    }

    var len = numStr.length;
    var out = '';
    for (var i = 0; i < len; i++) {
      var d = numStr.charCodeAt(i) - 48; // '0'.charCodeAt(0) === 48
      if (d === 0) continue;
      var posFromRight = len - i; // 1 = หน่วย, 2 = สิบ, 3 = ร้อย, ...

      if (posFromRight === 2 && d === 1) {
        out += 'สิบ';
      } else if (posFromRight === 2 && d === 2) {
        out += 'ยี่สิบ';
      } else if (posFromRight === 1 && d === 1 && len > 1) {
        out += 'เอ็ด';
      } else {
        out += THAI_DIGIT[d] + (THAI_PLACE[posFromRight - 1] || '');
      }
    }
    return out;
  }

  /**
   * อ่านสตริงตัวเลข (จำนวนเต็ม, ไม่ติดลบ) เป็นคำอ่านเต็ม — คืน "ศูนย์" ถ้าค่าคือ 0
   * (readDigits() ล้วนๆ จะคืนสตริงว่างสำหรับ "0" เพราะมันคิดในเชิง "หลักนี้ไม่ต้องอ่าน"
   *  ฟังก์ชันนี้ห่อไว้อีกชั้นเพื่อให้ใช้เป็นคำอ่านของเลขเดี่ยวๆ ได้ตรงไปตรงมา)
   */
  function readInteger(numStr) {
    if (/^0+$/.test(numStr)) return 'ศูนย์';
    return readDigits(numStr);
  }

  /* =========================================================
   * แปลง input ผู้ใช้ -> { ok, text } หรือ { ok:false, reason }
   * ========================================================= */
  var MAX_SAFE = Number.MAX_SAFE_INTEGER; // ~9 x 10^15 — เกินกว่านี้ความแม่นยำของ float พังจนไม่ควรอ่านต่อ

  function parseAmount(raw) {
    var s = String(raw || '').trim().replace(/,/g, '');
    if (s === '' || s === '-') return { ok: false, reason: 'empty' };
    if (!/^-?\d+(\.\d+)?$/.test(s)) return { ok: false, reason: 'invalid' };

    var num = parseFloat(s);
    if (!isFinite(num)) return { ok: false, reason: 'invalid' };
    if (Math.abs(num) > MAX_SAFE) return { ok: false, reason: 'toolarge' };

    return { ok: true, value: num };
  }

  function bahtText(raw) {
    var parsed = parseAmount(raw);
    if (!parsed.ok) return parsed;

    var negative = parsed.value < 0;
    var abs = Math.abs(parsed.value);

    // ปัดทศนิยมเหลือ 2 ตำแหน่ง (สตางค์) ด้วยการปัดเลขแบบมาตรฐาน
    var fixed = abs.toFixed(2); // เช่น "1250.50"
    var dot = fixed.indexOf('.');
    var bahtIntStr = fixed.slice(0, dot);
    var satang = parseInt(fixed.slice(dot + 1), 10);

    var bahtPart = readInteger(bahtIntStr);
    var out = bahtPart + 'บาท';
    if (satang === 0) {
      out += 'ถ้วน';
    } else {
      // สตางค์อ่านเป็นเลข 1-99 อิสระ (ไม่เติมเลขศูนย์นำหน้า) — เลข 1 โดดๆ จึงอ่าน "หนึ่ง" ไม่ใช่ "เอ็ด"
      out += readDigits(String(satang)) + 'สตางค์';
    }
    if (negative) out = 'ลบ' + out;

    return { ok: true, text: out, bahtInt: bahtIntStr, satang: satang, negative: negative };
  }

  /* expose for manual console verification / automated test scripts */
  window.__bahttextInternals = { readDigits: readDigits, readInteger: readInteger, bahtText: bahtText, parseAmount: parseAmount };

  /* =========================================================
   * DOM wiring — instant live update + copy button
   * ========================================================= */
  var inputEl = byId('bt-input');
  var outputEl = byId('bt-output');
  var detailEl = byId('bt-detail');
  var copyBtn = byId('bt-copy-btn');
  var examplesEl = byId('bt-examples');

  var lastText = '';

  function render() {
    var raw = inputEl.value;
    if (String(raw || '').trim() === '') {
      outputEl.textContent = '—';
      outputEl.classList.add('empty');
      detailEl.textContent = 'พิมพ์จำนวนเงินด้านซ้ายเพื่อดูคำอ่าน';
      lastText = '';
      return;
    }

    var result = bahtText(raw);
    if (!result.ok) {
      outputEl.textContent = '—';
      outputEl.classList.add('empty');
      lastText = '';
      if (result.reason === 'toolarge') {
        detailEl.textContent = 'ตัวเลขมีขนาดใหญ่เกินกว่าจะอ่านได้อย่างแม่นยำ';
      } else {
        detailEl.textContent = 'กรอกตัวเลขให้ถูกต้อง เช่น 1250.50 หรือ -21';
      }
      return;
    }

    outputEl.classList.remove('empty');
    outputEl.textContent = result.text;
    lastText = result.text;
    detailEl.textContent = 'จำนวนเงิน ' + Number(inputEl.value.replace(/,/g, '')).toLocaleString('th-TH', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' บาท';
  }

  inputEl.addEventListener('input', render);

  if (examplesEl) {
    examplesEl.querySelectorAll('button').forEach(function (b) {
      b.onclick = function () {
        inputEl.value = b.dataset.v;
        render();
        inputEl.focus();
      };
    });
  }

  copyBtn.onclick = function () {
    if (!lastText) { window.toast('ยังไม่มีคำอ่านให้คัดลอก', 'err'); return; }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(lastText).then(function () {
        window.toast('คัดลอกคำอ่านแล้ว', 'ok');
      }).catch(function () {
        window.toast('คัดลอกไม่สำเร็จ', 'err');
      });
    } else {
      window.toast('เบราว์เซอร์นี้ไม่รองรับการคัดลอกอัตโนมัติ', 'err');
    }
  };

  render();
})();
