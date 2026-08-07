<?php
// ============================================================
//  QR Studio — Configuration
// ============================================================

// --- No database ---
// This build is a fully client-side toolbox; there is no DB connection.

// --- Base URL of this app ---
// Used to build DYNAMIC QR redirect links (BASE_URL/r.php?c=CODE).
// IMPORTANT: change this when you move the app to a real server,
// otherwise already-printed dynamic QR codes point to the wrong host.
// No trailing slash.
define('BASE_URL', 'http://localhost/qrcode');

// --- App ---
define('APP_NAME', 'Toolbox');
define('APP_TZ', 'Asia/Bangkok');
