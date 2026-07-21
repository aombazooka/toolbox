# QR Studio — แผนงานพัฒนา (สำหรับมอบหมาย subagent)

เอกสารนี้คือแผนงานที่เหลือทั้งหมด ออกแบบให้ **subagent หยิบไปทำทีละงาน (1 งาน / 1 subagent)** ได้โดยไม่ต้องมีบริบทเดิม
อ่านหัวข้อ **0–2 ก่อนเสมอ** แล้วจึงลงมือทำงานที่ได้รับมอบหมายในหัวข้อ 3

> **สถานะ (อัปเดต 2026-07-14): เสร็จครบทุกงาน T1–T10 + T8a/b/c แล้ว** ✅
> เอกสารนี้เก็บไว้เป็นบันทึกสเปก/ประวัติการพัฒนา — งานทั้งหมดในหัวข้อ 3 ทำและทดสอบผ่านแล้ว (ดูสรุปในหัวข้อ 6)

---

## 0. บริบทระบบ (อ่านก่อนเริ่ม)

- **Stack:** PHP 8.2 + MySQL/MariaDB บน XAMPP · เสิร์ฟที่ `http://localhost/qrcode` · DB ชื่อ `qrcode_studio`
- **QR rendering:** ฝั่ง client ด้วย `assets/vendor/qr-code-styling.js` (โลโก้กลาง + สไตล์) — **ไม่มี** การเจน QR ฝั่ง server
- **ธีม:** มินิมอล สีส้มพาสเทล ภาษาไทยทั้งหมด รองรับ light/dark (`data-theme` + `localStorage` key `qrs-theme`)
- **ความปลอดภัย:** PHP session + CSRF · ทุกข้อมูลผูก `user_id` (พร้อมขยายหลายผู้ใช้) · ปัจจุบันผู้ใช้เดียว (role `admin`)
- **บัญชีเดโม (ตอนพัฒนา):** `admin` / `qr123456`

### โครงไฟล์
```
config.php              DB_*, BASE_URL, APP_NAME, APP_TZ (define constants)
schema.sql              โครงตาราง (idempotent: CREATE TABLE IF NOT EXISTS)
install.php             ติดตั้งครั้งแรก (สร้าง DB+ตาราง+admin) — บล็อกตัวเองเมื่อติดตั้งแล้ว
login.php / logout.php
index.php               หน้าสร้าง QR         → assets/js/create.js
history.php             ประวัติ+ค้นหา+สถิติ   → assets/js/history.js
bulk.php                แปลงหลายลิงก์+ZIP     → assets/js/bulk.js (+ jszip.min.js)
print.php               แผ่นพิมพ์ A4          → assets/js/print.js
r.php                   เด้งลิงก์ dynamic + log สแกน (public, ไม่ต้องล็อกอิน)
includes/  db.php auth.php helpers.php header.php footer.php
api/       save.php list.php get.php update.php delete.php   (JSON)
assets/    css/{app.css, app-extra.css}  js/*  vendor/{qr-code-styling.js, jszip.min.js}
```

### Helper ที่มีให้ใช้ (อย่าเขียนซ้ำ)
- `includes/db.php` → `db(): PDO` (singleton, ERRMODE_EXCEPTION, FETCH_ASSOC)
- `includes/helpers.php` → `e()`, `generate_short_code()`, `json_out($data,$code)`, `json_input()`, `csrf_token()`, `csrf_check($t)`, `thai_date($dt)`, `dynamic_link($code)`
- `includes/auth.php` → `current_user()`, `is_logged_in()`, `require_login()` (redirect หน้า login), `require_login_api()` (401 JSON), `login_user($row)`, `logout_user()`, `uid()` (id ผู้ใช้ปัจจุบัน)

### ตาราง DB ปัจจุบัน
- `users(id, username, password_hash, display_name, role['admin'|'staff'], created_at)`
- `categories(id, user_id, name, color, created_at)` — **มีตารางแต่ยังไม่ได้ใช้** (ดู T5)
- `qrcodes(id, user_id, name, category[string], type['static'|'dynamic'], destination_url, short_code[unique,null], style_json[MEDIUMTEXT], logo_data[MEDIUMTEXT base64], scan_count, is_active, expires_at, created_at, updated_at)`
- `scans(id, qrcode_id, scanned_at, ip_hash, user_agent)`
- `settings(k, v)`

`style_json` เก็บ `{dotColor,bgColor,dotType,ec,logoSize,size}` · `logo_data` เก็บ data URL base64 ของโลโก้

---

## 1. กติกา / Definition of Done (ใช้กับทุกงาน)

งานถือว่าเสร็จเมื่อครบทุกข้อ:

1. **ตามแพตเทิร์นเดิม** — หน้าใหม่: `require_once includes/auth.php; require_login();` ตั้ง `$active,$page_title,$page_js,$csrf` → `include header.php` → เนื้อหา → `<script>window.APP={baseUrl,csrf}</script>` → `include footer.php`
2. **API ใหม่:** `require ../includes/auth.php; require_login_api();` · ทุก **POST เช็ค `csrf_check`** · ตอบด้วย `json_out()` · ทุก query **scope ด้วย `uid()`** (ยกเว้น admin ดูรวม ดู T8)
3. **ลิงก์ภายในใช้ relative** (`index.php`, `assets/...`) — ใช้ `BASE_URL` **เฉพาะ** ลิงก์ที่จะถูกสแกน (dynamic) เท่านั้น เพื่อให้ระบบพกพาได้
4. **UI ภาษาไทย** · ใช้ CSS variables ธีมส้มพาสเทล (`--accent`, `--accent-strong`, `--accent-ink`, `--surface`, ฯลฯ) · ทำงานทั้ง light/dark
5. **QR ฝั่ง client** เจนจาก `style_json` (ดูฟังก์ชัน `buildOpts` ใน `history.js`/`print.js` เป็นต้นแบบ) · **อย่า** ดึง `logo_data` มาใน list (หนัก) — ใช้ `api/get.php` เมื่อต้องใช้โลโก้จริง
6. **แก้ schema:** อัปเดต `schema.sql` (idempotent) **และ** เพิ่ม migration ให้ระบบที่ติดตั้งแล้วอัปเกรดได้ (ดู T9)
7. **ทดสอบก่อนปิดงาน** (ดูหัวข้อ 4) — lint ผ่าน, ไม่มี console error, ทดสอบ flow จริง
8. **ไม่ commit ความลับ / ไม่แก้ `config.php` credentials / ไม่ลบข้อมูลผู้ใช้จริง** · ลบไฟล์ทดสอบ/seed ที่สร้างขึ้นชั่วคราวทุกครั้ง

---

## 2. การมอบหมายให้ subagent

- **1 งาน (Tx) = 1 subagent** · ทำงานตามลำดับความสำคัญ/dependency ในหัวข้อ 5
- แต่ละ subagent: อ่านหัวข้อ 0–2 + สเปกงานของตน + ไฟล์ที่เกี่ยวข้อง → ลงมือ → ทดสอบ → **รายงานกลับ**: ไฟล์ที่แก้/เพิ่ม, การเปลี่ยน DB, วิธีทดสอบและผลลัพธ์, สิ่งที่ค้าง
- ห้ามขยายขอบเขตเกินสเปก — ถ้าเจอปัญหา dependency ให้ระบุในรายงาน

---

## 3. รายการงาน (Tickets)

รูปแบบ: **เป้าหมาย · ไฟล์ · สเปก · DB · เกณฑ์ผ่าน**

### T1 — ตั้งค่าบัญชี & เปลี่ยนรหัสผ่าน  `P1 · เล็ก`
- **เป้าหมาย:** ปิดช่องโหว่รหัสเริ่มต้น ให้ผู้ใช้แก้ชื่อที่แสดง + เปลี่ยนรหัสผ่านได้
- **ไฟล์:** `settings.php` (ใหม่) · `includes/header.php` (เพิ่มไอคอนเฟือง/ลิงก์ "ตั้งค่า" ข้าง user-chip → settings.php)
- **สเปก:** หน้า settings มี 2 การ์ด — (ก) ข้อมูลบัญชี: แก้ `display_name`; (ข) เปลี่ยนรหัสผ่าน: รหัสปัจจุบัน + ใหม่ + ยืนยัน. POST มาที่ตัวเอง, เช็ค `csrf`, `password_verify` รหัสปัจจุบัน, `password_hash` รหัสใหม่ (>=6 ตัว), `UPDATE users`. อัปเดต `$_SESSION['user']['display_name']` ทันทีเมื่อเปลี่ยนชื่อ. แสดงข้อความสำเร็จ/ผิดพลาดแบบ `.auth-msg`
- **DB:** ไม่มี
- **เกณฑ์ผ่าน:** เปลี่ยนรหัสได้, รหัสปัจจุบันผิด→error, รหัสใหม่ใช้ล็อกอินได้, ชื่อที่แสดงเปลี่ยนใน header ทันที

### T9 — กลไก migration (ทำก่อนงานที่แก้ DB)  `P1 · เล็ก`
- **เป้าหมาย:** ให้ระบบที่ติดตั้งแล้วอัปเกรด schema ได้อย่างปลอดภัย (idempotent)
- **ไฟล์:** `migrate.php` (ใหม่, ต้องล็อกอิน + role admin) · แนวทางใน README
- **สเปก:** `migrate.php` รัน DDL ที่ปลอดภัยซ้ำได้ — ใช้ `CREATE TABLE IF NOT EXISTS` และสำหรับ `ALTER` ให้เช็ค `information_schema.COLUMNS/STATISTICS` ก่อนเพิ่มคอลัมน์/ดัชนี. งานอื่นที่เพิ่มตาราง/คอลัมน์ให้มาเติม statement ที่นี่ + อัปเดต `schema.sql`
- **DB:** เพิ่ม index `scans(scanned_at)` เป็นตัวอย่างแรก (ใช้ใน T2)
- **เกณฑ์ผ่าน:** รัน `migrate.php` ซ้ำได้ไม่ error, ดัชนีถูกสร้าง

### T3 — แก้ไข QR เดิมเต็มรูปแบบ  `P1 · กลาง`
- **เป้าหมาย:** เปิด QR ที่บันทึกไว้กลับมาแก้ (สไตล์/โลโก้/ชื่อ/ลิงก์) แล้วบันทึกทับ **รายการเดิม** (ไม่สร้างซ้ำ)
- **ไฟล์:** `index.php` (รับ `?edit=ID`) · `assets/js/create.js` (prefill + โหมด update) · `api/save.php` (รองรับ `id`→UPDATE) · `assets/js/history.js` (ปุ่มแก้ไข → `index.php?edit=ID`)
- **สเปก:**
  - `api/save.php`: ถ้ามี `id` และเป็นของ `uid()` → `UPDATE` เฉพาะ name, category, destination_url, style_json, logo_data **โดยไม่เปลี่ยน `type` และ `short_code`** (QR ที่พิมพ์ไปแล้วต้องยังเด้งได้) → คืน id เดิม · **ล็อก type ในโหมดแก้ไข** (ปิดปุ่ม `#qrtype`) เพื่อรักษาความถูกต้องของ short_code
  - `index.php`: ถ้ามี `?edit=ID` ให้ inject ข้อมูลเริ่มต้น (ผ่าน `window.APP.edit = {...}` จาก PHP: query แถวนั้น scope uid) รวม `logo_data`
  - `create.js`: ถ้ามี `window.APP.edit` → เซ็ต `S`, prefill ฟอร์ม, ตั้ง `savedId = edit.id` (ให้ดาวน์โหลด/บันทึกครั้งแรก = UPDATE ไม่สร้างใหม่), แสดงหัวข้อ "แก้ไข QR"
  - ปุ่ม "แก้ไข" ในหน้า history ให้มีทั้ง static และ dynamic → นำไป `index.php?edit=ID`
- **DB:** ไม่มี
- **เกณฑ์ผ่าน:** แก้ QR แล้วดาวน์โหลด → จำนวนรายการใน history **ไม่เพิ่ม**, `short_code` ของ dynamic คงเดิม, ค่าที่แก้ถูกบันทึก

### T2 — แดชบอร์ดสถิติการสแกน  `P1 · กลาง`  (ต้องมี T9)
- **เป้าหมาย:** ดูยอดสแกน QR ไดนามิกตามช่วงเวลา + รายตัว
- **ไฟล์:** `analytics.php` (ใหม่) · `assets/js/analytics.js` · `api/analytics.php` · `includes/header.php` (เพิ่มแท็บ "สถิติ") · `history.js` (แถว dynamic คลิกดูสถิติ → `analytics.php?id=ID`)
- **สเปก:**
  - `api/analytics.php` (scope uid): ภาพรวม = ยอดสแกนรวม, จำนวน QR ไดนามิก, สแกน 30 วันล่าสุดต่อวัน (`GROUP BY DATE(scanned_at)`), Top 5 QR ที่ถูกสแกนมากสุด (JOIN qrcodes). ถ้ามี `?id=` → รายละเอียด QR นั้น: timeline รายวัน + สแกนล่าสุด 50 รายการ (scanned_at, user_agent ย่อ)
  - `analytics.js`: วาดกราฟแท่งด้วย **inline SVG เอง (ไม่พึ่งไลบรารีนอก)** · การ์ดสรุป (ใช้คลาส `.stat` เดิม) · ตาราง Top QR
  - เชื่อมโยง: แถว dynamic ใน history เพิ่มปุ่ม/ลิงก์ "สถิติ"
- **DB:** ใช้ `scans` เดิม + ดัชนี `scans(scanned_at)` (จาก T9)
- **เกณฑ์ผ่าน:** ยอดตรงกับตาราง `scans`, กราฟรายวันขึ้น, หน้า `?id=` แสดงเฉพาะ QR นั้น, เห็นเฉพาะข้อมูลของผู้ใช้

### T7 — วันหมดอายุ & เปิด/ปิดลิงก์  `P2 · เล็ก`
- **เป้าหมาย:** ตั้งวันหมดอายุและเปิด/ปิด QR ไดนามิก (`r.php` เช็ค `expires_at`/`is_active` อยู่แล้ว)
- **ไฟล์:** `index.php` (ช่องวันหมดอายุใน "ตัวเลือกขั้นสูง") · `create.js` (ส่ง `expires_at`) · `api/save.php` + `api/update.php` (รับ `expires_at`) · `history.js` (สลับ active/หมดอายุ)
- **สเปก:** input `datetime-local` (ไม่บังคับ) · save/update รับค่า แปลงเป็น DATETIME หรือ NULL · ใน history เพิ่มปุ่มสลับสถานะ (ใช้ `api/update.php` ที่รองรับ `is_active` อยู่แล้ว) + แสดงป้าย "ปิด/หมดอายุ"
- **DB:** ใช้คอลัมน์เดิม (`expires_at`, `is_active`)
- **เกณฑ์ผ่าน:** ตั้งหมดอายุในอดีต→`r.php` ตอบ "หมดอายุ", ปิดใช้งาน→"ปิดการใช้งาน", เปิดใหม่ได้

### T5 — โฟลเดอร์/แท็ก (หมวดหมู่จาก DB)  `P2 · กลาง`  (ต้องมี T9)
- **เป้าหมาย:** จัดการหมวดหมู่จริงจาก DB (เพิ่ม/ลบ/สี) แทน dropdown ฮาร์ดโค้ด
- **ไฟล์:** `api/categories.php` (list/add/delete, scope uid) · จัดการหมวดใน `settings.php` (จาก T1) หรือหน้าแยก · `index.php`/`history.php`/`print.php` (เติม options หมวดจาก DB — render ผ่าน PHP หรือ fetch)
- **สเปก:** ใช้ตาราง `categories` เดิม · `qrcodes.category` ยังเก็บเป็น **string ชื่อหมวด** (ไม่เปลี่ยนเป็น FK เพื่อความเข้ากันได้) · seed หมวดเริ่มต้นตอน install + migration ใส่ค่าเริ่มต้นถ้าตารางว่าง · แสดงป้ายสีในหน้า history ตามสีหมวด
- **DB:** ใช้ `categories` เดิม (อาจเพิ่ม migration seed)
- **เกณฑ์ผ่าน:** เพิ่มหมวด→โผล่ใน dropdown ทุกหน้า, ป้ายสีแสดงถูกต้อง

### T4 — นำเข้า CSV/Excel  `P2 · กลาง`
- **เป้าหมาย:** อัปโหลด/วางรายการ `ชื่อ,ลิงก์[,หมวด]` เพื่อสร้าง+บันทึกทีละมาก
- **ไฟล์:** `bulk.php` (โหมด CSV + file input) · `assets/js/bulk.js` (parse CSV ฝั่ง client, แมปคอลัมน์) · ใช้ `api/save.php` เดิม
- **สเปก:** รองรับ **CSV** ก่อน (แต่ละบรรทัด `ชื่อ,ลิงก์` หรือ `ชื่อ,ลิงก์,หมวด`) — parse ใน JS, แสดงตัวอย่าง preview grid พร้อมชื่อ, "บันทึกทั้งหมด" ใช้ชื่อ/หมวดจาก CSV · **Excel (.xlsx)** เป็น optional: ถ้าทำ ให้โหลด SheetJS มาไว้ `assets/vendor/` (ห้ามพึ่ง CDN)
- **DB:** ไม่มี
- **เกณฑ์ผ่าน:** วาง `ชื่อ,ลิงก์` หลายบรรทัด→เจน QR ตามชื่อ→บันทึกครบพร้อมชื่อ/หมวด

### T6 — เทมเพลตสไตล์  `P2 · เล็ก-กลาง`  (ต้องมี T9)
- **เป้าหมาย:** บันทึกสไตล์ปัจจุบัน (สี/รูปแบบ/โลโก้) เป็นเทมเพลตตั้งชื่อ แล้วเรียกใช้ซ้ำ
- **ไฟล์:** `schema.sql` + migration (ตาราง `templates`) · `api/templates.php` (list/add/delete) · `index.php`+`create.js` (ปุ่ม "บันทึกสไตล์" + dropdown เลือกเทมเพลต)
- **สเปก:** ตาราง `templates(id, user_id, name, style_json, logo_data, created_at)` · "บันทึกสไตล์" เก็บ S ปัจจุบัน · เลือกเทมเพลต→apply ลงฟอร์ม+preview
- **DB:** ตาราง `templates` ใหม่
- **เกณฑ์ผ่าน:** บันทึกสไตล์แล้วนำไปใช้กับ QR ใหม่ได้

### T10 — เสริมความปลอดภัย  `P2 · เล็ก`
- **เป้าหมาย:** ลดความเสี่ยงพื้นฐาน
- **ไฟล์:** `includes/auth.php` (cookie flags), `login.php` (จำกัดการลองผิด), README (เตือนลบ install.php)
- **สเปก:** ตั้ง session cookie `httponly` + `samesite=Lax` (ผ่าน `session_set_cookie_params` ก่อน `session_start` ใน auth.php) · จำกัดการล็อกอินผิดต่อเนื่อง (นับใน session หรือ settings) แล้วหน่วงเวลา/ล็อกชั่วคราว · เพิ่มเตือนถ้า `install.php` ยังอยู่หลังติดตั้ง
- **DB:** ไม่มี (หรือ optional `login_attempts`)
- **เกณฑ์ผ่าน:** cookie มี HttpOnly/SameSite, ลองรหัสผิดหลายครั้งถูกจำกัด

### T8 — หลายผู้ใช้ + สิทธิ์ + audit log  `P3 · ใหญ่`  (ต้องมี T1, T9)
แบ่งเป็น 3 งานย่อย:
- **T8a จัดการผู้ใช้ (admin):** `users_admin.php` + `api/users.php` — admin สร้าง/แก้/รีเซ็ตรหัส/ลบผู้ใช้ (role admin/staff) · เพิ่ม `require_admin()` ใน auth.php · header แสดงเมนู admin เฉพาะ admin
- **T8b สิทธิ์การเข้าถึง:** staff เห็น/จัดการเฉพาะของตน (เป็นอยู่แล้วผ่าน `uid()`) · admin มีสวิตช์ "ดูของทุกคน" ใน history/analytics (ตัด filter user_id เมื่อเป็น admin เท่านั้น) · ป้องกันทุก API ให้ตรวจสิทธิ์
- **T8c audit log:** ตาราง `audit_log(id,user_id,action,entity,entity_id,detail,created_at)` + helper `log_audit()` · เรียก log ตอน login/create/update/delete · หน้า `audit.php` (admin) ดูย้อนหลัง
- **DB:** ตาราง `audit_log` ใหม่ (+ migration)
- **เกณฑ์ผ่าน:** admin สร้าง staff ได้, staff เห็นเฉพาะของตน, admin ดูรวมได้, การกระทำถูกบันทึกใน audit

---

## 4. วิธีทดสอบในสภาพแวดล้อมนี้ (สำคัญ)

- **การ screenshot ใน browser pane ค้าง** — อย่าพึ่ง screenshot. ใช้แทน:
  - `php -l <file>` ตรวจ syntax ทุกไฟล์ PHP ที่แก้
  - ตรวจ DOM ผ่าน JS: `document.querySelectorAll(...)`, อ่านค่า, จำลองคลิก `.click()` แล้วเช็คผล
  - ทดสอบ API ด้วย `curl` — **ส่ง JSON ผ่านไฟล์** (`--data-binary @file`) โดยสร้างไฟล์ด้วย PHP `json_encode` (ห้ามพิมพ์ JSON ภาษาไทยใน bash โดยตรง — จะ mojibake)
  - เช็ค console error = 0
- **flow ล็อกอินสำหรับ curl:** GET `login.php` (เก็บ cookie + csrf จาก hidden field) → POST `login.php` → GET หน้าใดๆ ดึง `csrf` จาก `window.APP` → เรียก API ด้วย cookie เดิม
- **ภาษาไทย:** MySQL CLI console แสดงไทยเป็น `???` (ปกติ ไม่ใช่บั๊ก) — ตรวจความถูกต้องด้วย `bin2hex()` หรือดูในหน้าเว็บ. เขียนไฟล์ที่มีภาษาไทยด้วยเครื่องมือ Write (UTF-8) ไม่ใช่ผ่าน shell
- **ล้างข้อมูล/ไฟล์ทดสอบทุกครั้งก่อนปิดงาน**

---

## 5. ลำดับแนะนำ & dependency

| ลำดับ | งาน | เหตุผล |
|---|---|---|
| Milestone A | **T9 → T1 → T10** | migration ก่อน, ปิดช่องโหว่รหัส/ความปลอดภัยเร็วๆ |
| Milestone B | **T3 → T2 → T7** | คุณค่าหลัก: แก้ QR เดิม, สถิติสแกน, หมดอายุ |
| Milestone C | **T5 → T4 → T6** | UX งานเยอะ: หมวดหมู่, นำเข้า CSV, เทมเพลต |
| Milestone D | **T8a → T8b → T8c** | ขยายหลายผู้ใช้ + audit (ใหญ่สุด ทำท้าย) |

**Dependency:** T9 ต้องมาก่อน T2/T5/T6/T8 (งานที่แตะ DB) · T5 ควรมาก่อน/พร้อม T4 (ใช้ dropdown หมวดร่วมกัน) · T8 ต้องมี T1 (แพตเทิร์นบัญชี) + T9

---

## 6. ของที่ "ทำเสร็จแล้ว" (ห้ามทำซ้ำ)
**เฟส 1 + เฟส 2 ครบทั้งหมด** (ทุกงานทดสอบ end-to-end ผ่านแล้ว):
- แผ่นพิมพ์ A4 (`print.php`) · ZIP export (bulk) · auto-save on first download · ค้นหา/สถิติพื้นฐาน
- **T9** migration runner (`migrate.php`, idempotent, guard ด้วย information_schema)
- **T1** ตั้งค่าบัญชี + เปลี่ยนรหัสผ่าน (`settings.php`)
- **T10** ความปลอดภัย: session cookie HttpOnly/SameSite + จำกัดล็อกอินผิด (`login_attempts`) + เตือน install.php
- **T3** แก้ไข QR เดิมเต็มรูปแบบ (`index.php?edit=`, UPDATE, ล็อก type/short_code)
- **T2** แดชบอร์ดสถิติสแกน (`analytics.php`, กราฟ SVG เอง, per-QR + ภาพรวม)
- **T7** วันหมดอายุ + เปิด/ปิดลิงก์
- **T5** หมวดหมู่จาก DB (`categories` + `api/categories.php` + จัดการใน settings + สีป้าย)
- **T4** นำเข้า CSV/Excel (`bulk.php` + SheetJS local, parse ชื่อ,ลิงก์,หมวด)
- **T6** เทมเพลตสไตล์ (`templates` + `api/templates.php`)
- **T8a** จัดการผู้ใช้ (`users_admin.php` + `api/users.php`, role admin/staff, กัน admin คนสุดท้าย)
- **T8b** สิทธิ์เข้าถึง: staff เห็นเฉพาะของตน · admin "ดูของทุกคน" + จัดการข้ามผู้ใช้ได้
- **T8c** audit log (`audit_log` + `log_audit()` + `audit.php`) บันทึก login/qr/user events

DB tables ทั้งหมด: users, categories, qrcodes, scans, settings, login_attempts, templates, audit_log
`migrate.php` มี 5 migrations · หน้า admin (จัดการผู้ใช้/อัปเดต DB/audit) อยู่ใน `settings.php`
