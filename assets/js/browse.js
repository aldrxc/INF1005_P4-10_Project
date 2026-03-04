/* browse.js — Browse & filter page JS for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------
    // Debounced search — auto-submit after 400ms idle
    // -----------------------------------------------
    var searchInputs = document.querySelectorAll('#filterSearch');
    searchInputs.forEach(function (input) {
        var debounceTimer;
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                var form = input.closest('form');
                if (form) form.submit();
            }, 400);
        });
    });

    // -----------------------------------------------
    // Auto-submit filter form on checkbox/radio/select change
    // (category radios, genre select, condition checkboxes)
    // -----------------------------------------------
    document.querySelectorAll('.filter-check, #filterGenre').forEach(function (el) {
        el.addEventListener('change', function () {
            var form = el.closest('form');
            if (form) form.submit();
        });
    });

    // -----------------------------------------------
    // Price range: ensure min <= max on blur
    // -----------------------------------------------
    var minPrice = document.getElementById('minPrice');
    var maxPrice = document.getElementById('maxPrice');

    function validatePriceRange() {
        if (!minPrice || !maxPrice) return;
        var min = parseFloat(minPrice.value);
        var max = parseFloat(maxPrice.value);
        if (!isNaN(min) && !isNaN(max) && min > max) {
            maxPrice.setCustomValidity('Max price must be greater than min price.');
            maxPrice.classList.add('is-invalid');
        } else {
            maxPrice.setCustomValidity('');
            maxPrice.classList.remove('is-invalid');
        }
    }

    if (minPrice) minPrice.addEventListener('blur', validatePriceRange);
    if (maxPrice) maxPrice.addEventListener('blur', validatePriceRange);

    // -----------------------------------------------
    // Sort dropdown: sync hidden sort input in filter form
    // when both sort selects are present (desktop sort +
    // offcanvas filter form)
    // -----------------------------------------------
    var mainSortSelect = document.querySelector('#sortForm select[name="sort"]');
    var filterSortInputs = document.querySelectorAll('#filterForm input[name="sort"]');

    if (mainSortSelect) {
        mainSortSelect.addEventListener('change', function () {
            filterSortInputs.forEach(function (input) {
                input.value = mainSortSelect.value;
            });
        });
    }

    // -----------------------------------------------
    // Listing card hover: show quick-view overlay
    // -----------------------------------------------
    document.querySelectorAll('.listing-card').forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            card.classList.add('hovered');
        });
        card.addEventListener('mouseleave', function () {
            card.classList.remove('hovered');
        });
    });

});
