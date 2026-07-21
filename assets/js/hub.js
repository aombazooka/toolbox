/* ================= Toolbox — Hub search/filter ================= */
(function () {
  'use strict';
  var q = document.getElementById('hub-q');
  if (!q) return;

  var cats = Array.prototype.slice.call(document.querySelectorAll('.hub-cat'));
  var empty = document.getElementById('hub-empty');

  function apply() {
    var v = q.value.trim().toLowerCase();
    var anyVisible = false;

    cats.forEach(function (cat) {
      var cards = cat.querySelectorAll('.tool-card');
      var catHasVisible = false;
      cards.forEach(function (card) {
        var match = !v || (card.dataset.search || '').indexOf(v) !== -1;
        card.classList.toggle('hidden', !match);
        if (match) catHasVisible = true;
      });
      cat.classList.toggle('hidden', !catHasVisible);
      if (catHasVisible) anyVisible = true;
    });

    if (empty) empty.classList.toggle('hidden', anyVisible);
  }

  q.addEventListener('input', apply);
  apply();
})();
