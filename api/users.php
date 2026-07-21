<?php
// ============================================================
//  QR Studio — user management API (admin only)
//  GET  → list all users
//  POST action=add            {username, display_name, password, role}
//  POST action=update         {id, display_name?, role?}
//  POST action=reset_password {id, password}
//  POST action=delete         {id}
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_admin_api();

$method = $_SERVER['REQUEST_METHOD'];

function user_row_out(array $r): array {
    return [
        'id'           => (int)$r['id'],
        'username'     => $r['username'],
        'display_name' => $r['display_name'],
        'role'         => $r['role'],
        'created_at'   => thai_date($r['created_at']),
    ];
}

if ($method === 'GET') {
    $st = db()->query("SELECT id, username, display_name, role, created_at FROM users ORDER BY id");
    $items = [];
    foreach ($st as $r) $items[] = user_row_out($r);
    json_out(['ok' => true, 'items' => $items]);
}

if ($method !== 'POST') json_out(['ok' => false, 'error' => 'method'], 405);

$in = json_input();
if (!csrf_check($in['csrf'] ?? '')) json_out(['ok' => false, 'error' => 'เซสชันหมดอายุ กรุณารีเฟรชหน้า'], 400);

$action = $in['action'] ?? '';
$pdo    = db();

if ($action === 'add') {
    $username = trim($in['username'] ?? '');
    $display  = trim($in['display_name'] ?? '');
    $password = (string)($in['password'] ?? '');
    $role     = $in['role'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{3,64}$/', $username)) {
        json_out(['ok' => false, 'error' => 'ชื่อผู้ใช้ใช้ได้เฉพาะ a-z, 0-9, _ . และยาว 3–64 ตัว'], 422);
    }
    if ($display === '') $display = $username;
    if (mb_strlen($password) < 6) {
        json_out(['ok' => false, 'error' => 'รหัสผ่านอย่างน้อย 6 ตัวอักษร'], 422);
    }
    if (!in_array($role, ['admin', 'staff'], true)) {
        json_out(['ok' => false, 'error' => 'สิทธิ์ไม่ถูกต้อง'], 422);
    }

    $dup = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $dup->execute([$username]);
    if ((int)$dup->fetchColumn() > 0) json_out(['ok' => false, 'error' => 'มีชื่อผู้ใช้นี้อยู่แล้ว'], 422);

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES (?,?,?,?)");
        $ins->execute([$username, password_hash($password, PASSWORD_DEFAULT), $display, $role]);
        $newId = (int)$pdo->lastInsertId();

        $catIns = $pdo->prepare("INSERT INTO categories (user_id, name, color) VALUES (?,?,?)");
        foreach (default_categories() as $catName => $catColor) {
            $catIns->execute([$newId, $catName, $catColor]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_out(['ok' => false, 'error' => 'สร้างผู้ใช้ไม่สำเร็จ'], 500);
    }

    log_audit('user_add', 'user', $newId, $username);

    $st = $pdo->prepare("SELECT id, username, display_name, role, created_at FROM users WHERE id = ?");
    $st->execute([$newId]);
    json_out(['ok' => true, 'item' => user_row_out($st->fetch())]);
}

if ($action === 'update') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 422);

    $st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $st->execute([$id]);
    $target = $st->fetch();
    if (!$target) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 404);

    $fields = [];
    $params = [];
    $newDisplay = $target['display_name'];
    $newRole    = $target['role'];

    if (array_key_exists('display_name', $in)) {
        $newDisplay = trim((string)$in['display_name']);
        if ($newDisplay === '') $newDisplay = $target['username'];
        if (mb_strlen($newDisplay) > 100) $newDisplay = mb_substr($newDisplay, 0, 100);
        $fields[] = 'display_name = ?';
        $params[] = $newDisplay;
    }

    if (array_key_exists('role', $in)) {
        $newRole = $in['role'];
        if (!in_array($newRole, ['admin', 'staff'], true)) {
            json_out(['ok' => false, 'error' => 'สิทธิ์ไม่ถูกต้อง'], 422);
        }
        if ($target['role'] === 'admin' && $newRole !== 'admin') {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($cnt <= 1) {
                json_out(['ok' => false, 'error' => 'ต้องมีผู้ดูแลระบบอย่างน้อย 1 คน ไม่สามารถลดสิทธิ์ผู้ดูแลระบบคนสุดท้ายได้'], 422);
            }
        }
        $fields[] = 'role = ?';
        $params[] = $newRole;
    }

    if (!$fields) json_out(['ok' => false, 'error' => 'ไม่มีข้อมูลที่จะแก้ไข'], 422);

    $params[] = $id;
    $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

    if (array_key_exists('role', $in)) {
        log_audit('user_role', 'user', $id, $newRole);
    }

    // Keep the current session in sync if the admin edited their own row.
    if ($id === uid()) {
        $_SESSION['user']['display_name'] = $newDisplay;
        $_SESSION['user']['role']         = $newRole;
    }

    $st2 = $pdo->prepare("SELECT id, username, display_name, role, created_at FROM users WHERE id = ?");
    $st2->execute([$id]);
    json_out(['ok' => true, 'item' => user_row_out($st2->fetch())]);
}

if ($action === 'reset_password') {
    $id       = (int)($in['id'] ?? 0);
    $password = (string)($in['password'] ?? '');
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 422);
    if (mb_strlen($password) < 6) json_out(['ok' => false, 'error' => 'รหัสผ่านอย่างน้อย 6 ตัวอักษร'], 422);

    $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $chk->execute([$id]);
    if ((int)$chk->fetchColumn() === 0) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 404);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
    json_out(['ok' => true]);
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 422);
    if ($id === uid()) json_out(['ok' => false, 'error' => 'ไม่สามารถลบบัญชีของตัวเองได้'], 422);

    $st = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $st->execute([$id]);
    $target = $st->fetch();
    if (!$target) json_out(['ok' => false, 'error' => 'ไม่พบผู้ใช้'], 404);

    if ($target['role'] === 'admin') {
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($cnt <= 1) json_out(['ok' => false, 'error' => 'ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้'], 422);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    log_audit('user_delete', 'user', $id);
    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'ไม่รู้จักคำสั่ง'], 400);
