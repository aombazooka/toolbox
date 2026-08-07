# Toolbox

เว็บรวม **เครื่องมือออนไลน์ฟรี** ใช้ง่าย ไม่ต้องล็อกอิน **ทำงานในเบราว์เซอร์ทั้งหมด (client-side)
— ไม่ต้องใช้ฐานข้อมูล ไม่มีการอัปโหลดไฟล์ขึ้นเซิร์ฟเวอร์**

**เครื่องมือ (14):** สร้าง QR · QR สำเร็จรูป (WiFi/vCard/อีเมล/SMS/โทร) · QR พร้อมเพย์ ·
ลดขนาด/แปลงรูป · รูป→PDF/รวม/แยกหน้า/ดึงหน้า · คำนวณผ่อน/สินเชื่อ · ชุดคำนวณ (BMI/ส่วนลด/VAT/%/ทิป) ·
สุ่มรายชื่อ/วงล้อ/เลข/ลูกเต๋า · สร้างรหัสผ่าน · อายุ/นับวัน/พ.ศ.-ค.ศ. · แปลงหน่วย/อุณหภูมิ/สกุลเงิน ·
อ่านเงินเป็นบาทถ้วน · คำนวณภาษีเงินได้ · ชุดเครื่องมือข้อความ

---

## รันได้ 2 แบบ (ทั้งคู่ไม่ใช้ฐานข้อมูล)

### 1) แบบ static — โฮสต์ฟรีบน GitHub Pages (แนะนำสำหรับสาธารณะ)

ไฟล์ static อยู่ในโฟลเดอร์ **`docs/`** (HTML/CSS/JS ล้วน) เปิดที่ไหนก็ได้

**สร้าง/อัปเดตไฟล์ static:** (เปิด Apache ของ XAMPP ไว้ ไม่ต้องเปิด MySQL)
```bash
node build-static.js
```
สคริปต์จะดึงหน้าเว็บที่ PHP render แล้ว post-process เป็น static ลง `docs/` (ลิงก์เป็น `.html`,
สกุลเงินดึงตรงจาก `frankfurter.dev`)

**เปิด GitHub Pages:** repo → Settings → Pages → Source: *Deploy from a branch* →
Branch `master` / folder `/docs` → Save → เว็บออนไลน์ที่ `https://<user>.github.io/<repo>/`

**ทดสอบในเครื่อง:** `cd docs && php -S 127.0.0.1:8899` แล้วเปิด `http://127.0.0.1:8899/`

### 2) แบบ PHP บน XAMPP (ไม่ต้องใช้ MySQL)

วางโปรเจกต์ใน `htdocs` แล้วเปิด **Apache อย่างเดียว** (ไม่ต้องเปิด MySQL) →
เข้าที่ `http://localhost/qrcode/` ใช้งานได้ทุกเครื่องมือ

> หน้าเว็บใช้เชลล์ PHP ร่วมกัน (`includes/header.php` / `footer.php`) อ่านรายการเครื่องมือจาก
> `includes/tools.php` — เพิ่มเครื่องมือใหม่ = ต่อ 1 รายการในไฟล์นั้น

---

## โครงสร้างไฟล์

```
index.php                 หน้า Hub (การ์ดเครื่องมือ + ค้นหา)
<tool>.php                หน้าเครื่องมือแต่ละตัว (qr, convert, pdf, ...)
config.php                ตั้งค่า APP_NAME / BASE_URL (ไม่มี DB)
includes/                 auth (session shell, ไม่มี DB), helpers, header, footer, tools (registry)
assets/css, assets/js     สไตล์ + ตรรกะเครื่องมือ (ฝั่ง client)
assets/vendor/            ไลบรารีในเครื่อง: qr-code-styling, pdf-lib, jszip
build-static.js           สคริปต์สร้างเว็บ static ลง docs/
docs/                     ผลลัพธ์ static (สำหรับ GitHub Pages)
```

## หมายเหตุ

- **สกุลเงิน** ดึงอัตราสดจาก `frankfurter.dev` (ฟรี ไม่ต้องคีย์) เพื่ออ้างอิงเท่านั้น
- **QR พร้อมเพย์** สร้าง payload ตามมาตรฐาน EMVCo/PromptPay + CRC16 — ควรลองสแกนด้วยแอปธนาคารจริง
  ก่อนใช้รับเงินจริง
- **ภาษีเงินได้** เป็นการประมาณเบื้องต้นตามอัตราขั้นบันไดปัจจุบัน — ตรวจสอบกับกรมสรรพากรก่อนยื่นจริง
