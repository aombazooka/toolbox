/* ============================================================
 *  build-static.js — สร้างเว็บ "Toolbox" เวอร์ชัน static ล้วน (ไม่มี DB/PHP)
 *
 *  วิธีทำงาน: ดึง HTML ที่ PHP render จริง (แบบ guest ไม่ล็อกอิน) ผ่าน HTTP
 *  จาก http://localhost/qrcode/<page> แล้ว post-process ให้เป็นไฟล์ static
 *  เขียนลงโฟลเดอร์ docs/ (GitHub Pages เสิร์ฟจาก /docs ได้)
 *
 *  ต้องรัน XAMPP (Apache + MySQL) อยู่ก่อน:  node build-static.js
 *  เครื่องมือที่ตัดออก: ย่อลิงก์ (ต้องมี backend) — เก็บเฉพาะ client-side ล้วน
 * ============================================================ */
const fs = require('fs');
const path = require('path');

const BASE = 'http://localhost/qrcode/';
const OUT = path.join(__dirname, 'docs');

// เครื่องมือ client-side ล้วนที่จะเก็บไว้ (ตัด shorten/บัญชี/ประวัติ ออก)
const PAGES = [
  'index', 'qr', 'qr-presets', 'promptpay', 'image', 'pdf',
  'calc-loan', 'calc', 'random', 'password', 'date-tools',
  'convert', 'bahttext', 'tax', 'text-tools',
];
// slug ที่ลิงก์ .php ต้องแปลงเป็น .html
const LINKABLE = new Set(PAGES);

function convertLinks(html) {
  // href="X.php" -> href="X.html" เฉพาะ slug ที่เราเก็บไว้
  return html.replace(/href="([a-z0-9-]+)\.php(\?[^"]*)?"/g, (m, slug, q) => {
    if (LINKABLE.has(slug)) return 'href="' + slug + '.html"' + '';
    return m; // slug อื่น (login/shorten/...) จัดการแยกด้านล่าง
  });
}

function stripDbBits(html) {
  // 1) ปุ่ม "เข้าสู่ระบบ" ในหัวเว็บ
  html = html.replace(/<a class="btn btn-primary" href="login\.php"[^>]*>[\s\S]*?<\/a>/g, '');
  // 2) ลิงก์เครื่องมือ "ย่อลิงก์" (shorten) ทั้งใน dropdown และการ์ดบน Hub
  html = html.replace(/<a [^>]*href="shorten\.php"[^>]*>[\s\S]*?<\/a>/g, '');
  // 3) window.APP: qr ต้องคง isGuest:true, edit:null (กัน create.js เข้าใจว่าเป็นผู้ใช้ล็อกอิน
  //    แล้วไปเรียก api/save.php) ; หน้าอื่นไม่มี isGuest -> ตั้งเป็น {} ด้วย negative lookahead
  //    เพื่อไม่ให้ไปทับบรรทัด qr ที่เพิ่งแทนไป
  html = html.replace(/window\.APP = \{[^}]*isGuest[^}]*\};/g,
    'window.APP = { baseUrl: "", csrf: "", isGuest: true, edit: null };');
  html = html.replace(/window\.APP = \{(?![^}]*isGuest)[^}]*\};/g, 'window.APP = {};');
  return html;
}

async function fetchPage(name) {
  const url = BASE + (name === 'index' ? '' : name + '.php');
  const res = await fetch(url, { headers: { 'Cache-Control': 'no-cache' } });
  if (!res.ok) throw new Error('fetch ' + url + ' -> HTTP ' + res.status);
  return await res.text();
}

function buildStaticConvertJs(src) {
  // เปลี่ยนจากเรียก proxy api/rates.php -> ดึงตรงจาก frankfurter.app (รองรับ CORS)
  // ใช้ frankfurter.dev (endpoint นี้ส่ง CORS header ถูกต้อง เรียกตรงจากเบราว์เซอร์ได้
  //  ต่างจาก frankfurter.app ที่ 301 redirect แล้ว CORS ล้ม) · param คือ base= ไม่ใช่ from=
  src = src.replace(
    "      const res = await fetch('api/rates.php');\n      const data = await res.json();",
    "      const res = await fetch('https://api.frankfurter.dev/v1/latest?base=THB');\n" +
    "      const raw = await res.json();\n" +
    "      const data = (raw && raw.rates)\n" +
    "        ? { ok: true, base: raw.base || 'THB', date: raw.date, rates: raw.rates, cached: false, fetched_at: '', stale: false }\n" +
    "        : { ok: false };"
  );
  // ข้อความสถานะ/โน้ต: ตัดคำที่พูดถึง "ตัวกลางฝั่งเซิร์ฟเวอร์/แคช 1 วัน" ออก
  src = src.replace(
    /catNoteEl\.textContent = 'ดึงอัตราแลกเปลี่ยน[^']*';/,
    "catNoteEl.textContent = 'ดึงอัตราแลกเปลี่ยนล่าสุดตรงจาก frankfurter.dev — ใช้เพื่อการอ้างอิงเท่านั้น ไม่ใช่อัตราซื้อขายจริงจากธนาคาร/ร้านแลกเงิน';"
  );
  src = src.replace(
    /let msg = 'อัตราอ้างอิงวันที่ ' \+ data\.date[^;]*;/,
    "let msg = 'อัตราอ้างอิงล่าสุดวันที่ ' + data.date + ' (ดึงสดจาก frankfurter.dev)';"
  );
  return src;
}

async function main() {
  // เตรียมโฟลเดอร์ docs/
  fs.rmSync(OUT, { recursive: true, force: true });
  fs.mkdirSync(OUT, { recursive: true });

  // คัดลอก assets ทั้งหมด (css/js/vendor)
  fs.cpSync(path.join(__dirname, 'assets'), path.join(OUT, 'assets'), { recursive: true });

  // ทำ convert.js เวอร์ชัน static ทับไฟล์ที่คัดลอกมา
  const convSrc = fs.readFileSync(path.join(__dirname, 'assets/js/convert.js'), 'utf8');
  fs.writeFileSync(path.join(OUT, 'assets/js/convert.js'), buildStaticConvertJs(convSrc));

  // ลบ JS/vendor ของฟีเจอร์ที่ตัดออก (บัญชี/ประวัติ/ย่อลิงก์/นำเข้า Excel) — ไม่มีหน้า static ไหนโหลด
  ['analytics.js', 'bulk.js', 'history.js', 'print.js', 'settings.js', 'shorten.js', 'users.js']
    .forEach(f => fs.rmSync(path.join(OUT, 'assets/js', f), { force: true }));
  fs.rmSync(path.join(OUT, 'assets/vendor/xlsx.full.min.js'), { force: true }); // ใช้เฉพาะนำเข้า Excel (ตัดออก)

  // .nojekyll เพื่อไม่ให้ GitHub Pages รัน Jekyll (กันไฟล์/โฟลเดอร์บางชื่อโดนซ่อน)
  fs.writeFileSync(path.join(OUT, '.nojekyll'), '');

  const leftover = [];
  for (const name of PAGES) {
    let html = await fetchPage(name);
    html = stripDbBits(html);
    html = convertLinks(html);
    // ตรวจลิงก์ .php ที่ยังหลงเหลือ (ควรไม่มี)
    const rem = [...html.matchAll(/href="([^"]*\.php[^"]*)"/g)].map(m => m[1]);
    if (rem.length) leftover.push(name + ': ' + [...new Set(rem)].join(', '));
    fs.writeFileSync(path.join(OUT, name + '.html'), html);
    console.log('  ✓ ' + name + '.html');
  }

  console.log('\nเสร็จสิ้น — เขียน ' + PAGES.length + ' หน้า + assets ลง docs/');
  if (leftover.length) {
    console.log('\n⚠ ยังพบลิงก์ .php หลงเหลือ (ต้องตรวจ):');
    leftover.forEach(l => console.log('   ' + l));
  } else {
    console.log('✓ ไม่มีลิงก์ .php หลงเหลือ');
  }
}

main().catch(e => { console.error('BUILD FAILED:', e.message); process.exit(1); });
