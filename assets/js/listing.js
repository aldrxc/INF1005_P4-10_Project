/* listing.js - single listing detail page JS for MerchVault */
$(function () {

    // -----------------------------------------------
    // custom image gallery (thumbnail -> main swap)
    // -----------------------------------------------
    var $mainImg = $('#galleryMainImg');
    var $thumbBtns = $('.gallery-thumb');

    $thumbBtns.on('click', function () {
        var src = $(this).data('src');
        if ($mainImg.length && src) {
            $mainImg.attr('src', src);
            $mainImg.attr('alt', $(this).find('img').attr('alt') || '');
        }
        $thumbBtns.removeClass('active');
        $(this).addClass('active');
    });

    // keyboard support
    $thumbBtns.on('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // -----------------------------------------------
    // add to cart - AJAX (no page reload)
    // -----------------------------------------------
    var $addToCartBtn = $('#addToCartBtn');
    if ($addToCartBtn.length) {
        $addToCartBtn.on('click', function () {
            var listingId = $(this).data('listing-id');
            var csrf = $(this).data('csrf');

            $addToCartBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding…');

            var formData = new FormData();
            formData.append('cart_action', 'add');
            formData.append('listing_id', listingId);
            formData.append('csrf_token', csrf);

            fetch('/handlers/cart-handler.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    $addToCartBtn.html('<i class="bi bi-check-circle me-2" aria-hidden="true"></i>In Cart').prop('disabled', true);
                    if (typeof window.updateCartBadge === 'function') {
                        window.updateCartBadge(data.cartCount);
                    }
                    showToast(data.message || 'Added to cart!', 'success');
                } else {
                    $addToCartBtn.html('<i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Add to Cart').prop('disabled', false);
                    showToast(data.message || 'Could not add to cart.', 'danger');
                }
            })
            .catch(() => {
                $addToCartBtn.html('<i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Add to Cart').prop('disabled', false);
                showToast('A network error occurred. Please try again.', 'danger');
            });
        });
    }

    // -----------------------------------------------
    // share button - copy url to clipboard
    // -----------------------------------------------
    var $shareBtn = $('#shareBtn');
    if ($shareBtn.length) {
        $shareBtn.on('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showToast('Link copied to clipboard!', 'info');
                    $shareBtn.html('<i class="bi bi-check me-1" aria-hidden="true"></i>Copied!');
                    setTimeout(() => { $shareBtn.html('<i class="bi bi-share me-1" aria-hidden="true"></i>Share'); }, 2000);
                });
            } else {
                // fallback
                var $input = $('<input>').val(window.location.href).appendTo('body').select();
                document.execCommand('copy');
                $input.remove();
                showToast('Link copied!', 'info');
            }
        });
    }

    // -----------------------------------------------
    // ticket countdown timer
    // -----------------------------------------------
    var $eventDateEl = $('#eventDate');
    var $countdownEl = $('#ticketCountdown');

    if ($eventDateEl.length && $countdownEl.length) {
        var eventDate = new Date($eventDateEl.data('date') + 'T00:00:00');

        function updateCountdown() {
            var diff = eventDate - new Date();

            if (diff <= 0) {
                $countdownEl.text('This event has already taken place.').attr('class', 'mt-2 text-muted small');
                return;
            }

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            $countdownEl.html('<i class="bi bi-clock me-1" aria-hidden="true"></i>' + days + 'd ' + hours + 'h ' + minutes + 'm until event')
                        .attr('class', 'mt-2 small text-accent');
        }

        updateCountdown();
        setInterval(updateCountdown, 60000);
    }

    // -----------------------------------------------
    // shared toast helper
    // -----------------------------------------------
    function showToast(message, type) {
        var $container = $('#toastContainer');
        if (!$container.length) {
            $container = $('<div>', {
                id: 'toastContainer',
                class: 'toast-container position-fixed bottom-0 end-0 p-3',
                'aria-live': 'polite'
            }).appendTo('body');
        }

        var $toastEl = $('<div>', {
            class: 'toast align-items-center text-bg-' + type + ' border-0',
            role: 'alert',
            'aria-live': 'assertive',
            'aria-atomic': 'true',
            html: '<div class="d-flex"><div class="toast-body">' + escapeHtml(message) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>'
        }).appendTo($container);

        var toast = new bootstrap.Toast($toastEl[0], { delay: 3000 });
        toast.show();
        $toastEl.on('hidden.bs.toast', function () { $(this).remove(); });
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

});