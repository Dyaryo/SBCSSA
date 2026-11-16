/**
 * POS terminal helpers: the quick-amount keypad, the side buttons, and the
 * keyboard-first window (TAB to the amount box, F2 Meron, F3 Wala, F4 Draw).
 * Dependency-free so a LAN terminal with no internet still works.
 */
(function () {
  'use strict';

  function initAmountGrids(scope) {
    (scope || document).querySelectorAll('[data-amount-grid]').forEach(function (grid) {
      var form = grid.closest('form');
      var input = form ? form.querySelector('input[name="amount"]') : null;

      if (!input) {
        return;
      }

      grid.querySelectorAll('[data-amount]').forEach(function (button) {
        button.addEventListener('click', function () {
          input.value = button.getAttribute('data-amount');

          grid.querySelectorAll('[data-amount]').forEach(function (other) {
            other.classList.toggle('active', other === button);
          });

          input.focus();
        });
      });

      input.addEventListener('input', function () {
        grid.querySelectorAll('[data-amount]').forEach(function (other) {
          other.classList.toggle('active', other.getAttribute('data-amount') === input.value);
        });
      });
    });
  }

  initAmountGrids(document);

  /* ---------- bet accepted animation ---------- */
  var success = document.querySelector('[data-bet-success]');

  if (success) {
    window.setTimeout(function () {
      success.classList.add('is-leaving');
    }, 2400);

    window.setTimeout(function () {
      if (success.parentNode) {
        success.parentNode.removeChild(success);
      }
    }, 2900);
  }

  /* ---------- auto-print the receipt ---------- */
  // A bet is printed the moment it is taken. The print happens inside a hidden
  // frame so the terminal stays on the betting screen.

  var autoPrint = document.querySelector('[data-auto-print]');

  if (autoPrint) {
    var frame = document.createElement('iframe');
    frame.setAttribute('aria-hidden', 'true');
    frame.style.position = 'fixed';
    frame.style.width = '0';
    frame.style.height = '0';
    frame.style.border = '0';
    frame.style.visibility = 'hidden';
    frame.src = autoPrint.getAttribute('data-auto-print');

    frame.addEventListener('load', function () {
      window.setTimeout(function () {
        try {
          frame.contentWindow.focus();
          frame.contentWindow.print();
        } catch (e) {
          // popup/print blocked — the slip is still on the bet history
        }
      }, 250);
    });

    document.body.appendChild(frame);

    window.setTimeout(function () {
      if (frame.parentNode) {
        frame.parentNode.removeChild(frame);
      }
    }, 60000);
  }

  /* ---------- live window feed ---------- */
  // The Game Master opens and closes fights all night. Poll a cheap signature
  // so the window updates itself; if it reloads, the amounts already typed are
  // put back so the teller does not lose the slip they were entering.

  var live = document.querySelector('[data-pos-live]');
  var AMOUNTS_KEY = 'esabong.pos.amounts';
  var RELOAD_KEY = 'esabong.pos.reloaded';

  function readAmounts() {
    var amounts = {};

    document.querySelectorAll('.pos-bet-form').forEach(function (form) {
      var id = form.querySelector('input[name="fight_id"]');
      var amount = form.querySelector('input[name="amount"]');

      if (id && amount && amount.value) {
        amounts[id.value] = amount.value;
      }
    });

    return amounts;
  }

  function restoreAmounts(saved) {
    document.querySelectorAll('.pos-bet-form').forEach(function (form) {
      var id = form.querySelector('input[name="fight_id"]');
      var amount = form.querySelector('input[name="amount"]');

      if (id && amount && saved[id.value]) {
        amount.value = saved[id.value];
        amount.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  }

  if (live) {
    try {
      if (window.sessionStorage.getItem(RELOAD_KEY) === '1') {
        restoreAmounts(JSON.parse(window.sessionStorage.getItem(AMOUNTS_KEY) || '{}'));
        window.sessionStorage.removeItem(RELOAD_KEY);
        window.sessionStorage.removeItem(AMOUNTS_KEY);
      }
    } catch (e) {
      // storage disabled — polling still works, amounts just won't persist
    }

    var signature = live.getAttribute('data-fight-signature') || live.getAttribute('data-signature');
    var feedUrl = live.getAttribute('data-feed-url');
    var windowUrl = live.getAttribute('data-window-url');
    var shiftId = live.getAttribute('data-shift-id') || '';
    var stack = document.querySelector('[data-pos-fight-stack]');
    var swapping = false;

    // A new fight does not reload the terminal: the old cards slide out, the
    // fresh cards slide in, and amounts already typed are put back.
    var swapWindow = function () {
      if (!stack || !windowUrl || swapping) {
        return;
      }

      swapping = true;
      var amounts = readAmounts();
      stack.classList.add('is-swapping');

      fetch(windowUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (fresh) {
          if (!fresh || typeof fresh.html !== 'string') {
            throw new Error('window unavailable');
          }

          window.setTimeout(function () {
            stack.innerHTML = fresh.html;
            restoreAmounts(amounts);
            initAmountGrids(stack);
            signature = fresh.fight_signature || fresh.signature || signature;
            stack.classList.remove('is-swapping');
            stack.classList.add('is-entering');

            window.requestAnimationFrame(function () {
              window.requestAnimationFrame(function () {
                stack.classList.remove('is-entering');
              });
            });

            swapping = false;
          }, 170);
        })
        .catch(function () {
          swapping = false;
          stack.classList.remove('is-swapping');
          window.location.reload();
        });
    };

    var poll = function () {
      if (document.hidden || !feedUrl) {
        return;
      }

      fetch(feedUrl, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
          if (!data) {
            return;
          }

          // Opening or closing the shift changes the whole terminal.
          if (String(data.shift_id || '') !== String(shiftId)) {
            window.location.reload();
            return;
          }

          var next = data.fight_signature || data.signature;
          if (!next || next === signature) {
            return;
          }

          if (stack && windowUrl) {
            swapWindow();
            return;
          }

          try {
            window.sessionStorage.setItem(AMOUNTS_KEY, JSON.stringify(readAmounts()));
            window.sessionStorage.setItem(RELOAD_KEY, '1');
          } catch (e) {
            // storage disabled — just refresh
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
  }

  /* ---------- keyboard window ---------- */

  var SIDE_KEYS = { F2: 'red', F3: 'blue', F4: 'draw' };
  var lastForm = null;

  function formFor(target) {
    if (target && target.closest) {
      var own = target.closest('.pos-bet-form');
      if (own) {
        return own;
      }
    }

    if (lastForm && document.contains(lastForm)) {
      return lastForm;
    }

    return document.querySelector('.pos-bet-form');
  }

  document.addEventListener('focusin', function (event) {
    var form = event.target.closest ? event.target.closest('.pos-bet-form') : null;

    if (form) {
      lastForm = form;
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.ctrlKey || event.altKey || event.metaKey) {
      return;
    }

    var form = formFor(event.target);
    if (!form) {
      return;
    }

    var amount = form.querySelector('input[name="amount"]');
    if (!amount || amount.disabled) {
      return;
    }

    // TAB means "go to the blank amount box", not "walk the page".
    if (event.key === 'Tab') {
      if (document.activeElement === amount) {
        return; // already typing an amount — let Tab move on normally
      }

      event.preventDefault();
      amount.focus();
      amount.select();
      return;
    }

    var side = SIDE_KEYS[event.key];
    if (!side) {
      return;
    }

    event.preventDefault();

    if (!amount.value || Number(amount.value) <= 0) {
      amount.focus();
      amount.select();
      return;
    }

    var button = form.querySelector('button[name="side"][value="' + side + '"]');
    if (button) {
      button.click();
    }
  });
})();
