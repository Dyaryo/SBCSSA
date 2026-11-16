/**
 * POS console helpers. Deliberately dependency-free so the system runs on a
 * LAN with no internet access.
 */
(function () {
  'use strict';

  var DEVICE_KEY = 'esabong.device.uuid';

  function uuid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }

    // Fallback for older browsers on Windows terminals.
    return 'dev-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
  }

  function deviceId() {
    try {
      var existing = window.localStorage.getItem(DEVICE_KEY);

      if (!existing) {
        existing = uuid();
        window.localStorage.setItem(DEVICE_KEY, existing);
      }

      return existing;
    } catch (e) {
      return 'unavailable';
    }
  }

  var id = deviceId();

  document.querySelectorAll('[data-device-uuid]').forEach(function (input) {
    input.value = id;
  });

  // Show the fingerprint so a supervisor can read it out over the radio.
  document.querySelectorAll('[data-device-label]').forEach(function (el) {
    el.textContent = id.slice(0, 18) + '…';
  });

  /* ---------- confirmation dialog ---------- */
  // Money moves on these buttons, so the last step is a proper card instead of
  // the browser's grey alert. Keyboard friendly: Enter confirms, Esc cancels.

  var dialog = null;
  var pendingForm = null;
  var pendingSubmitter = null;
  var lastFocus = null;

  function buildDialog() {
    if (dialog) {
      return dialog;
    }

    dialog = document.createElement('div');
    dialog.className = 'confirm-backdrop';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'confirm-title');
    dialog.innerHTML =
      '<div class="confirm-card" role="document">' +
        '<div class="confirm-icon" aria-hidden="true">!</div>' +
        '<p class="confirm-kicker" id="confirm-title">Confirm action</p>' +
        '<p class="confirm-message"></p>' +
        '<div class="confirm-actions">' +
          '<button type="button" class="btn btn-ghost confirm-cancel">Cancel</button>' +
          '<button type="button" class="btn btn-primary confirm-go">Confirm</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(dialog);

    dialog.querySelector('.confirm-cancel').addEventListener('click', closeDialog);
    dialog.querySelector('.confirm-go').addEventListener('click', function () {
      var form = pendingForm;
      var submitter = pendingSubmitter;

      closeDialog();

      if (form) {
        form.dataset.confirmed = '1';

        // Re-click the button that was pressed so its name/value (for example
        // result=meron) is still part of the submission.
        if (submitter && form.contains(submitter)) {
          submitter.click();
        } else if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }

        window.setTimeout(function () {
          delete form.dataset.confirmed;
        }, 0);
      }
    });

    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) {
        closeDialog();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (!dialog.classList.contains('is-open')) {
        return;
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        closeDialog();
      } else if (event.key === 'Enter') {
        event.preventDefault();
        dialog.querySelector('.confirm-go').click();
      }
    });

    return dialog;
  }

  function openDialog(form, message, tone, submitter) {
    buildDialog();

    lastFocus = document.activeElement;
    pendingForm = form;
    pendingSubmitter = submitter || null;

    dialog.querySelector('.confirm-message').textContent = message;
    dialog.classList.toggle('is-danger', tone === 'danger');
    dialog.classList.add('is-open');

    window.setTimeout(function () {
      dialog.querySelector('.confirm-cancel').focus();
    }, 30);
  }

  function closeDialog() {
    if (!dialog) {
      return;
    }

    dialog.classList.remove('is-open');
    pendingForm = null;
    pendingSubmitter = null;

    if (lastFocus && document.contains(lastFocus)) {
      lastFocus.focus();
    }
  }

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.confirmed === '1') {
        return;
      }

      event.preventDefault();

      var tone = form.getAttribute('data-confirm-tone') ||
        (/cannot be undone|irreversible|final|void|revoke|suspend/i.test(form.getAttribute('data-confirm')) ? 'danger' : 'normal');

      openDialog(form, form.getAttribute('data-confirm'), tone, event.submitter);
    });
  });

  // Flash messages slide in with a tick so the result of an action reads as done.
  document.querySelectorAll('.flash.ok').forEach(function (flash) {
    flash.classList.add('flash-done');
  });

  // Account creation form: show only the fields for the selected role.
  var roleSelect = document.querySelector('[data-role-select]');

  if (roleSelect) {
    var toggle = function () {
      var role = roleSelect.value;
      document.querySelectorAll('[data-for-role]').forEach(function (el) {
        el.style.display = el.getAttribute('data-for-role') === role ? '' : 'none';
      });
    };

    roleSelect.addEventListener('change', toggle);
    toggle();
  }

  // Server clock in the footer.
  document.querySelectorAll('[data-clock]').forEach(function (el) {
    var tick = function () {
      el.textContent = new Date().toLocaleTimeString();
    };

    tick();
    window.setInterval(tick, 1000);
  });
})();
