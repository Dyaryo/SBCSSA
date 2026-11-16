/**
 * Event form preview: the TV box mirrors the chosen share and the credits that
 * share would put on the arena board.
 */
(function () {
  'use strict';

  var box = document.querySelector('[data-tv-preview]');

  if (!box) {
    return;
  }

  var pool = parseFloat(box.getAttribute('data-pool')) || 0;
  var percentOut = box.querySelector('[data-tv-percent]');
  var stakeOut = box.querySelector('[data-tv-stake]');

  function update() {
    var checked = document.querySelector('input[name="tv_total_bets"]:checked');

    if (!checked) {
      return;
    }

    var percent = parseInt(checked.value, 10) || 0;

    if (percentOut) {
      percentOut.textContent = percent + '%';
    }

    if (stakeOut) {
      stakeOut.textContent = Math.round(pool * percent / 100).toLocaleString('en-US');
    }
  }

  document.querySelectorAll('input[name="tv_total_bets"]').forEach(function (radio) {
    radio.addEventListener('change', update);
  });

  update();
})();
