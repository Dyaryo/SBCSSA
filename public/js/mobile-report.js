/**
 * Mobile report app: registers the service worker for install/offline shell and
 * wires the Android install button. iOS uses Share → Add to Home Screen.
 */
(function () {
  'use strict';

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {
        // HTTPS or localhost is required; the page still works without it.
      });
    });
  }

  var install = document.querySelector('[data-install-app]');
  var hint = document.querySelector('[data-install-hint]');
  var deferred = null;

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferred = event;

    if (install) {
      install.hidden = false;
    }

    if (hint) {
      hint.hidden = true;
    }
  });

  if (install) {
    install.addEventListener('click', function () {
      if (!deferred) {
        return;
      }

      deferred.prompt();
      deferred.userChoice.then(function () {
        deferred = null;
      });
    });
  }

  window.addEventListener('appinstalled', function () {
    if (install) {
      install.hidden = true;
    }
  });
})();
