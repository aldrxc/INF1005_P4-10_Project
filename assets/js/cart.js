/* cart.js — Shopping cart page JS for MerchVault */

document.addEventListener('DOMContentLoaded', function () {

    // -----------------------------------------------
    // Quantity stepper buttons
    // -----------------------------------------------
    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-action');
            var itemId = btn.getAttribute('data-item-id');
            var input  = document.querySelector('.qty-input[data-item-id="' + itemId + '"]');
            if (!input) return;

            var current = parseInt(input.value, 10);
            var newQty  = action === 'plus' ? current + 1 : Math.max(1, current - 1);
            if (newQty === current) return;

            input.value = newQty;
            updateCartItem(itemId, newQty);
        });
    });

    // -----------------------------------------------
    // Manual quantity input change
    // -----------------------------------------------
    document.querySelectorAll('.qty-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var itemId = input.getAttribute('data-item-id');
            var newQty = Math.max(1, parseInt(input.value, 10) || 1);
            input.value = newQty;
            updateCartItem(itemId, newQty);
        });
    });

    // -----------------------------------------------
    // Remove item buttons
    // -----------------------------------------------
    document.querySelectorAll('.remove-item-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var itemId = btn.getAttribute('data-item-id');
            removeCartItem(itemId);
        });
    });

    // -----------------------------------------------
    // AJAX: update quantity
    // -----------------------------------------------
    function updateCartItem(cartItemId, qty) {
        var formData = new FormData();
        formData.append('cart_action',   'update');
        formData.append('cart_item_id',  cartItemId);
        formData.append('quantity',      qty);
        formData.append('csrf_token',    typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        fetch('/handlers/cart-handler.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                var itemTotalEl = document.getElementById('itemTotal' + cartItemId);
                if (itemTotalEl) itemTotalEl.textContent = data.lineTotal;

                var subtotalEl = document.getElementById('cartSubtotal');
                var totalEl    = document.getElementById('cartTotal');
                if (subtotalEl) subtotalEl.textContent = data.cartTotal;
                if (totalEl)    totalEl.textContent    = data.cartTotal;

                if (typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge(data.cartCount);
                }
            }
        })
        .catch(function () {
            // Silent fail — page still functional
        });
    }

    // -----------------------------------------------
    // AJAX: remove item (fade out row)
    // -----------------------------------------------
    function removeCartItem(cartItemId) {
        var formData = new FormData();
        formData.append('cart_action',  'remove');
        formData.append('cart_item_id', cartItemId);
        formData.append('csrf_token',   typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        fetch('/handlers/cart-handler.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                var row = document.getElementById('cartItem' + cartItemId);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease';
                    row.style.opacity    = '0';
                    setTimeout(function () { row.remove(); }, 310);
                }

                var subtotalEl = document.getElementById('cartSubtotal');
                var totalEl    = document.getElementById('cartTotal');
                if (subtotalEl) subtotalEl.textContent = data.cartTotal;
                if (totalEl)    totalEl.textContent    = data.cartTotal;

                if (typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge(data.cartCount);
                }

                // If cart is now empty, reload to show empty state
                if (data.cartCount === 0) {
                    window.location.reload();
                }
            }
        })
        .catch(function () {
            // Silent fail
        });
    }

});
