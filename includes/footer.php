</main>
<footer><?= e(APP_NAME) ?> · ระบบสร้างและจัดการ QR Code</footer>
<div class="toast" id="toast"></div>
<script>
/* ---- theme (persisted) ---- */
(function () {
  const root = document.documentElement, btn = document.getElementById('theme-btn');
  const sun  = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>';
  const moon = '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>';
  const icon = document.getElementById('theme-icon');
  function paint() { if (icon) icon.innerHTML = root.getAttribute('data-theme') === 'dark' ? moon : sun; }
  const saved = localStorage.getItem('qrs-theme');
  if (saved) root.setAttribute('data-theme', saved);
  paint();
  if (btn) btn.onclick = () => {
    const dark = root.getAttribute('data-theme') === 'dark';
    root.setAttribute('data-theme', dark ? 'light' : 'dark');
    localStorage.setItem('qrs-theme', dark ? 'light' : 'dark');
    paint();
  };
})();
/* ---- toast helper ---- */
window.toast = function (msg, type) {
  const t = document.getElementById('toast');
  const ok   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  const err  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>';
  t.className = 'toast show ' + (type || '');
  t.innerHTML = (type === 'err' ? err : ok) + '<span></span>';
  t.querySelector('span').textContent = msg;
  clearTimeout(window._toastT);
  window._toastT = setTimeout(() => t.classList.remove('show'), 2600);
};
</script>
<?php foreach ((array)($page_js ?? []) as $js): ?>
<script src="<?= e(asset_url('assets/js/' . $js)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
