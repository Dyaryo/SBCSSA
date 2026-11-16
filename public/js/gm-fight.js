/**
 * Fight console refresh: while the betting window is open the pit should see
 * tellers' bets land without pressing refresh. The page polls a cheap
 * signature and reloads itself when the fight, its bets or its locks change.
 */
(function () {
  'use strict';

  var consoleBlock = document.querySelector('[data-gm-fight]');

  if (!consoleBlock) {
    return;
  }

  var signature = consoleBlock.getAttribute('data-signature');
  var feedUrl = consoleBlock.getAttribute('data-feed-url');

  if (!feedUrl) {
    return;
  }

  var poll = function () {
    if (document.hidden) {
      return;
    }

    fetch(feedUrl, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (response) { return response.ok ? response.json() : null; })
      .then(function (data) {
        if (!data || !data.signature || data.signature === signature) {
          return;
        }

        window.location.reload();
      })
      .catch(function () {
        // a dropped poll is harmless; the next one retries
      });
  };

  window.setInterval(poll, 4000);

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      poll();
    }
  });
})();
