/**
 * Arena board: ticking clock and a slow refresh so the wall screen keeps up
 * with the pit without anyone touching it.
 */
(function () {
  'use strict';

  var clock = document.querySelector('[data-board-clock]');

  function tick() {
    if (!clock) {
      return;
    }

    clock.textContent = new Date().toLocaleTimeString([], {
      hour: 'numeric',
      minute: '2-digit',
      second: '2-digit'
    });
  }

  tick();
  window.setInterval(tick, 1000);

  /* ---------- live board + big bet flash ---------- */
  // Public poll: the server sends a slip side only, never an amount, ticket,
  // teller or player. The same payload carries a state signature so a locked
  // side, new stake or result reaches the TV within a few seconds.
  var board = document.querySelector('[data-big-bet-feed]');
  var bigBetBusy = false;

  if (board) {
    var feed = board.getAttribute('data-big-bet-feed');
    var seen = board.getAttribute('data-big-bet-token') || '';
    var boardSignature = board.getAttribute('data-board-signature') || '';
    var pendingReload = false;

    var flash = function (side) {
      var target = side === 'draw'
        ? document.querySelector('.center .draw')
        : document.querySelector('.side.' + (side === 'red' ? 'meron' : 'wala'));

      if (!target) {
        return;
      }

      target.classList.remove('is-big-bet');
      void target.offsetWidth;
      target.classList.add('is-big-bet');
      bigBetBusy = true;

      window.setTimeout(function () {
        target.classList.remove('is-big-bet');
        bigBetBusy = false;

        if (pendingReload) {
          window.location.reload();
        }
      }, 2500);
    };

    var pollBigBet = function () {
      if (document.hidden || !feed) {
        return;
      }

      fetch(feed, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
          if (!data) {
            return;
          }

          var stateChanged = data.signature && data.signature !== boardSignature;

          if (data.token && data.token !== seen) {
            seen = data.token;

            if (stateChanged) {
              pendingReload = true;
            }

            flash(data.side);
            return;
          }

          if (stateChanged) {
            if (bigBetBusy) {
              pendingReload = true;
              return;
            }

            window.location.reload();
          }
        })
        .catch(function () {
          // a dropped poll is harmless; the next one retries
        });
    };

    window.setInterval(pollBigBet, 3000);
  }

  window.setInterval(function () {
    // Do not cut a big-bet flash off with a reload.
    if (!document.hidden && !bigBetBusy) {
      window.location.reload();
    }
  }, 15000);
})();
