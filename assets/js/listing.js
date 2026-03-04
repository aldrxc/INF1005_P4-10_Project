/* listing.js — Single listing detail page JS for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------
    // Custom image gallery (thumbnail → main swap)
    // -----------------------------------------------
    var mainImg  = document.getElementById('galleryMainImg');
    var thumbBtns = document.querySelectorAll('.gallery-thumb');

    thumbBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var src = btn.getAttribute('data-src');
            if (mainImg && src) {
                mainImg.src = src;
                mainImg.alt = btn.querySelector('img') ? btn.querySelector('img').alt : '';
            }
            thumbBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
        });

        // Keyboard support
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });
    });

    // -----------------------------------------------
    // Add to Cart — AJAX (no page reload)
    // -----------------------------------------------
    var addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function () {
            var listingId = addToCartBtn.getAttribute('data-listing-id');
            var csrf      = addToCartBtn.getAttribute('data-csrf');

            addToCartBtn.disabled = true;
            addToCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding…';

            var formData = new FormData();
            formData.append('cart_action', 'add');
            formData.append('listing_id', listingId);
            formData.append('csrf_token', csrf);

            fetch('/handlers/cart-handler.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    addToCartBtn.innerHTML = '<i class="bi bi-check-circle me-2" aria-hidden="true"></i>In Cart';
                    addToCartBtn.disabled = true;
                    if (typeof window.updateCartBadge === 'function') {
                        window.updateCartBadge(data.cartCount);
                    }
                    showToast(data.message || 'Added to cart!', 'success');
                } else {
                    addToCartBtn.innerHTML = '<i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Add to Cart';
                    addToCartBtn.disabled = false;
                    showToast(data.message || 'Could not add to cart.', 'danger');
                }
            })
            .catch(function () {
                addToCartBtn.innerHTML = '<i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Add to Cart';
                addToCartBtn.disabled = false;
                showToast('A network error occurred. Please try again.', 'danger');
            });
        });
    }

    // -----------------------------------------------
    // Share button — copy URL to clipboard
    // -----------------------------------------------
    var shareBtn = document.getElementById('shareBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(window.location.href).then(function () {
                    showToast('Link copied to clipboard!', 'info');
                    shareBtn.innerHTML = '<i class="bi bi-check me-1" aria-hidden="true"></i>Copied!';
                    setTimeout(function () {
                        shareBtn.innerHTML = '<i class="bi bi-share me-1" aria-hidden="true"></i>Share';
                    }, 2000);
                });
            } else {
                // Fallback
                var input = document.createElement('input');
                input.value = window.location.href;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showToast('Link copied!', 'info');
            }
        });
    }

    // -----------------------------------------------
    // Ticket countdown timer
    // -----------------------------------------------
    var eventDateEl = document.getElementById('eventDate');
    var countdownEl = document.getElementById('ticketCountdown');

    if (eventDateEl && countdownEl) {
        var eventDate = new Date(eventDateEl.getAttribute('data-date') + 'T00:00:00');

        function updateCountdown() {
            var now  = new Date();
            var diff = eventDate - now;

            if (diff <= 0) {
                countdownEl.textContent = 'This event has already taken place.';
                countdownEl.className = 'mt-2 text-muted small';
                return;
            }

            var days    = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            countdownEl.innerHTML =
                '<i class="bi bi-clock me-1" aria-hidden="true"></i>' +
                days + 'd ' + hours + 'h ' + minutes + 'm until event';
            countdownEl.className = 'mt-2 small text-accent';
        }

        updateCountdown();
        setInterval(updateCountdown, 60000);
    }

    // -----------------------------------------------
    // Shared toast helper
    // -----------------------------------------------
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }

        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + type + ' border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body">' + escapeHtml(message) + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" ' +
                    'data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';

        container.appendChild(toastEl);
        var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

});
