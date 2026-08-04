# TOOLBOX-PLAN — ยกระดับ QR Studio เป็นเว็บ "เครื่องมือออนไลน์" สาธารณะ + 10 เครื่องมือใหม่

> แผนนี้เขียนไว้ให้ **session/subagent ใหม่** หยิบไปสร้างได้ทันที (1 งาน = 1 subagent, เรียงลำดับ)
> อ่านหัวข้อ **0–4 ก่อนเสมอ** แล้วจึงลงมือทำงานในหัวข้อ 5

---

## 0. เป้าหมาย & การพลิกทิศ (Context)
เดิมระบบนี้ทำเพื่อโรงพยาบาล ตอนนี้ **เปลี่ยนเป็นเว็บเครื่องมือฟรีสาธารณะ ใครก็ใช้ได้** (แนวเว็บรวม tools ออนไลน์)
- **ล็อกอินเป็นทางเลือก ไม่ใช่ด่านบังคับ** — เครื่องมือเกือบทั้งหมดใช้ได้โดยไม่ต้องล็อกอิน (บัญชีมีไว้เก็บประวัติ/ของส่วนตัว)
- คงของเดิมไว้ทั้งหมด: เครื่องมือ **QR** + ระบบ auth/สมาชิก/ธีม/`includes/` + cache-busting
- **hosting/hardening ทำทีหลัง** (แผนนี้ยังรันบน XAMPP/localhost) — ดูหัวข้อ 8 สำหรับสิ่งที่ต้องทำก่อนขึ้นจริง
- งานหลัก: (ก) แปลงเป็นแพลตฟอร์ม "Toolbox" + หน้า Hub, (ข) สร้าง **10 เครื่องมือใหม่**

**ชื่อแบรนด์:** ใช้ค่ากลางๆ ก่อน (แก้ `APP_NAME` ใน `config.php` — เสนอ `"Toolbox"` หรือให้เจ้าของตั้งเอง) ตัดคำว่าโรงพยาบาลออกจากทุกหน้า

---

## 1. บริบทระบบเดิม (สิ่งที่ reuse ได้ — อย่าเขียนซ้ำ)
Stack: PHP 8.2 + MariaDB 10.4 บน XAMPP · `C:\xampp\htdocs\qrcode` · http://localhost/qrcode

**Helpers (`includes/helpers.php`):** `e()`, `json_out()`, `json_input()`, `csrf_token()`, `csrf_check()`, `thai_date()`, `generate_short_code()`, `dynamic_link()`, `default_categories()`, `guest_user_id()`, **`asset_url()`** (cache-busting — ใช้กับ CSS/JS ทุกไฟล์)
**Auth (`includes/auth.php`):** `current_user()`, `is_logged_in()`, `require_login()`, **`require_installed()`** (สำหรับหน้าสาธารณะ), `require_login_api()`, `is_admin()`, `require_admin()`, `login_user()`, `logout_user()`, `uid()`, `log_audit()`
**Shell:** `includes/header.php` (nav + ปุ่มธีม + user-chip/ปุ่มเข้าสู่ระบบสำหรับ guest), `includes/footer.php` (มี `window.toast(msg,type)` ให้ใช้ทั่วเว็บ + สคริปต์ธีม + โหลด `$page_js` ผ่าน asset_url)
**แพตเทิร์นหน้า:** `require_once includes/auth.php` → guard (`require_installed()` สำหรับสาธารณะ / `require_login()` ถ้าต้องล็อกอิน) → ตั้ง `$active,$page_title,$page_js,$csrf` → `include header.php` → เนื้อหา → `<script>window.APP={baseUrl,csrf,...}</script>` → `include footer.php`
**ตาราง:** users, categories, qrcodes, scans, settings, templates, login_attempts, audit_log
**ไลบรารีในเครื่อง (`assets/vendor/`):** `qr-code-styling.js`, `jszip.min.js`, `xlsx.full.min.js`
**ธีม:** CSS variables ส้มพาสเทล (`--accent #f4a15c`, `--accent-strong`, `--surface`, ฯลฯ) รองรับ light/dark (`data-theme` + localStorage `qrs-theme`) · คลาสพร้อมใช้: `.card/.card-pad`, `.field/.lab/.input`, `.btn/.btn-primary/.btn-ghost`, `.seg`, `.hint`, `.color-chip`, `.toast`, `.stat`, `.page-head`
**QR & ลิงก์เดิม:** `r.php?c=CODE` เด้ง dynamic QR + log ลง `scans` · ไลบรารี `qr-code-styling` วาด QR ฝั่ง client (มีโลโก้กลาง/สไตล์)

---

## 2. สถาปัตยกรรม Toolbox
- **`index.php` = หน้า Hub** (กริดการ์ดเครื่องมือ + ค้นหา/หมวด) · **ย้ายตัวสร้าง QR เดิม → `qr.php`**
- **Tool registry:** สร้าง `includes/tools.php` คืน array ของเมตาเครื่องมือ `[ ['slug','title','desc','href','icon'(svg inline/id),'cat','login'=>false] , ... ]` — Hub และ nav อ่านจากที่นี่ที่เดียว · **การเพิ่มเครื่องมือใหม่ = append 1 รายการ** (แบบ append-only ลดการชนไฟล์)
- **แต่ละเครื่องมือ = โมดูลในตัวเอง:** หน้า `<tool>.php` + (ถ้ามี backend) `api/<tool>/*.php` + (ถ้ามี) ตารางของตัวเอง · ใช้ `includes/` + ธีมร่วมกัน
- **หมวดบน Hub:** `รูปภาพ & PDF` · `QR & ลิงก์` · `เครื่องคำนวณ` · `ตัวช่วย & สุ่ม`
- **nav (header):** โลโก้ → Hub · เมนู "เครื่องมือ ▾" (dropdown จาก registry) หรือปุ่ม "ทั้งหมด" → Hub (11 เครื่องมือเยอะเกินใส่ tab ตรงๆ) · คงปุ่มธีม + user-chip/เข้าสู่ระบบ
- **ล็อกอิน:** เครื่องมือทั้งหมด `require_installed()` (สาธารณะ) ยกเว้นหน้าที่เป็นข้อมูลส่วนตัว (history/analytics/settings/admin คงเดิม)
- **Privacy (สำคัญ):** **เลิก guest auto-save ในเครื่องมือ QR** — guest แค่สร้าง+ดาวน์โหลด, เก็บประวัติเฉพาะคนล็อกอิน (แก้ `create.js` `persist()` ให้ guest ข้ามการเซฟ + เอา guest-INSERT ออกจาก `api/save.php` หรือปิดเฉพาะ QR) · เครื่องมือย่อลิงก์ (ต้องเก็บเพื่อ resolve) ให้ `user_id` เป็น NULL ได้เมื่อไม่ล็อกอิน

---

## 3. ระบบดีไซน์ & เลย์เอาต์เครื่องมือ (บังคับใช้ทุกเครื่องมือ — เพื่อความสวยงาม/สม่ำเสมอ)
เจ้าของเน้น **สวยงาม ใช้ง่าย** → ทุกเครื่องมือต้องหน้าตาเป็นชุดเดียวกัน ใช้โครงนี้:

**โครง "หน้าเครื่องมือ" มาตรฐาน**
```
[page-head]  ไอคอนวงกลม + <h1>ชื่อเครื่องมือ</h1> + <p>คำอธิบายสั้น 1 บรรทัด</p>
[grid 2 คอลัมน์ ≥900px, ซ้อนกันบนมือถือ]
  ├─ การ์ด "อินพุต"  (.card > .card-pad): ฟอร์ม/อัปโหลด/ตัวเลือก (ใช้ .field/.input/.seg/.btn)
  └─ การ์ด "ผลลัพธ์" (.card): พรีวิว/ผลลัพธ์ + ปุ่มการกระทำหลัก (ดาวน์โหลด/คัดลอก) แบบ sticky บนจอกว้าง
```
**กติกาดีไซน์**
- Mobile-first responsive · ใช้ CSS variables + คลาสเดิม (ห้ามฮาร์ดโค้ดสีนอกธีม) · รองรับ light/dark
- ผลลัพธ์แบบ **instant** (อัปเดตทันทีที่พิมพ์/เปลี่ยนค่า เมื่อเป็นไปได้) · ปุ่มหลัก 1 ปุ่มเด่น (btn-primary)
- Feedback: ใช้ `window.toast()` ตอนคัดลอก/ดาวน์โหลด/ผิดพลาด · มี loading state ตอนประมวลผลนาน · empty state ที่เป็นมิตร
- ไอคอน: inline SVG สโตรก 2px สไตล์เดียวกับที่มีอยู่ · ทุกข้อความเป็นภาษาไทย กระชับ เข้าใจง่าย
- **ความเป็นส่วนตัวเป็นจุดขาย:** เครื่องมือ client-side (รูป/PDF/คำนวณ/สุ่ม/รหัสผ่าน) **ประมวลผลในเบราว์เซอร์ ไม่อัปไฟล์ขึ้นเซิร์ฟเวอร์** — ใส่ป้าย/ข้อความบอกผู้ใช้ ("ไฟล์ไม่ถูกอัปโหลด ประมวลผลในเครื่องคุณ")
**Hub (index.php)**
- กริดการ์ดเครื่องมือ (ไอคอน + ชื่อ + คำอธิบายสั้น) จัดกลุ่มตามหมวด · มีช่องค้นหา (filter ฝั่ง client) · การ์ด hover ยกเงา · responsive (auto-fill minmax ~220px)
- Hero บนสุด: ชื่อเว็บ + สโลแกนสั้น ("เครื่องมือออนไลน์ ฟรี ใช้ง่าย ไม่ต้องล็อกอิน")

---

## 4. กติกา / Definition of Done (ทุกงาน)
1. **หน้าเครื่องมือสาธารณะ:** `require_installed()` (ไม่ใช่ require_login) · ตามแพตเทิร์นหน้า (header/footer, `window.APP`, `$page_js` โหลดผ่าน asset_url อัตโนมัติจาก footer)
2. **เครื่องมือ client-side ล้วน:** ไม่ต้องมี API/CSRF/ตาราง — ทำงานจบในเบราว์เซอร์
3. **เครื่องมือที่มี backend (เก็บข้อมูล):** `api/<tool>/*.php` ใช้ `json_out`/`json_input`, **CSRF ทุก POST**, และ **บันทึกข้อควรทำ rate-limit** (ทำจริงในเฟส hardening) · แก้ schema = อัปเดต `schema.sql` (idempotent) + เพิ่ม migration ใน `migrate.php` (append-only, ใช้ `table_exists()`)
4. **ไลบรารีภายนอกทั้งหมดต้องเก็บในเครื่อง** (`assets/vendor/`) — ห้ามพึ่ง CDN ตอนรัน (ดูรายการหัวข้อ 9)
5. **ลงทะเบียนเครื่องมือ:** append 1 รายการใน `includes/tools.php` (Hub/nav จะแสดงให้เอง)
6. UI ตามหัวข้อ 3 · ภาษาไทย · ธีม light/dark · responsive
7. **ทดสอบก่อนปิดงาน** (หัวข้อ 7): `php -l`, `node -c`, ตรวจ DOM/พฤติกรรมจริง, ไม่มี console error · ลบไฟล์ทดสอบ
8. ห้ามแก้ `config.php` credentials · ห้ามทำพังของเดิม (QR/auth) · เครื่องมือ client-side ต้องทำงานแม้ไม่ล็อกอิน

---

## 5. รายการงาน (Tickets)

### T0 — ฐาน Toolbox (ต้องทำก่อนทุกงาน)
- **เป้าหมาย:** แปลงเป็นแพลตฟอร์มหลายเครื่องมือ + Hub + registry + privacy fix
- **ไฟล์:** `includes/tools.php` (ใหม่, registry) · `index.php` (→ Hub) · `qr.php` (ใหม่ = ย้ายเนื้อหา QR จาก index.php เดิม) · `includes/header.php` (nav ใหม่จาก registry) · `assets/js/hub.js` (ค้นหา/filter) · `config.php` (APP_NAME กลางๆ) · แก้ลิงก์ที่ชี้ index.php ในฐานะหน้า QR
- **สเปก:**
  - ย้ายทั้งหมดของ index.php (สร้าง QR) → `qr.php` (`$active='qr'`); index.php กลายเป็น Hub อ่าน `includes/tools.php` เรนเดอร์กริดการ์ด + ค้นหา
  - registry: array เมตาเครื่องมือ (เริ่มด้วย qr, และเติมทีละตัวในงาน T1–T10); header เรนเดอร์เมนูจาก registry
  - **อัปเดตลิงก์ทุกจุดที่อ้าง index.php ในฐานะหน้า QR** → `qr.php`: `assets/js/history.js` (`index.php?edit=` → `qr.php?edit=`), print.php/analytics ปุ่มที่กลับหน้า QR, และ header nav
  - `logout.php`/`login.php`/`register.php` redirect ไป `index.php` = ไป Hub (โอเค ไม่ต้องแก้)
  - **Privacy:** เอา guest auto-save ออกจากเครื่องมือ QR (create.js: `persist()` ถ้า isGuest → ไม่เซฟ; api/save.php: ปิด path guest-INSERT หรือคืนค่าเฉยๆ) — guest ดาวน์โหลดได้ปกติ
  - Hero + สโลแกนบน Hub · การ์ด responsive · light/dark
- **DB:** ไม่มี
- **เกณฑ์ผ่าน:** เปิด `/qrcode/` เห็น Hub มีการ์ด "สร้าง QR" (+ที่จะเพิ่มทีหลัง) · `qr.php` ทำงานครบเหมือน index เดิม (สร้าง/แก้/ดาวน์โหลด/ประวัติเชื่อมกันถูก) · guest ที่ qr.php ดาวน์โหลดได้แต่ไม่มีการเซฟใต้ `__guest__` · nav/ลิงก์ไม่พัง

---

### T1 — ย่อลิงก์ (URL Shortener) + นับคลิก  `backend · reuse redirect`
- **ไฟล์:** `shorten.php` + `assets/js/shorten.js` + `api/shorten/create.php`, `api/shorten/list.php` (ของฉันเมื่อล็อกอิน) + `s.php` (redirect) + schema/migration ตาราง `short_links`
- **สเปก:**
  - กรอก URL ยาว → (ไม่บังคับ) ตั้ง alias เอง / วันหมดอายุ → ได้ **ลิงก์สั้น** `BASE_URL/s.php?c=CODE` (หรือ `/s/CODE`) + ปุ่มคัดลอก + **ปุ่มทำ QR ของลิงก์สั้น** (เรียกใช้ qr-code-styling)
  - `s.php`: หา code ใน `short_links` (active + ไม่หมดอายุ) → `clicks++` (+log แถวใน `short_clicks` best-effort) → 302 redirect · ไม่พบ → หน้าไม่พบธีมเดียวกับ r.php
  - ล็อกอิน: เห็นรายการลิงก์ของตัวเอง + ยอดคลิก + แก้ปลายทาง/ปิด/ลบ · guest: ย่อได้ (เก็บ user_id NULL) แต่ดูรายการไม่ได้
  - validate URL (http/https), กัน alias ซ้ำ/สงวน (s, api, ...), `generate_short_code()` ถ้าไม่ตั้ง alias
- **DB:** `short_links(id, code VARCHAR(32) UNIQUE, target_url TEXT, user_id INT NULL, clicks INT DEFAULT 0, is_active TINYINT DEFAULT 1, expires_at DATETIME NULL, created_at)` + (optional) `short_clicks(id, link_id, clicked_at, ip_hash, referer, ua, INDEX)`
- **เกณฑ์ผ่าน:** ย่อ→ได้ลิงก์→เปิด s.php เด้งถูก + clicks เพิ่ม · หมดอายุ/ปิด→ไม่เด้ง · ล็อกอินเห็นสถิติของตัวเอง

### T2 — QR สำเร็จรูป: WiFi / vCard / อีเมล / SMS / โทร  `client-side · reuse qr-code-styling`
- **ไฟล์:** `qr-presets.php` + `assets/js/qr-presets.js` (โหลด `assets/vendor/qr-code-styling.js`)
- **สเปก:** แท็บ 5 ชนิด กรอกฟิลด์เฉพาะ → ประกอบสตริงมาตรฐาน แล้ววาด QR สด + ดาวน์โหลด PNG/SVG (ใช้รูปแบบ opts/ดาวน์โหลดแบบเดียวกับ create.js)
  - **WiFi:** `WIFI:T:WPA;S:<ssid>;P:<pass>;H:<hidden>;;` · **โทร:** `tel:<num>` · **SMS:** `SMSTO:<num>:<msg>` · **อีเมล:** `mailto:<to>?subject=&body=` · **vCard:** vCard 3.0 (ชื่อ/เบอร์/อีเมล/บริษัท/ที่อยู่/เว็บ)
  - ปรับสี/รูปแบบ/ขนาดได้ (reuse ชิ้นส่วนสไตล์จาก create.js) · ทั้งหมด client-side
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** แต่ละชนิดสร้าง QR สแกนแล้วได้ผลถูก (เช่น WiFi เชื่อมเน็ตได้, tel โทรออก)

### T3 — ลดขนาด/บีบอัดรูป + แปลงไฟล์  `client-side (Canvas) · private`
- **ไฟล์:** `image.php` + `assets/js/image.js` (อาจใช้ `assets/vendor/jszip.min.js` สำหรับหลายไฟล์)
- **สเปก:** ลากไฟล์รูป (หลายไฟล์ได้) → ปรับ: ขนาดสูงสุด(px)/เปอร์เซ็นต์, คุณภาพ(สำหรับ JPG/WebP), ฟอร์แมตออก (JPG/PNG/WebP) → พรีวิว + ขนาดก่อน/หลัง → ดาวน์โหลดทีละไฟล์หรือ ZIP · ใช้ `<canvas>` + `toBlob()` **ในเบราว์เซอร์ล้วน** (ไม่อัปขึ้นเซิร์ฟเวอร์ — เน้นป้ายบอก)
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** ย่อ 4000px→1000px ได้ไฟล์เล็กลงจริง, แปลง PNG→WebP ได้, หลายไฟล์→ZIP

### T4 — รูป → PDF · รวม PDF · แยกหน้า PDF  `client-side (pdf-lib)`
- **ไฟล์:** `pdf.php` + `assets/js/pdf.js` + `assets/vendor/pdf-lib.min.js`
- **สเปก:** 3 โหมด (แท็บ): (ก) **รูป→PDF** (เลือกรูปหลายไฟล์ เรียงลำดับ ตั้งขนาดหน้า A4/พอดีรูป → รวมเป็น PDF), (ข) **รวม PDF** (อัปหลาย PDF เรียงแล้วรวม), (ค) **แยกหน้า** (อัป PDF 1 ไฟล์ ระบุช่วงหน้า เช่น 1-3,5 → ได้ PDF ใหม่) · ใช้ **pdf-lib** ฝั่ง client ทั้งหมด
  - **นอกขอบเขต (อย่าทำ):** บีบอัด PDF, PDF→Word (ต้องใช้ server tools) — ระบุว่ายังไม่รองรับ
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** รูป 3 ใบ→PDF 3 หน้าเปิดได้, รวม 2 PDF ได้จำนวนหน้ารวม, แยกหน้า 2-3 ได้ PDF 2 หน้า

### T5 — เครื่องคำนวณผ่อน/สินเชื่อ  `client-side`
- **ไฟล์:** `calc-loan.php` + `assets/js/calc-loan.js`
- **สเปก:** อินพุต: เงินต้น, ดอกเบี้ย % ต่อปี, จำนวนงวด(เดือน), แบบดอกเบี้ย (**ลดต้นลดดอก / คงที่(flat)**) → ผลลัพธ์: ค่างวด/เดือน, ดอกเบี้ยรวม, ยอดชำระรวม, และ **ตารางผ่อน (amortization)** ต่องวด (เงินต้น/ดอกเบี้ย/คงเหลือ) · อัปเดตสด
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** สูตรลดต้นลดดอกถูก (ตรวจกับสูตร PMT), flat ถูก, ตารางรวมกันลงตัว

### T6 — ชุดเครื่องคำนวณ: BMI · ส่วนลด · VAT · เปอร์เซ็นต์ · ทิป  `client-side`
- **ไฟล์:** `calc.php` + `assets/js/calc.js`
- **สเปก:** แท็บ/ส่วนย่อย 5 อัน: **BMI** (นน./สส.→ค่า+เกณฑ์แปลผลไทย), **ส่วนลด** (ราคา, %ลด→ราคาสุทธิ+ประหยัด), **VAT 7%** (บวก/ถอด VAT), **เปอร์เซ็นต์** (X เป็นกี่% ของ Y / เพิ่ม-ลดกี่% / กี่% ของ), **ทิป** (บิล, %, หารกี่คน) · อัปเดตสด
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** ทุกโหมดคำนวณถูก

### T7 — สุ่มรายชื่อ / วงล้อสุ่ม / สุ่มเลข / ทอยลูกเต๋า  `client-side · ไวรัล`
- **ไฟล์:** `random.php` + `assets/js/random.js`
- **สเปก:** โหมด: (ก) **สุ่มรายชื่อ** (วางรายการทีละบรรทัด → สุ่มเลือก N, เลือกซ้ำได้/ไม่ได้), (ข) **วงล้อสุ่ม** (SVG/canvas หมุนมีอนิเมชัน + เสียง optional → ชี้ผู้ชนะ), (ค) **สุ่มเลข** (ช่วง min-max, จำนวน, ไม่ซ้ำ), (ง) **ลูกเต๋า/เหรียญ** · ใช้ `crypto.getRandomValues`
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** วงล้อหมุนลื่น ชี้ผลถูกช่อง, สุ่มไม่ซ้ำทำงาน

### T8 — สร้างรหัสผ่านปลอดภัย  `client-side`
- **ไฟล์:** `password.php` + `assets/js/password.js`
- **สเปก:** สไลเดอร์ความยาว (4–64), toggle: พิมพ์ใหญ่/เล็ก/ตัวเลข/สัญลักษณ์/เลี่ยงตัวกำกวม (0O1lI) → รหัส + **ตัววัดความแข็งแรง** + ปุ่มคัดลอก + ปุ่มสุ่มใหม่ · `crypto.getRandomValues` · (optional) สร้าง passphrase จากคำ
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** เคารพ toggle ทุกตัว, คัดลอกได้, วัดความแข็งแรงสมเหตุผล

### T9 — คำนวณอายุ · นับวัน · แปลง พ.ศ.↔ค.ศ.  `client-side · ฮิตไทย`
- **ไฟล์:** `date-tools.php` + `assets/js/date-tools.js`
- **สเปก:** โหมด: (ก) **อายุ** (วันเกิด→ปี/เดือน/วัน + วันเกิดถัดไปอีกกี่วัน), (ข) **นับวันระหว่าง 2 วันที่**, (ค) **บวก/ลบวัน** จากวันที่, (ง) **แปลง พ.ศ.↔ค.ศ.** + แสดงวันที่แบบไทย (ใช้ `thai_date` แนวเดียวกัน แต่ทำฝั่ง client) · อัปเดตสด
- **DB:** ไม่มี · **เกณฑ์ผ่าน:** อายุถูกต้องข้ามปีอธิกสุรทิน, แปลงปีถูก (+543)

### T10 — แปลงหน่วย + อุณหภูมิ + สกุลเงิน  `client-side (+API เรตเงิน)`
- **ไฟล์:** `convert.php` + `assets/js/convert.js` + (optional) `api/rates.php` (พร็อกซี+แคชเรต)
- **สเปก:** หมวด: ความยาว/น้ำหนัก/พื้นที่/ปริมาตร/อุณหภูมิ/ความเร็ว/ข้อมูล(bytes)/เวลา — ตารางแฟกเตอร์ฝั่ง client, แปลงสด · **สกุลเงิน:** ดึงเรตจาก API ฟรีไม่ต้องคีย์ (แนะนำ `frankfurter.app`) — ทำ **`api/rates.php` เป็นพร็อกซีฝั่ง server + แคชผลใน `settings` รายวัน** (เลี่ยง CORS/rate-limit ฝั่ง client) และมี fallback ถ้าดึงไม่ได้
- **DB:** ใช้ `settings` เก็บแคชเรต (ไม่ต้องสร้างตารางใหม่) · **เกณฑ์ผ่าน:** หน่วยทั่วไปแปลงถูก, อุณหภูมิ C/F/K ถูก, สกุลเงินโชว์เรตล่าสุด/แคชได้

---

## 6. ลำดับ & dependency
1. **T0 (ฐาน) ต้องเสร็จก่อนทุกงาน** (สร้าง Hub + registry + ย้าย qr.php)
2. จากนั้นทำเรียง fit/effort: **T1 → T2 → T3 → T4 → T5 → T6 → T7 → T8 → T9 → T10**
3. งาน client-side (T2,T3,T5,T6,T7,T8,T9) แทบไม่ชนกัน แต่ **ทุกงาน append `includes/tools.php`** — ให้ทำทีละงาน (append-only ลดชน) และแตะ header เฉพาะผ่าน registry
4. งานมี DB: **T1** (short_links) เท่านั้น (+T10 ใช้ settings เดิม)

---

## 7. วิธีทดสอบในสภาพแวดล้อมนี้
- **screenshot ใน browser pane ค้าง** — อย่าพึ่ง. ใช้: `php -l` ทุกไฟล์ PHP, `node -c` ทุกไฟล์ JS, ตรวจ DOM/พฤติกรรมผ่าน JS (querySelector/จำลองคลิก/อ่านผล), `curl` สำหรับ API/redirect (ส่ง JSON ผ่านไฟล์เลี่ยง mojibake ไทย), ดู console error = 0
- ไฟล์ที่มีภาษาไทย: เขียนด้วยเครื่องมือ Write (UTF-8) ไม่ใช่ผ่าน shell · ตรวจความถูกด้วย `bin2hex()` (mysql CLI โชว์ไทยเป็น ??)
- **XAMPP อาจดับ** ระหว่างทาง — สตาร์ตใหม่แบบ detached: PowerShell `Start-Process 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini','--standalone' -WindowStyle Hidden` และ `Start-Process 'C:\xampp\apache\bin\httpd.exe' -WindowStyle Hidden` · PHP: `C:/xampp/php/php.exe` · MySQL CLI: `C:/xampp/mysql/bin/mysql.exe -u root` (ไม่มีรหัส)
- **admin เดโมถูกเจ้าของเปลี่ยนรหัสแล้ว** — อย่ารีเซ็ต; ทดสอบส่วนที่ต้องล็อกอินด้วยการสมัครบัญชี staff ชั่วคราวผ่าน `register.php` แล้วลบทิ้ง · ล้างข้อมูลทดสอบทุกครั้ง (เหลือ users: admin + __guest__)

---

## 8. ก่อนขึ้น hosting จริง (เฟส hardening — ยังไม่ทำตอนนี้ แต่ต้องทำก่อนออนไลน์)
- **Rate-limit** ต่อ IP: หน้าสมัคร (`register.php`) + ย่อลิงก์ (`api/shorten/create.php`) + guest actions ที่เขียน DB — กันบอท/สแปม
- **HTTPS + secure cookie** (แก้ `secure=>true` ใน `session_set_cookie_params` เมื่อขึ้น https) · security headers (CSP/X-Frame-Options/Referrer-Policy)
- **ลบ `install.php`** หลังติดตั้ง · เปลี่ยน/ลบบัญชี/รหัสเดโม · ตั้ง `BASE_URL` เป็นโดเมนจริง (QR/ลิงก์ dynamic จะได้ชี้ถูก)
- พิจารณาลบข้อมูลนิรนามเก่าอัตโนมัติ (short_links ที่หมดอายุ) · ตรวจ upload ถ้ามีในอนาคต

---

## 9. ไลบรารีที่ต้องเก็บในเครื่อง (`assets/vendor/`, ห้าม CDN ตอนรัน)
- มีแล้ว: `qr-code-styling.js`, `jszip.min.js`, `xlsx.full.min.js`
- ต้องเพิ่ม: **`pdf-lib.min.js`** (T4 — `https://cdn.jsdelivr.net/npm/pdf-lib/dist/pdf-lib.min.js`) · (optional T3) `browser-image-compression`
- T10 สกุลเงิน: ใช้ API `https://api.frankfurter.app/latest?from=THB` (ฟรี ไม่ต้องคีย์) ผ่านพร็อกซี `api/rates.php` + แคชใน `settings`

---

## 10. สรุปเครื่องมือ (11 ตัว รวม QR เดิม)
QR(เดิม) · ย่อลิงก์ · QR สำเร็จรูป(WiFi/vCard/…) · ลดขนาด-แปลงรูป · รูป→PDF/รวม/แยก · คำนวณผ่อน · ชุดคำนวณ(BMI/ส่วนลด/VAT/%/ทิป) · สุ่ม/วงล้อ · สร้างรหัสผ่าน · อายุ/วันที่/พ.ศ.-ค.ศ. · แปลงหน่วย/อุณหภูมิ/สกุลเงิน

---

## 11. รอบเพิ่มเติม — เครื่องมือ "ไทยแท้" (T11–T14, client-side ล้วน)
> รอบสอง หลัง T0–T10 เสร็จ · เน้นสิ่งที่คนไทยค้นหา/ใช้จริง · ทุกตัว client-side (ไม่มี backend/DB) · กติกาเดิมทั้งหมด (หัวข้อ 3–4, 7): `require_installed()`, append registry, ธีม light/dark, ภาษาไทย, ทดสอบก่อนปิดงาน

### T11 — QR พร้อมเพย์ (PromptPay QR)  `client-side · reuse qr-code-styling · หมวด QR & ลิงก์`
- **ไฟล์:** `promptpay.php` + `assets/js/promptpay.js`
- **สเปก:** กรอกเบอร์โทร **หรือ** เลขบัตรประชาชน 13 หลัก (+ตัวเลือกจำนวนเงิน) → ประกอบ payload มาตรฐาน **EMVCo/PromptPay** (AID `A000000677010111`, tag 29 → subtag 01=proxy) + **CRC16-CCITT (poly 0x1021, init 0xFFFF)** ต่อท้าย tag 63 → วาด QR สด (qr-code-styling) + ดาวน์โหลด PNG/SVG
  - เบอร์โทร → normalize เป็น `0066` + เบอร์ตัดศูนย์หน้า (13 หลัก); บัตร ปชช. → 13 หลักตรงๆ; จำนวนเงิน → tag 54, ถ้าไม่กรอก = QR แบบไม่ระบุยอด
  - **ต้องทดสอบ payload กับสูตรมาตรฐาน + สแกนด้วยแอปธนาคารจริง** (ความถูกต้องของ CRC/format คือ acceptance)
- **เกณฑ์ผ่าน:** สแกน QR ด้วยแอปธนาคารแล้วขึ้นชื่อบัญชี/พร้อมโอนได้ · ทั้งแบบระบุยอด/ไม่ระบุยอด

### T12 — อ่านจำนวนเงินเป็นตัวอักษรไทย (บาทถ้วน)  `client-side · หมวดเครื่องคำนวณ`
- **ไฟล์:** `bahttext.php` + `assets/js/bahttext.js`
- **สเปก:** ใส่ตัวเลข (รองรับทศนิยม 2 ตำแหน่ง = สตางค์) → คำอ่านภาษาไทยตามหลัก ราชบัณฑิตฯ เช่น `1,250.50` → "หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์", `21` → "ยี่สิบเอ็ดบาทถ้วน", `1000000` → "หนึ่งล้านบาทถ้วน" · จัดการ "เอ็ด/ยี่/สิบ" และหลักล้านซ้ำ (>7 หลัก) ให้ถูก · อัปเดตสด + ปุ่มคัดลอก
- **เกณฑ์ผ่าน:** เคสมาตรฐานถูกทุกเคส (เอ็ด, ยี่สิบ, สิบ, ล้านซ้ำ, ศูนย์บาท, สตางค์อย่างเดียว, ปัดสตางค์)

### T13 — คำนวณภาษีเงินได้บุคคลธรรมดา  `client-side · หมวดเครื่องคำนวณ`
- **ไฟล์:** `tax.php` + `assets/js/tax.js`
- **สเปก:** รายได้ทั้งปี + ค่าลดหย่อนหลัก (ส่วนตัว 60,000 · คู่สมรส · บุตร · ประกันสังคม · ประกันชีวิต/สุขภาพ · กองทุน RMF/SSF · ดอกเบี้ยบ้าน · บริจาค ฯลฯ ตามเพดานปัจจุบัน) + หักค่าใช้จ่าย 50% ไม่เกิน 100,000 → เงินได้สุทธิ → ภาษีแบบ **ขั้นบันได** (0/5/10/15/20/25/30/35% ตามช่วง) → แสดงภาษีที่ต้องจ่าย + effective rate + ตารางแยกตามขั้น · อัปเดตสด
  - **ใส่หมายเหตุปีภาษี + คำเตือน** ว่าเป็นการประมาณเบื้องต้น อ้างอิงเกณฑ์ล่าสุด ควรตรวจกับกรมสรรพากร (กันเรื่องเกณฑ์เปลี่ยนรายปี)
- **เกณฑ์ผ่าน:** ขั้นบันไดถูก (ตรวจกับตัวอย่างสรรพากร) · เพดานลดหย่อน/ค่าใช้จ่ายถูก

### T14 — ชุดเครื่องมือข้อความ  `client-side · หมวดตัวช่วย & สุ่ม`
- **ไฟล์:** `text-tools.php` + `assets/js/text-tools.js`
- **สเปก:** แท็บ/ส่วนย่อย: **นับคำ/ตัวอักษร/บรรทัด/ย่อหน้า** (รองรับนับอักษรไทย) · **แปลงตัวพิมพ์** (ใหญ่หมด/เล็กหมด/ขึ้นต้นประโยค/สลับ) · **ลบบรรทัดซ้ำ/ช่องว่างเกิน/บรรทัดว่าง** · **เรียงบรรทัด** (ก-ฮ/A-Z/สุ่ม/กลับด้าน) · **เลขไทย↔อารบิก** (๐-๙ ↔ 0-9) · **ตรวจเลขบัตรประชาชน 13 หลัก** (อัลกอริทึม check digit) · อัปเดตสด + ปุ่มคัดลอก
- **เกณฑ์ผ่าน:** นับถูก (รวมไทย) · แปลง/ลบ/เรียงถูก · check digit บัตร ปชช. ตรวจถูก (เลขจริงผ่าน, เลขมั่วไม่ผ่าน)

## 12. สรุปเครื่องมือรอบเพิ่มเติม (รวมเป็น 15 ตัว)
+ QR พร้อมเพย์ · บาทถ้วน · คำนวณภาษีเงินได้ · ชุดเครื่องมือข้อความ
