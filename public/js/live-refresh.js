/**
 * Live screens: dashboards poll a cheap signature and reload themselves when
 * something moves — a bet, a shift, a fight opening or a result. No manual
 * refresh, no websocket to maintain on a LAN.
 */
(function () {
  'use strict';

  document.querySelectorAll('[data-live-refresh]').forEach(function (watch) {
    var signature = watch.getAttribute('data-signature');
    var feedUrl = watch.getAttribute('data-feed-url');

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
  });
})();
