<?php
// ============================================================
//  QR Studio — Configuration
// ============================================================

// --- Database (XAMPP defaults: user "root", empty password) ---
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'qrcode_studio');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- Base URL of this app ---
// Used to build DYNAMIC QR redirect links (BASE_URL/r.php?c=CODE).
// IMPORTANT: change this when you move the app to a real server,
// otherwise already-printed dynamic QR codes point to the wrong host.
// No trailing slash.
define('BASE_URL', 'http://localhost/qrcode');

// --- App ---
define('APP_NAME', 'QR Studio');
define('APP_TZ', 'Asia/Bangkok');
