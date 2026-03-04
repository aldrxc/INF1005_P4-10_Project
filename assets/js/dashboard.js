/* dashboard.js — Dashboard page JS for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------
    // Persist active tab in URL hash (survives refresh)
    // -----------------------------------------------
    var tabTriggers = document.querySelectorAll('#dashTabs [data-bs-toggle="tab"]');
    var hashTabMap  = {
        '#listings'  : 'listingsTab',
        '#purchases' : 'purchasesTab',
        '#sales'     : 'salesTab',
    };

    // Activate tab from hash on load
    var hash = window.location.hash;
    if (hash && hashTabMap[hash]) {
        var triggerEl = document.getElementById(hashTabMap[hash]);
        if (triggerEl) {
            bootstrap.Tab.getOrCreateInstance(triggerEl).show();
        }
    }

    // Update hash when tab changes
    tabTriggers.forEach(function (trigger) {
        trigger.addEventListener('shown.bs.tab', function (e) {
            var paneId = e.target.getAttribute('data-bs-target');
            if (paneId === '#listingsPane')  history.replaceState(null, '', '#listings');
            if (paneId === '#purchasesPane') history.replaceState(null, '', '#purchases');
            if (paneId === '#salesPane')     history.replaceState(null, '', '#sales');
        });
    });

    // -----------------------------------------------
    // Inline listing status toggle (AJAX)
    // -----------------------------------------------
    document.querySelectorAll('.status-toggle').forEach(function (select) {
        select.addEventListener('change', function () {
            var listingId = select.getAttribute('data-listing-id');
            var csrf      = select.getAttribute('data-csrf');
            var newStatus = select.value;

            var formData = new FormData();
            formData.append('listing_id', listingId);
            formData.append('status',     newStatus);
            formData.append('csrf_token', csrf);

            // Visual feedback
            select.disabled = true;

            fetch('/handlers/status-handler.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                select.disabled = false;
                if (!data.success) {
                    // Revert on failure
                    alert('Could not update status: ' + (data.message || 'Unknown error.'));
                    // Reload to get fresh state
                    window.location.reload();
                }
            })
            .catch(function () {
                select.disabled = false;
                alert('A network error occurred. Please refresh the page.');
            });
        });
    });

    // -----------------------------------------------
    // Delete listing — Bootstrap modal confirmation
    // -----------------------------------------------
    var deleteModal      = document.getElementById('deleteListingModal');
    var deleteIdInput    = document.getElementById('deleteListingId');
    var deleteTitleEl    = document.getElementById('deleteListingTitle');

    if (deleteModal) {
        document.querySelectorAll('.delete-listing-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var listingId = btn.getAttribute('data-listing-id');
                var title     = btn.getAttribute('data-title');

                if (deleteIdInput)  deleteIdInput.value    = listingId;
                if (deleteTitleEl)  deleteTitleEl.textContent = title;

                bootstrap.Modal.getOrCreateInstance(deleteModal).show();
            });
        });
    }

});
