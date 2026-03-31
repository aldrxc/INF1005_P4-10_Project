/* create-listing.js - create & edit listing form JS for MerchVault */
$(function () {

    var $categorySelect = $('#category_id');
    var $merchFields = $('#merchFields');
    var $ticketFields = $('#ticketFields');
    var $sizeField = $('#sizeField');
    var $descTextarea = $('#description');
    var $descCount = $('#descCount');
    var $dropZone = $('#dropZone');
    var $fileInput = $('#images');
    var $previewGrid = $('#imagePreviewGrid');
    var $countHint = $('#imageCountHint');

    var selectedFiles = []; // track DataTransfer-style files

    // -----------------------------------------------
    // category-driven conditional field visibility
    // -----------------------------------------------
    function updateFieldVisibility() {
        if (!$categorySelect.length) return;
        var slug = $categorySelect.find('option:selected').data('slug');

        var isTicket = slug === 'event-tickets';
        var isApparel = slug === 'band-tees';

        // show/hide field groups
        $ticketFields.toggle(isTicket);
        $merchFields.toggle(!isTicket);
        $sizeField.toggle(isApparel);

        // toggle required attributes on ticket fields
        ['#event_name', '#event_date', '#venue_name', '#venue_city'].forEach(function (id) {
            var $el = $(id);
            if ($el.length) $el.prop('required', isTicket);
        });
    }

    if ($categorySelect.length) {
        $categorySelect.on('change', updateFieldVisibility);
        updateFieldVisibility(); // run on page load (for edit form pre-fill)
    }

    // -----------------------------------------------
    // character counter for description
    // -----------------------------------------------
    function updateDescCount() {
        if (!$descTextarea.length || !$descCount.length) return;
        var len = $descTextarea.val().length;
        $descCount.text(len);
        $descCount.closest('small').toggleClass('text-danger', len > 950);
    }

    if ($descTextarea.length) {
        $descTextarea.on('input', updateDescCount);
        updateDescCount();
    }

    // -----------------------------------------------
    // price: format to 2 decimal places on blur
    // -----------------------------------------------
    $('#price').on('blur', function () {
        var val = parseFloat($(this).val());
        if (!isNaN(val) && val > 0) {
            $(this).val(val.toFixed(2));
        }
    });

    // -----------------------------------------------
    // event date: must be in the future
    // -----------------------------------------------
    var $eventDateInput = $('#event_date');
    if ($eventDateInput.length) {
        var today = new Date().toISOString().split('T')[0];
        $eventDateInput.attr('min', today);

        $eventDateInput.on('change', function () {
            if ($(this).val() < today) {
                this.setCustomValidity('Event date must be in the future.');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // -----------------------------------------------
    // image upload: drag-and-drop + FileReader preview
    // -----------------------------------------------
    var MAX_FILES = 5;

    function renderPreviews() {
        if (!$previewGrid.length) return;

        $previewGrid.empty();
        if (selectedFiles.length === 0) {
            $previewGrid.addClass('d-none');
            if ($countHint.length) $countHint.text('');
            return;
        }

        $previewGrid.removeClass('d-none');
        if ($countHint.length) {
            $countHint.text(selectedFiles.length + ' / ' + MAX_FILES + ' image' + (selectedFiles.length !== 1 ? 's' : '') + ' selected');
        }

        $.each(selectedFiles, function (index, file) {
            var $wrapper = $('<div>', { class: 'image-preview-item position-relative' });
            
            if (index === 0) {
                $wrapper.append($('<span>', {
                    class: 'badge bg-accent position-absolute top-0 start-0 m-1',
                    css: { fontSize: '0.65rem' },
                    text: 'Primary'
                }));
            }

            var $img = $('<img>', { alt: 'Preview of ' + file.name, class: 'preview-img rounded' });

            var reader = new FileReader();
            reader.onload = function (e) { $img.attr('src', e.target.result); };
            reader.readAsDataURL(file);
            $wrapper.append($img);

            // remove button
            var $removeBtn = $('<button>', {
                type: 'button',
                class: 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0',
                css: { width: '22px', height: '22px', fontSize: '0.7rem', lineHeight: '1' },
                'aria-label': 'Remove image ' + (index + 1),
                html: '<i class="bi bi-x" aria-hidden="true"></i>'
            }).on('click', function () {
                selectedFiles.splice(index, 1);
                syncFileInput();
                renderPreviews();
            });

            $wrapper.append($removeBtn);
            $previewGrid.append($wrapper);
        });
    }

    function syncFileInput() {
        // rebuild file input's FileList using DataTransfer
        if (!$fileInput.length) return;
        var dt = new DataTransfer();
        selectedFiles.forEach(function (f) { dt.items.add(f); });
        $fileInput[0].files = dt.files;
    }

    function addFiles(newFiles) {
        for (var i = 0; i < newFiles.length; i++) {
            if (selectedFiles.length >= MAX_FILES) break;
            var file = newFiles[i];
            if (!file.type.startsWith('image/')) continue;
            selectedFiles.push(file);
        }
        syncFileInput();
        renderPreviews();
    }

    if ($fileInput.length) {
        $fileInput.on('change', function () {
            addFiles(this.files);
        });
    }

    if ($dropZone.length) {
        // click to trigger file input
        $dropZone.on('click', function (e) {
            if (e.target !== $fileInput[0] && !$(e.target).closest('label[for="images"]').length) {
                $fileInput.trigger('click');
            }
        });

        // keyboard accessibility
        $dropZone.on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $fileInput.trigger('click');
            }
        });

        $dropZone.on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        }).on('dragleave', function () {
            $(this).removeClass('drag-over');
        }).on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files) {
                addFiles(e.originalEvent.dataTransfer.files);
            }
        });
    }

    // -----------------------------------------------
    // client-side form validation before submit
    // -----------------------------------------------
    var $form = $('#createListingForm');
    if ($form.length) {
        $form.on('submit', function (e) {
            var valid = true;

            // price > 0
            var $priceEl = $('#price');
            
            if ($priceEl.length) {
                var price = parseFloat($priceEl.val());
                if (isNaN(price) || price <= 0) {
                    $priceEl[0].setCustomValidity('Please enter a price greater than 0.');
                    $priceEl[0].reportValidity();
                    valid = false;
                } else {
                    $priceEl[0].setCustomValidity('');
                }
            }

            // ticket: event date in future
            if ($ticketFields.length && $ticketFields.is(':visible')) {
                var $evDate = $('#event_date');
                if ($evDate.length && $evDate.val()) {
                    var today = new Date().toISOString().split('T')[0];
                    if ($evDate.val() < today) {
                        $evDate[0].setCustomValidity('Event date must be in the future.');
                        $evDate.addClass('is-invalid'); // turns input box red
                        
                        // inject text error directly below input if it isnt there already
                        if ($evDate.siblings('.invalid-feedback.date-error').length === 0) {
                            $evDate.after('<div class="invalid-feedback date-error">Event date must be in the future.</div>');
                        }
                        
                        valid = false;
                    } else {
                        $evDate[0].setCustomValidity('');
                        $evDate.removeClass('is-invalid'); // removes red box
                        $evDate.siblings('.invalid-feedback.date-error').remove(); // clears text
                    }
                }
            }

            if (!this.checkValidity()) {
                e.preventDefault();
                this.reportValidity();
            } else if (!valid) {
                e.preventDefault();
            }
        });
    }

});