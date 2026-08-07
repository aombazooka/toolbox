<?php
// Shared page shell. Expects (optional): $active, $page_title.
$u          = current_user();
$active     = $active ?? '';
$page_title = $page_title ?? APP_NAME;
$tools      = require __DIR__ . '/tools.php';
$toolSlugs  = array_column($tools, 'slug');
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> · <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/app-extra.css')) ?>">
<script src="<?= e(asset_url('assets/vendor/qr-code-styling.js')) ?>"></script>
</head>
<body>
<header>
  <div class="head-inner">
    <a class="brand" href="index.php">
      <div class="logo-mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M4 4h6v6H4V4zM14 4h6v6h-6V4zM4 14h6v6H4v-6z" stroke="#fff" stroke-width="2" stroke-linejoin="round"/><path d="M14 14h2.5v2.5H14V14zM17.5 17.5H20V20h-2.5v-2.5zM14 20h2.5M20 14v2.5" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div class="brand-name"><?= e(APP_NAME) ?><small>เครื่องมือออนไลน์ฟรี</small></div>
    </a>
    <nav>
      <a class="tab <?= $active === 'hub' ? 'active' : '' ?>" href="index.php">ทั้งหมด</a>
      <details class="nav-drop">
        <summary class="tab <?= in_array($active, $toolSlugs, true) ? 'active' : '' ?>">
          เครื่องมือ
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0"><path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>
        <div class="nav-drop-menu">
          <?php foreach ($tools as $t): ?>
          <a class="nav-drop-item" href="<?= e($t['href']) ?>"><?= $t['icon'] ?><span><?= e($t['title']) ?></span></a>
          <?php endforeach; ?>
          <a class="nav-drop-item nav-drop-all" href="index.php">ดูเครื่องมือทั้งหมด →</a>
        </div>
      </details>
    </nav>
    <div class="head-right">
      <button class="icon-btn" id="theme-btn" title="สลับธีม">
        <svg id="theme-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
    </div>
  </div>
</header>
<main>
