/* cart.js - shopping cart page JS for MerchVault */
$(function () {

    // -----------------------------------------------
    // quantity stepper buttons
    // -----------------------------------------------
    $('.qty-btn').on('click', function () {
        var action = $(this).data('action');
        var itemId = $(this).data('item-id');
        var $input = $('.qty-input[data-item-id="' + itemId + '"]');
        
        if (!$input.length) return;

        var current = parseInt($input.val(), 10);
        var newQty = action === 'plus' ? current + 1 : Math.max(1, current - 1);
        if (newQty === current) return;

        $input.val(newQty);
        updateCartItem(itemId, newQty);
    });

    // -----------------------------------------------
    // manual quantity input change
    // -----------------------------------------------
    $('.qty-input').on('change', function () {
        var itemId = $(this).data('item-id');
        var newQty = Math.max(1, parseInt($(this).val(), 10) || 1);
        $(this).val(newQty);
        updateCartItem(itemId, newQty);
    });

    // -----------------------------------------------
    // remove item buttons
    // -----------------------------------------------
    $('.remove-item-btn').on('click', function () {
        var itemId = $(this).data('item-id');
        removeCartItem(itemId);
    });

    // -----------------------------------------------
    // AJAX: update quantity
    // -----------------------------------------------
    function updateCartItem(cartItemId, qty) {
        var formData = new FormData();
        formData.append('cart_action', 'update');
        formData.append('cart_item_id', cartItemId);
        formData.append('quantity', qty);
        formData.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        fetch('/handlers/cart-handler.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#itemTotal' + cartItemId).text(data.lineTotal);
                $('#cartSubtotal, #cartTotal').text(data.cartTotal);

                if (typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge(data.cartCount);
                }
            }
        }).catch(() => { /* silent fail - page still functional */ });
    }

    // -----------------------------------------------
    // AJAX: remove item (fade out row)
    // -----------------------------------------------
    function removeCartItem(cartItemId) {
        var formData = new FormData();
        formData.append('cart_action', 'remove');
        formData.append('cart_item_id', cartItemId);
        formData.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');

        fetch('/handlers/cart-handler.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var $row = $('#cartItem' + cartItemId);
                if ($row.length) {
                    $row.css({ 'transition': 'opacity 0.3s ease', 'opacity': '0' });
                    setTimeout(() => $row.remove(), 310);
                }

                $('#cartSubtotal, #cartTotal').text(data.cartTotal);

                if (typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge(data.cartCount);
                }

                // if cart is now empty, reload to show empty state
                if (data.cartCount === 0) {
                    window.location.reload();
                }
            }
        }).catch(() => { /* silent fail - page still functional */ });
    }

});