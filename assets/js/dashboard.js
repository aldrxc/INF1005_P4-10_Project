/* dashboard.js - dashboard page JS for MerchVault */
$(function () {

    // -----------------------------------------------
    // persist active tab in URL hash (survives refresh)
    // -----------------------------------------------
    var hashTabMap = {
        '#listings': 'listingsTab',
        '#purchases': 'purchasesTab',
        '#sales': 'salesTab',
    };

    // activate tab from hash on load
    var hash = window.location.hash;
    if (hash && hashTabMap[hash]) {
        var $triggerEl = $('#' + hashTabMap[hash]);
        if ($triggerEl.length) {
            bootstrap.Tab.getOrCreateInstance($triggerEl[0]).show();
        }
    }

    // update hash when tab changes
    $('#dashTabs [data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var paneId = $(e.target).data('bs-target');
        if (paneId === '#listingsPane') history.replaceState(null, '', '#listings');
        if (paneId === '#purchasesPane') history.replaceState(null, '', '#purchases');
        if (paneId === '#salesPane') history.replaceState(null, '', '#sales');
    });

    // -----------------------------------------------
    // inline listing status toggle (AJAX)
    // -----------------------------------------------
    $('.status-toggle').on('change', function () {
        var $select = $(this);
        var listingId = $select.data('listing-id');
        var csrf = $select.data('csrf');
        var newStatus = $select.val();

        var formData = new FormData();
        formData.append('listing_id', listingId);
        formData.append('status', newStatus);
        formData.append('csrf_token', csrf);

         // visual feedback
        $select.prop('disabled', true);

        fetch('/handlers/status-handler.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            $select.prop('disabled', false);
            if (!data.success) {
                // revert on failure
                alert('Could not update status: ' + (data.message || 'Unknown error.'));
                // reload to get fresh state
                window.location.reload();
            }
        })
        .catch(() => {
            $select.prop('disabled', false);
            alert('A network error occurred. Please refresh the page.');
        });
    });

    // -----------------------------------------------
    // delete listing - bootstrap modal confirmation
    // -----------------------------------------------
    var $deleteModal = $('#deleteListingModal');
    if ($deleteModal.length) {
        $('.delete-listing-btn').on('click', function () {
            var listingId = $(this).data('listing-id');
            var title = $(this).data('title');

            $('#deleteListingId').val(listingId);
            $('#deleteListingTitle').text(title);

            bootstrap.Modal.getOrCreateInstance($deleteModal[0]).show();
        });
    }

});