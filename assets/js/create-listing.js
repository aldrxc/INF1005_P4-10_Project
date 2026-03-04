/* create-listing.js — Create & Edit Listing form JS for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    var categorySelect = document.getElementById('category_id');
    var merchFields    = document.getElementById('merchFields');
    var ticketFields   = document.getElementById('ticketFields');
    var sizeField      = document.getElementById('sizeField');
    var descTextarea   = document.getElementById('description');
    var descCount      = document.getElementById('descCount');
    var dropZone       = document.getElementById('dropZone');
    var fileInput      = document.getElementById('images');
    var previewGrid    = document.getElementById('imagePreviewGrid');
    var countHint      = document.getElementById('imageCountHint');

    var selectedFiles = []; // track DataTransfer-style files

    // -----------------------------------------------
    // Category-driven conditional field visibility
    // -----------------------------------------------
    function updateFieldVisibility() {
        if (!categorySelect) return;
        var selectedOption = categorySelect.options[categorySelect.selectedIndex];
        var slug = selectedOption ? selectedOption.getAttribute('data-slug') : '';

        var isTicket   = slug === 'event-tickets';
        var isApparel  = slug === 'band-tees';

        // Show/hide field groups
        if (ticketFields)   ticketFields.style.display  = isTicket  ? '' : 'none';
        if (merchFields)    merchFields.style.display   = isTicket  ? 'none' : '';
        if (sizeField)      sizeField.style.display     = isApparel ? '' : 'none';

        // Toggle required attributes on ticket fields
        ['event_name', 'event_date', 'venue_name', 'venue_city'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.required = isTicket;
            }
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', updateFieldVisibility);
        updateFieldVisibility(); // run on page load (for edit form pre-fill)
    }

    // -----------------------------------------------
    // Character counter for description
    // -----------------------------------------------
    function updateDescCount() {
        if (!descTextarea || !descCount) return;
        var len = descTextarea.value.length;
        descCount.textContent = len;
        descCount.closest('small').classList.toggle('text-danger', len > 950);
    }

    if (descTextarea) {
        descTextarea.addEventListener('input', updateDescCount);
        updateDescCount();
    }

    // -----------------------------------------------
    // Price: format to 2 decimal places on blur
    // -----------------------------------------------
    var priceInput = document.getElementById('price');
    if (priceInput) {
        priceInput.addEventListener('blur', function () {
            var val = parseFloat(priceInput.value);
            if (!isNaN(val) && val > 0) {
                priceInput.value = val.toFixed(2);
            }
        });
    }

    // -----------------------------------------------
    // Event date: must be in the future
    // -----------------------------------------------
    var eventDateInput = document.getElementById('event_date');
    if (eventDateInput) {
        var today = new Date().toISOString().split('T')[0];
        eventDateInput.setAttribute('min', today);

        eventDateInput.addEventListener('change', function () {
            if (eventDateInput.value < today) {
                eventDateInput.setCustomValidity('Event date must be in the future.');
            } else {
                eventDateInput.setCustomValidity('');
            }
        });
    }

    // -----------------------------------------------
    // Image upload: drag-and-drop + FileReader preview
    // -----------------------------------------------
    var MAX_FILES = 5;

    function renderPreviews() {
        if (!previewGrid) return;

        previewGrid.innerHTML = '';
        if (selectedFiles.length === 0) {
            previewGrid.classList.add('d-none');
            if (countHint) countHint.textContent = '';
            return;
        }

        previewGrid.classList.remove('d-none');
        if (countHint) {
            countHint.textContent = selectedFiles.length + ' / ' + MAX_FILES + ' image' + (selectedFiles.length !== 1 ? 's' : '') + ' selected';
        }

        selectedFiles.forEach(function (file, index) {
            var wrapper = document.createElement('div');
            wrapper.className = 'image-preview-item position-relative';
            if (index === 0) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-accent position-absolute top-0 start-0 m-1';
                badge.style.fontSize = '0.65rem';
                badge.textContent = 'Primary';
                wrapper.appendChild(badge);
            }

            var img = document.createElement('img');
            img.alt = 'Preview of ' + file.name;
            img.className = 'preview-img rounded';

            var reader = new FileReader();
            reader.onload = function (e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
            wrapper.appendChild(img);

            // Remove button
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0';
            removeBtn.style.cssText = 'width:22px;height:22px;font-size:0.7rem;line-height:1';
            removeBtn.setAttribute('aria-label', 'Remove image ' + (index + 1));
            removeBtn.innerHTML = '<i class="bi bi-x" aria-hidden="true"></i>';
            removeBtn.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                syncFileInput();
                renderPreviews();
            });
            wrapper.appendChild(removeBtn);
            previewGrid.appendChild(wrapper);
        });
    }

    function syncFileInput() {
        // Rebuild the file input's FileList using DataTransfer
        if (!fileInput) return;
        var dt = new DataTransfer();
        selectedFiles.forEach(function (f) { dt.items.add(f); });
        fileInput.files = dt.files;
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

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            addFiles(fileInput.files);
        });
    }

    if (dropZone) {
        // Click to trigger file input
        dropZone.addEventListener('click', function (e) {
            if (e.target !== fileInput && !e.target.closest('label[for="images"]')) {
                fileInput && fileInput.click();
            }
        });

        // Keyboard accessibility
        dropZone.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput && fileInput.click();
            }
        });

        dropZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        dropZone.addEventListener('dragleave', function () {
            dropZone.classList.remove('drag-over');
        });
        dropZone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            addFiles(e.dataTransfer.files);
        });
    }

    // -----------------------------------------------
    // Client-side form validation before submit
    // -----------------------------------------------
    var form = document.getElementById('createListingForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;

            // Price > 0
            var price = parseFloat(document.getElementById('price') && document.getElementById('price').value);
            if (isNaN(price) || price <= 0) {
                var priceEl = document.getElementById('price');
                if (priceEl) {
                    priceEl.setCustomValidity('Please enter a price greater than 0.');
                    priceEl.reportValidity();
                    valid = false;
                }
            } else {
                var priceEl = document.getElementById('price');
                if (priceEl) priceEl.setCustomValidity('');
            }

            // Ticket: event date in future
            if (ticketFields && ticketFields.style.display !== 'none') {
                var evDate = document.getElementById('event_date');
                if (evDate && evDate.value) {
                    var today = new Date().toISOString().split('T')[0];
                    if (evDate.value < today) {
                        evDate.setCustomValidity('Event date must be in the future.');
                        valid = false;
                    }
                }
            }

            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
            } else if (!valid) {
                e.preventDefault();
            }
        });
    }

});
