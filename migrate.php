<?php
// ============================================================
//  QR Studio — schema migration runner (admin only)
//  Safe to re-run: every migration below checks information_schema
//  before altering anything, so running this page twice never errors.
//  Future DB changes: add the DDL to schema.sql (idempotent, for fresh
//  installs) AND append a migration entry to the $migrations list below
//  (for installs that already exist).
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_login();

$active     = '';
$page_title = 'อัปเดตฐานข้อมูล';
$page_js    = [];
$csrf       = csrf_token();
$u          = current_user();

if (($u['role'] ?? '') !== 'admin') {
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="page-head"><h1>อัปเดตฐานข้อมูล</h1></div>
    <div class="auth-msg err" style="max-width:520px">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
      ต้องเป็นผู้ดูแลระบบ
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

/** Does a column already exist on a table? */
function col_exists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn() > 0;
}

/** Does an index already exist on a table? */
function index_exists(PDO $pdo, string $table, string $index): bool {
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute([$table, $index]);
    return (int)$st->fetchColumn() > 0;
}

/** Does a table already exist? (mostly informational — CREATE TABLE IF NOT EXISTS
 *  already makes table-creation migrations idempotent on its own) */
function table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}

/**
 * Ordered list of migrations. Each item:
 *   name  — Thai label shown in the report
 *   check → PDO -> bool   already applied?  (true = skip)
 *   apply → PDO -> void   the DDL to run when not yet applied
 * Append new items to the end; never remove or reorder existing ones.
 */
$migrations = [
    [
        'name'  => 'เพิ่มดัชนี idx_scan_time บนตาราง scans(scanned_at)',
        'check' => fn(PDO $pdo) => index_exists($pdo, 'scans', 'idx_scan_time'),
        'apply' => fn(PDO $pdo) => $pdo->exec('ALTER TABLE scans ADD INDEX idx_scan_time (scanned_at)'),
    ],
    [
        'name'  => 'สร้างตาราง login_attempts (จำกัดการล็อกอินผิดซ้ำ)',
        'check' => fn(PDO $pdo) => table_exists($pdo, 'login_attempts'),
        'apply' => fn(PDO $pdo) => $pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
              id           INT AUTO_INCREMENT PRIMARY KEY,
              ip_hash      VARCHAR(64) NULL,
              username     VARCHAR(64) NULL,
              success      TINYINT(1) NOT NULL DEFAULT 0,
              attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_la_ip (ip_hash, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ),
    ],
    [
        'name'  => 'เติมหมวดหมู่เริ่มต้นให้ผู้ใช้ที่ยังไม่มีหมวดหมู่เลย',
        // Skip (already applied) when no user has zero categories.
        'check' => fn(PDO $pdo) => (int)$pdo->query(
            'SELECT COUNT(*) FROM users u
             WHERE NOT EXISTS (SELECT 1 FROM categories c WHERE c.user_id = u.id)'
        )->fetchColumn() === 0,
        'apply' => function (PDO $pdo) {
            $userIds = $pdo->query(
                'SELECT u.id FROM users u
                 WHERE NOT EXISTS (SELECT 1 FROM categories c WHERE c.user_id = u.id)'
            )->fetchAll(PDO::FETCH_COLUMN);
            $ins = $pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?,?,?)');
            foreach ($userIds as $targetUserId) {
                foreach (default_categories() as $catName => $catColor) {
                    $ins->execute([(int)$targetUserId, $catName, $catColor]);
                }
            }
        },
    ],
    [
        'name'  => 'สร้างตาราง templates (เทมเพลตสไตล์ที่บันทึกไว้)',
        'check' => fn(PDO $pdo) => table_exists($pdo, 'templates'),
        'apply' => fn(PDO $pdo) => $pdo->exec(
            'CREATE TABLE IF NOT EXISTS templates (
              id         INT AUTO_INCREMENT PRIMARY KEY,
              user_id    INT NOT NULL,
              name       VARCHAR(100) NOT NULL,
              style_json MEDIUMTEXT NULL,
              logo_data  MEDIUMTEXT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_tpl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              INDEX idx_tpl_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ),
    ],
    [
        'name'  => 'สร้างตาราง audit_log (บันทึกการใช้งาน)',
        'check' => fn(PDO $pdo) => table_exists($pdo, 'audit_log'),
        'apply' => fn(PDO $pdo) => $pdo->exec(
            'CREATE TABLE IF NOT EXISTS audit_log (
              id         INT AUTO_INCREMENT PRIMARY KEY,
              user_id    INT NULL,
              action     VARCHAR(40) NOT NULL,
              entity     VARCHAR(40) NULL,
              entity_id  INT NULL,
              detail     VARCHAR(255) NULL,
              ip_hash    VARCHAR(64) NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_audit_time (created_at),
              INDEX idx_audit_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ),
    ],
    [
        'name'  => 'สร้างผู้ใช้ระบบ __guest__ (เจ้าของ QR ที่สร้างโดยไม่ล็อกอิน)',
        'check' => fn(PDO $pdo) => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE username = '__guest__'")->fetchColumn() > 0,
        'apply' => function (PDO $pdo) {
            $pdo->prepare("INSERT INTO users (username, password_hash, display_name, role) VALUES ('__guest__', ?, ?, 'staff')")
                ->execute([password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), 'ผู้ใช้ทั่วไป (ไม่ล็อกอิน)']);
            $gid = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO categories (user_id, name, color) VALUES (?,?,?)');
            foreach (default_categories() as $catName => $catColor) {
                $ins->execute([$gid, $catName, $catColor]);
            }
        },
    ],
    [
        'name'  => 'สร้างตาราง short_links (T1 — ย่อลิงก์ + นับคลิก)',
        'check' => fn(PDO $pdo) => table_exists($pdo, 'short_links'),
        'apply' => fn(PDO $pdo) => $pdo->exec(
            'CREATE TABLE IF NOT EXISTS short_links (
              id          INT AUTO_INCREMENT PRIMARY KEY,
              code        VARCHAR(32) NOT NULL UNIQUE,
              target_url  TEXT NOT NULL,
              user_id     INT NULL,
              clicks      INT NOT NULL DEFAULT 0,
              is_active   TINYINT(1) NOT NULL DEFAULT 1,
              expires_at  DATETIME NULL,
              created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              CONSTRAINT fk_short_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
              INDEX idx_short_user (user_id),
              INDEX idx_short_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ),
    ],
    [
        'name'  => 'สร้างตาราง short_clicks (T1 — log การคลิกลิงก์สั้น)',
        'check' => fn(PDO $pdo) => table_exists($pdo, 'short_clicks'),
        'apply' => fn(PDO $pdo) => $pdo->exec(
            'CREATE TABLE IF NOT EXISTS short_clicks (
              id         INT AUTO_INCREMENT PRIMARY KEY,
              link_id    INT NOT NULL,
              clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              ip_hash    VARCHAR(64) NULL,
              referer    VARCHAR(500) NULL,
              ua         VARCHAR(255) NULL,
              CONSTRAINT fk_short_click_link FOREIGN KEY (link_id) REFERENCES short_links(id) ON DELETE CASCADE,
              INDEX idx_short_click_link (link_id),
              INDEX idx_short_click_time (clicked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ),
    ],
];

$pdo = db();
$results = [];
foreach ($migrations as $m) {
    try {
        if (($m['check'])($pdo)) {
            $results[] = ['name' => $m['name'], 'status' => 'skip', 'error' => null];
        } else {
            ($m['apply'])($pdo);
            $results[] = ['name' => $m['name'], 'status' => 'ran', 'error' => null];
        }
    } catch (Throwable $ex) {
        $results[] = ['name' => $m['name'], 'status' => 'error', 'error' => $ex->getMessage()];
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>อัปเดตฐานข้อมูล</h1>
  <p>รันชุดคำสั่งปรับโครงสร้างฐานข้อมูล (migration) — รันซ้ำได้อย่างปลอดภัย รายการที่มีอยู่แล้วจะถูกข้าม</p>
</div>

<div class="hist-table" style="max-width:720px">
  <div class="hist-row head">
    <div>รายการ migration</div><div style="text-align:right">ผลลัพธ์</div>
  </div>
  <div>
    <?php foreach ($results as $r): ?>
      <div class="hist-row" style="grid-template-columns:1fr auto">
        <div><?= e($r['name']) ?>
          <?php if ($r['status'] === 'error'): ?>
            <div class="hint" style="color:var(--danger);margin-top:4px"><?= e($r['error']) ?></div>
          <?php endif; ?>
        </div>
        <div style="text-align:right">
          <?php if ($r['status'] === 'ran'): ?>
            <span class="badge">รันแล้ว</span>
          <?php elseif ($r['status'] === 'skip'): ?>
            <span class="badge soon">ข้าม (มีอยู่แล้ว)</span>
          <?php else: ?>
            <span class="badge soon" style="background:rgba(229,72,77,.12);color:var(--danger)">ผิดพลาด</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<p class="hint" style="margin-top:18px">
  <a class="btn btn-ghost" href="migrate.php">รันตรวจสอบอีกครั้ง</a>
  <a class="btn btn-ghost" href="index.php">กลับหน้าแรก</a>
</p>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
