<?php
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า'], 400);

// Privacy: guests (not logged in) may create + download QR codes entirely client-side,
// but nothing is ever persisted for them — this endpoint is a no-op for guests so no
// row is ever written to `qrcodes` under the "__guest__" user.
// (Matches assets/js/create.js, which now skips calling this endpoint when isGuest.)
if (!is_logged_in()) {
    json_out(['ok' => true, 'id' => null, 'short_code' => null, 'dynamic_link' => null]);
}

$name = trim($in['name'] ?? '');
if ($name === '') $name = 'ไม่มีชื่อ';
if (mb_strlen($name) > 200) $name = mb_substr($name, 0, 200);

$dest = trim($in['destination_url'] ?? '');
if ($dest === '') json_out(['ok' => false, 'error' => 'กรุณากรอกลิงก์ปลายทาง'], 422);
if (mb_strlen($dest) > 2000) json_out(['ok' => false, 'error' => 'ลิงก์ยาวเกินไป'], 422);

$type     = (($in['type'] ?? 'static') === 'dynamic') ? 'dynamic' : 'static';
$category = trim($in['category'] ?? '');
$category = $category !== '' ? $category : null;

$style = (isset($in['style_json']) && is_array($in['style_json']))
    ? json_encode($in['style_json'], JSON_UNESCAPED_UNICODE) : null;

$logo = $in['logo_data'] ?? null;
if ($logo !== null) {
    if (!is_string($logo) || strncmp($logo, 'data:image/', 11) !== 0) {
        $logo = null;
    } elseif (strlen($logo) > 3 * 1024 * 1024) {
        json_out(['ok' => false, 'error' => 'โลโก้ใหญ่เกินไป'], 422);
    }
}

/* expires_at: empty/null -> NULL, otherwise parse to DATETIME (reject if unparseable) */
$expiresAt = null;
if (array_key_exists('expires_at', $in) && $in['expires_at'] !== null && trim((string)$in['expires_at']) !== '') {
    $ts = strtotime((string)$in['expires_at']);
    if ($ts === false) json_out(['ok' => false, 'error' => 'วันหมดอายุไม่ถูกต้อง'], 422);
    $expiresAt = date('Y-m-d H:i:s', $ts);
}

/* ---- update path: id present → edit an existing record in place (login required — already guaranteed above) ---- */
$editId = (int)($in['id'] ?? 0);
if ($editId > 0) {
    // Admins may edit any user's QR; staff stays restricted to their own.
    if (is_admin()) {
        $st = db()->prepare("SELECT id, type, short_code FROM qrcodes WHERE id = ? LIMIT 1");
        $st->execute([$editId]);
    } else {
        $st = db()->prepare("SELECT id, type, short_code FROM qrcodes WHERE id = ? AND user_id = ? LIMIT 1");
        $st->execute([$editId, uid()]);
    }
    $existing = $st->fetch();
    if (!$existing) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 404);

    // type and short_code are never changed here: printed dynamic QRs must keep resolving.
    if (is_admin()) {
        $upd = db()->prepare(
            "UPDATE qrcodes SET name = ?, category = ?, destination_url = ?, style_json = ?, logo_data = ?, expires_at = ?
             WHERE id = ?"
        );
        $upd->execute([$name, $category, $dest, $style, $logo, $expiresAt, $editId]);
    } else {
        $upd = db()->prepare(
            "UPDATE qrcodes SET name = ?, category = ?, destination_url = ?, style_json = ?, logo_data = ?, expires_at = ?
             WHERE id = ? AND user_id = ?"
        );
        $upd->execute([$name, $category, $dest, $style, $logo, $expiresAt, $editId, uid()]);
    }

    log_audit('qr_update', 'qrcode', $editId, $name);
    json_out([
        'ok'           => true,
        'id'           => $editId,
        'short_code'   => $existing['short_code'],
        'dynamic_link' => $existing['short_code'] ? dynamic_link($existing['short_code']) : null,
    ]);
}

$code = null;
if ($type === 'dynamic') {
    $code = preg_replace('/[^A-Za-z0-9]/', '', (string)($in['short_code'] ?? ''));
    if (strlen($code) < 5) $code = generate_short_code();
}

// Owner: always the logged-in user — guests never reach this point (see guard above).
$ownerId = uid();

$sql = "INSERT INTO qrcodes (user_id, name, category, type, destination_url, short_code, style_json, logo_data, expires_at)
        VALUES (?,?,?,?,?,?,?,?,?)";
$attempts = 0;
while (true) {
    try {
        db()->prepare($sql)->execute([$ownerId, $name, $category, $type, $dest, $code, $style, $logo, $expiresAt]);
        break;
    } catch (PDOException $ex) {
        if ($type === 'dynamic' && $ex->getCode() === '23000' && $attempts < 5) {
            $code = generate_short_code();
            $attempts++;
            continue;
        }
        json_out(['ok' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
    }
}

$id = (int)db()->lastInsertId();
log_audit('qr_create', 'qrcode', $id, $name);
json_out([
    'ok'           => true,
    'id'           => $id,
    'short_code'   => $code,
    'dynamic_link' => $code ? dynamic_link($code) : null,
]);
