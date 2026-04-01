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
                // update specific item line total
                $('[id="itemTotal' + cartItemId + '"]').text(data.lineTotal);

                // update all instances of subtotal and total on page
                // check if data.cartSubtotal exists, otherwise fallback to data.cartTotal
                $('[id="cartSubtotal"]').text(data.cartSubtotal !== undefined ? data.cartSubtotal : data.cartTotal);
                $('[id="cartTotal"]').text(data.cartTotal);

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
                var $row = $('[id="cartItem' + cartItemId + '"]');
                if ($row.length) {
                    $row.css({ 'transition': 'opacity 0.3s ease', 'opacity': '0' });
                    setTimeout(() => $row.remove(), 310);
                }

                $('[id="cartSubtotal"]').text(data.cartSubtotal !== undefined ? data.cartSubtotal : data.cartTotal);
                $('[id="cartTotal"]').text(data.cartTotal);

                // distinct item rows remaining (cartCount is SUM(qty), not row count)
                var distinctCount = $('#cartItemsList .cart-item').length - 1;
                $('[id="cartHeadingCount"]').text(distinctCount);
                $('[id="cartSubtotalLabel"]').html('Subtotal (<span id="cartItemCount">' + distinctCount + '</span> item' + (distinctCount !== 1 ? 's' : '') + ')');

                if (typeof window.updateCartBadge === 'function') {
                    window.updateCartBadge(count);
                }

                if (count === 0) {
                    window.location.reload();
                }
            }
        }).catch(() => { /* silent fail - page still functional */ });
    }

});