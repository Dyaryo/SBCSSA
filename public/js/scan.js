/**
 * Payout scanning: F9 jumps straight to the slip-code box so a handheld
 * scanner can type the code without the teller touching the mouse. If no scan
 * box is on the page, F9 opens the lookup screen instead.
 */
(function () {
  'use strict';

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'F9' || event.ctrlKey || event.altKey || event.metaKey) {
      return;
    }

    event.preventDefault();

    var input = document.querySelector('[data-scan-input]');

    if (input) {
      input.focus();
      input.select();
      return;
    }

    var holder = document.querySelector('[data-scan-url]');

    if (holder) {
      window.location.href = holder.getAttribute('data-scan-url');
    }
  });
})();
