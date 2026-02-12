// JMC Foodies Custom JavaScript
// BASE_URL is injected by PHP in footer.php

// Add to cart via AJAX
function addToCart(flavorId, btn) {
    const card = btn.closest('.product-card') || btn.closest('.card');
    const qtyInput = card.querySelector('.qty-input');
    const qty = parseInt(qtyInput ? qtyInput.value : 1);

    if (qty < 1) {
        alert('Quantity must be at least 1');
        return;
    }

    fetch(BASE_URL + '/api/cart_add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ product_flavor_id: flavorId, quantity_packs: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartBadge(data.cart_count);
            // Brief visual feedback
            btn.innerHTML = '<i class="material-icons">check</i>';
            btn.classList.add('bg-gradient-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="material-icons">add_shopping_cart</i>';
                btn.classList.remove('bg-gradient-success');
            }, 1000);
        } else {
            alert(data.message || 'Error adding to cart');
        }
    })
    .catch(() => alert('Network error'));
}

// Update cart quantity
function updateCartQty(index, qty) {
    if (qty < 1) return removeCartItem(index);

    fetch(BASE_URL + '/api/cart_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ cart_index: index, quantity_packs: qty })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => alert('Network error'));
}

// Remove cart item
function removeCartItem(index) {
    if (!confirm('Remove this item from cart?')) return;

    fetch(BASE_URL + '/api/cart_remove.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ cart_index: index })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => alert('Network error'));
}

// Update cart badge count
function updateCartBadge(count) {
    document.querySelectorAll('.cart-badge, #sidebarCartBadge').forEach(el => {
        el.textContent = count > 0 ? count : '';
    });
}

// Payment method toggle
function togglePaymentMethod() {
    const method = document.querySelector('input[name="payment_method"]:checked');
    const efundsInfo = document.getElementById('efundsInfo');
    if (efundsInfo) {
        efundsInfo.style.display = method && method.value === 'efunds' ? 'block' : 'none';
    }
}

// Print receipt
function printReceipt() {
    window.print();
}

// Confirm action
function confirmAction(message, url) {
    if (confirm(message)) {
        window.location.href = url;
    }
}
