// Event type switch: every event has a title; only a derby shows the entry
// names field. Progressive enhancement only — the server validates both.
document.querySelectorAll('input[data-event-type]').forEach(function (radio) {
    var form = radio.closest('form');
    if (!form) return;

    function sync() {
        var checked = form.querySelector('input[data-event-type]:checked');
        var derby = !!checked && checked.value === 'derby';

        form.querySelectorAll('[data-for-type="derby"]').forEach(function (field) {
            field.hidden = !derby;
            field.querySelectorAll('input, textarea').forEach(function (input) {
                input.required = derby && input.dataset.requiredForDerby !== undefined;
            });
        });
    }

    radio.addEventListener('change', sync);
    if (radio === form.querySelector('input[data-event-type]')) sync();
});
