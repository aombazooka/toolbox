<?php
/**
 * Toolbox — tool registry.
 *
 * Single source of truth for the Hub (index.php) and the header nav dropdown.
 * Append-only: each new tool ticket (T1–T10) adds ONE entry to this array —
 * do not reorder or rewrite existing entries, just append after the last one.
 *
 * Fields:
 *   slug  — unique short id, used for $active matching in includes/header.php
 *   title — short Thai name shown on the card / nav dropdown
 *   desc  — one-line Thai description shown on the card
 *   href  — relative link to the tool's page
 *   icon  — inline SVG markup (stroke 2px, matches the rest of the site)
 *   cat   — category heading on the Hub (one of the 4 groups below)
 *   login — true if the tool requires login to use at all (false = usable as guest)
 */
return [
    [
        'slug'  => 'qr',
        'title' => 'สร้าง QR Code',
        'desc'  => 'แปลงลิงก์เป็น QR โค้ด ใส่โลโก้ตรงกลาง ปรับสีและสไตล์ได้',
        'href'  => 'qr.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6z" stroke-linejoin="round"/><path d="M14 14h2.5v2.5H14V14zM17.5 17.5H20V20h-2.5v-2.5zM14 20h2.5M20 14v2.5" stroke-linecap="round"/></svg>',
        'cat'   => 'QR & ลิงก์',
        'login' => false,
    ],
    [
        'slug'  => 'shorten',
        'title' => 'ย่อลิงก์',
        'desc'  => 'ย่อลิงก์ยาวให้สั้น ตั้งชื่อเองได้ นับยอดคลิก และทำ QR ต่อได้ทันที',
        'href'  => 'shorten.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.07 0l1.93-1.93a5 5 0 00-7.07-7.07L10.5 5.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 00-7.07 0L5 12.93a5 5 0 007.07 7.07l1.49-1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'cat'   => 'QR & ลิงก์',
        'login' => false,
    ],
    [
        'slug'  => 'qr-presets',
        'title' => 'QR สำเร็จรูป',
        'desc'  => 'สร้าง QR สำหรับ WiFi, นามบัตร (vCard), อีเมล, SMS หรือเบอร์โทร ได้ทันที',
        'href'  => 'qr-presets.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="M6 16c.5-2 2-3 2.5-3s2 1 2.5 3M13 9h5M13 13h5" stroke-linecap="round"/></svg>',
        'cat'   => 'QR & ลิงก์',
        'login' => false,
    ],
    [
        'slug'  => 'image',
        'title' => 'ลดขนาด/แปลงรูปภาพ',
        'desc'  => 'ย่อขนาด บีบอัด และแปลงไฟล์ภาพ (JPG/PNG/WebP) หลายไฟล์พร้อมกัน ในเบราว์เซอร์ล้วน',
        'href'  => 'image.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'cat'   => 'รูปภาพ & PDF',
        'login' => false,
    ],
    [
        'slug'  => 'pdf',
        'title' => 'รูป→PDF / รวม / แยกหน้า',
        'desc'  => 'แปลงรูปเป็น PDF, รวมหลายไฟล์ PDF เป็นไฟล์เดียว หรือแยกหน้าที่ต้องการออกมา',
        'href'  => 'pdf.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linejoin="round"/><path d="M14 2v6h6" stroke-linejoin="round"/><path d="M8 13h8M8 17h5" stroke-linecap="round"/></svg>',
        'cat'   => 'รูปภาพ & PDF',
        'login' => false,
    ],
    [
        'slug'  => 'calc-loan',
        'title' => 'คำนวณผ่อน/สินเชื่อ',
        'desc'  => 'คำนวณค่างวดรายเดือน ดอกเบี้ยรวม และตารางผ่อนชำระ แบบลดต้นลดดอกหรือคงที่',
        'href'  => 'calc-loan.php',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18" stroke-linecap="round"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01" stroke-linecap="round"/></svg>',
        'cat'   => 'เครื่องคำนวณ',
        'login' => false,
    ],
];
