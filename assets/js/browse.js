/* browse.js - browse & filter page JS for MerchVault */
$(function () {

    // -----------------------------------------------
    // debounced search - auto-submit after 1s idle
    // 400ms was too fast
    // -----------------------------------------------
    $('[id="filterSearch"]').each(function () {
        var debounceTimer; // scoped to each individual input
        $(this).on('input', function () {
            clearTimeout(debounceTimer);
            var $form = $(this).closest('form');
            
            debounceTimer = setTimeout(function () {
                if ($form.length) {
                    $form[0].submit(); // native submit, exactly like vanilla js
                }
            }, 1000); // 1s delay (1000ms)
        });
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
    $('[id="minPrice"], [id="maxPrice"]').on('blur', function () {
        // scope check to specific form user is interacting with
        var $form = $(this).closest('form');
        var $minInput = $form.find('[id="minPrice"]');
        var $maxInput = $form.find('[id="maxPrice"]');

        // safety check in case form is missing one of inputs
        if (!$minInput.length || !$maxInput.length) return;

        var min = parseFloat($minInput.val());
        var max = parseFloat($maxInput.val());

        // validate
        if (!isNaN(min) && !isNaN(max) && min > max) {
            $maxInput[0].setCustomValidity('Max price must be greater than min price.');
            $maxInput.addClass('is-invalid');
        } else {
            $maxInput[0].setCustomValidity('');
            $maxInput.removeClass('is-invalid');
        }
    });

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