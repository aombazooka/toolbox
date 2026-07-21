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
];
