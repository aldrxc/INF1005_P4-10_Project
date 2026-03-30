/* browse.js - browse & filter page JS for MerchVault */
$(function () {

    // -----------------------------------------------
    // debounced search - auto-submit after 1s idle
    // 400ms was too fast
    // -----------------------------------------------
    var debounceTimer;
    $('#filterSearch').on('input', function () {
        clearTimeout(debounceTimer);
        var $form = $(this).closest('form');
        debounceTimer = setTimeout(function () {
            if ($form.length) $form.trigger('submit');
        }, 1000);
    });

    // -----------------------------------------------
    // auto-submit filter form on checkbox/radio/select change
    // (category radios, genre select, condition checkboxes)
    // -----------------------------------------------
    $('.filter-check, #filterGenre').on('change', function () {
        $(this).closest('form').trigger('submit');
    });

    // -----------------------------------------------
    // price range: ensure min <= max on blur
    // -----------------------------------------------
    function validatePriceRange() {
        var min = parseFloat($('#minPrice').val());
        var max = parseFloat($('#maxPrice').val());
        var $maxPrice = $('#maxPrice');

        if ($maxPrice.length && !isNaN(min) && !isNaN(max) && min > max) {
            $maxPrice[0].setCustomValidity('Max price must be greater than min price.');
            $maxPrice.addClass('is-invalid');
        } else if ($maxPrice.length) {
            $maxPrice[0].setCustomValidity('');
            $maxPrice.removeClass('is-invalid');
        }
    }

    $('#minPrice, #maxPrice').on('blur', validatePriceRange);

    // -----------------------------------------------
    // sort dropdown: sync hidden sort input in filter form
    // when both sort selects are present (desktop sort +
    // offcanvas filter form)
    // -----------------------------------------------
    var $mainSortSelect = $('#sortForm select[name="sort"]');
    if ($mainSortSelect.length) {
        $mainSortSelect.on('change', function () {
            $('#filterForm input[name="sort"]').val($(this).val());
        });
    }

    // -----------------------------------------------
    // listing card hover: show quick-view overlay
    // -----------------------------------------------
    $('.listing-card').hover(
        function () { $(this).addClass('hovered'); },
        function () { $(this).removeClass('hovered'); }
    );

});