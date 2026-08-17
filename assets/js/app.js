// Minimal, dependency-free JS: "select all" checkboxes on approval
// queues, and toggling the reject-reason field.

document.addEventListener('change', function (e) {
    if (e.target.matches('[data-select-all]')) {
        var group = e.target.getAttribute('data-select-all');
        document.querySelectorAll('input[type=checkbox][data-group="' + group + '"]').forEach(function (cb) {
            cb.checked = e.target.checked;
        });
    }

    if (e.target.matches('[data-bulk-action]')) {
        var reasonBox = document.querySelector('[data-reason-for="' + e.target.getAttribute('data-bulk-action') + '"]');
        if (reasonBox) {
            reasonBox.style.display = e.target.value === 'reject' ? 'block' : 'none';
        }
    }
});

document.addEventListener('submit', function (e) {
    if (e.target.matches('[data-confirm]')) {
        if (!confirm(e.target.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    }
});
