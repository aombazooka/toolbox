<?php
// ============================================================
//  QR Studio — audit log viewer (admin only)
// ============================================================
require_once __DIR__ . '/includes/auth.php';
require_admin();

$active     = '';
$page_title = 'บันทึกการใช้งาน';
$page_js    = [];
$csrf       = csrf_token();

/** Thai label for each known audit action. Unknown actions fall back to the raw code. */
function audit_action_label(string $action): string {
    static $labels = [
        'login'        => 'เข้าสู่ระบบ',
        'login_failed' => 'เข้าสู่ระบบไม่สำเร็จ',
        'qr_create'    => 'สร้าง QR',
        'qr_update'    => 'แก้ไข QR',
        'qr_delete'    => 'ลบ QR',
        'user_add'     => 'เพิ่มผู้ใช้',
        'user_delete'  => 'ลบผู้ใช้',
        'user_role'    => 'เปลี่ยนสิทธิ์ผู้ใช้',
        'register'     => 'สมัครสมาชิก',
    ];
    return $labels[$action] ?? $action;
}

$knownActions = ['login', 'login_failed', 'register', 'qr_create', 'qr_update', 'qr_delete', 'user_add', 'user_delete', 'user_role'];
$filterAction = trim($_GET['action'] ?? '');
if (!in_array($filterAction, $knownActions, true)) $filterAction = '';

$rows       = [];
$loadError  = '';
try {
    if ($filterAction !== '') {
        $st = db()->prepare(
            "SELECT a.*, u.display_name, u.username
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.action = ?
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT 200"
        );
        $st->execute([$filterAction]);
    } else {
        $st = db()->query(
            "SELECT a.*, u.display_name, u.username
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT 200"
        );
    }
    $rows = $st->fetchAll();
} catch (Throwable $ex) {
    $loadError = 'ยังไม่พบตาราง audit_log — กรุณารัน "อัปเดตฐานข้อมูล" ก่อน';
}

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <h1>บันทึกการใช้งาน</h1>
  <p>ประวัติการเข้าสู่ระบบและการเปลี่ยนแปลงข้อมูลในระบบ 200 รายการล่าสุด — เฉพาะผู้ดูแลระบบเท่านั้น</p>
</div>

<?php if ($loadError): ?>
  <div class="auth-msg err" style="max-width:600px">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
    <?= e($loadError) ?>
  </div>
<?php else: ?>

  <form method="get" class="hist-toolbar" style="margin-bottom:16px">
    <select class="filter-sel" name="action" onchange="this.form.submit()">
      <option value="">ทุกการกระทำ</option>
      <?php foreach ($knownActions as $a): ?>
        <option value="<?= e($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= e(audit_action_label($a)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <div class="hist-table">
    <div class="hist-row head" style="grid-template-columns:150px 170px 150px 1fr">
      <div>เวลา</div><div>ผู้ใช้</div><div>การกระทำ</div><div>รายละเอียด</div>
    </div>
    <div>
      <?php if (!$rows): ?>
        <div class="hist-loading">ไม่มีข้อมูล</div>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <?php
          $ts     = strtotime($r['created_at']);
          $who    = $r['user_id'] ? ($r['display_name'] ?: $r['username'] ?: ('#' . $r['user_id'])) : '—';
          $detail = trim(
              ($r['entity'] ? $r['entity'] . ($r['entity_id'] ? ' #' . $r['entity_id'] : '') : '')
              . ($r['detail'] !== null && $r['detail'] !== '' ? ' — ' . $r['detail'] : '')
          );
        ?>
        <div class="hist-row" style="grid-template-columns:150px 170px 150px 1fr">
          <div><?= e(thai_date($r['created_at'])) ?> <?= e(date('H:i', $ts)) ?></div>
          <div><?= e($who) ?></div>
          <div><?= e(audit_action_label($r['action'])) ?></div>
          <div class="hint"><?= e($detail) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

<?php endif; ?>

<script>
window.APP = { baseUrl: <?= json_encode(rtrim(BASE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>, csrf: <?= json_encode($csrf) ?> };
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
