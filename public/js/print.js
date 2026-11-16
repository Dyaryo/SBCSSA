(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-print]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        window.print();
    });

    if (new URLSearchParams(window.location.search).get('auto') === '1') {
        window.addEventListener('load', function () {
            window.setTimeout(function () { window.print(); }, 300);
        });
    }
})();
