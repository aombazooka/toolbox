<?php
// ============================================================
//  QR Studio — saved style templates API (list / get / add / delete)
//  A template captures the visual style (colours, dot type, EC,
//  logo size, output size) and optionally a centre logo, so it can
//  be re-applied to a new QR later.
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        $st = db()->prepare("SELECT id, name, style_json, logo_data FROM templates WHERE id = ? AND user_id = ? LIMIT 1");
        $st->execute([$id, uid()]);
        $row = $st->fetch();
        if (!$row) json_out(['ok' => false, 'error' => 'ไม่พบเทมเพลต'], 404);

        json_out([
            'ok'    => true,
            'item'  => [
                'id'        => (int)$row['id'],
                'name'      => $row['name'],
                'style'     => $row['style_json'] ? json_decode($row['style_json'], true) : null,
                'logo_data' => $row['logo_data'],
            ],
        ]);
    }

    $st = db()->prepare("SELECT id, name, style_json, logo_data FROM templates WHERE user_id = ? ORDER BY id DESC");
    $st->execute([uid()]);
    $items = [];
    foreach ($st as $r) {
        $items[] = [
            'id'       => (int)$r['id'],
            'name'     => $r['name'],
            'style'    => $r['style_json'] ? json_decode($r['style_json'], true) : null,
            'has_logo' => $r['logo_data'] !== null,
        ];
    }
    json_out(['ok' => true, 'items' => $items]);
}

if ($method !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า'], 400);

$action = $in['action'] ?? '';

if ($action === 'add') {
    $name = trim($in['name'] ?? '');
    if ($name === '') json_out(['ok' => false, 'error' => 'กรุณากรอกชื่อเทมเพลต'], 422);
    if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);

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

    $ins = db()->prepare("INSERT INTO templates (user_id, name, style_json, logo_data) VALUES (?,?,?,?)");
    $ins->execute([uid(), $name, $style, $logo]);

    json_out(['ok' => true, 'item' => ['id' => (int)db()->lastInsertId(), 'name' => $name]]);
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 422);

    $del = db()->prepare("DELETE FROM templates WHERE id = ? AND user_id = ?");
    $del->execute([$id, uid()]);
    if ($del->rowCount() === 0) json_out(['ok' => false, 'error' => 'ไม่พบรายการ'], 404);

    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'ไม่รู้จักคำสั่ง'], 400);
