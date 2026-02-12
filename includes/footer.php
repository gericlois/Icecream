<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'retailer'): ?>
<nav class="mobile-bottom-nav d-md-none">
    <a href="<?php echo BASE_URL; ?>/retailer/index.php" class="<?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
        <i class="material-icons">home</i>
        <span>Home</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/retailer/catalog.php" class="<?php echo ($active_page ?? '') === 'catalog' ? 'active' : ''; ?>">
        <i class="material-icons">storefront</i>
        <span>Catalog</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/retailer/cart.php" class="cart-nav-link <?php echo ($active_page ?? '') === 'cart' ? 'active' : ''; ?>">
        <i class="material-icons">shopping_cart</i>
        <span>Cart</span>
        <?php $cc = get_cart_count(); if ($cc > 0): ?>
        <span class="cart-badge"><?php echo $cc; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo BASE_URL; ?>/retailer/orders.php" class="<?php echo ($active_page ?? '') === 'orders' ? 'active' : ''; ?>">
        <i class="material-icons">receipt_long</i>
        <span>Orders</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/retailer/efunds.php" class="<?php echo ($active_page ?? '') === 'efunds' ? 'active' : ''; ?>">
        <i class="material-icons">account_balance_wallet</i>
        <span>E-Funds</span>
    </a>
</nav>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/js/material-dashboard.min.js"></script>
<script>const BASE_URL = '<?php echo BASE_URL; ?>';</script>
<script src="<?php echo BASE_URL; ?>/assets/js/custom.js"></script>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'retailer'): ?>
<script>
// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?php echo BASE_URL; ?>/sw.js', { scope: '<?php echo BASE_URL; ?>/' })
        .then(function(reg) {
            console.log('Service Worker registered:', reg.scope);
        })
        .catch(function(err) {
            console.log('SW registration failed:', err);
        });
}

// Detect if running as installed PWA
if (window.matchMedia('(display-mode: standalone)').matches) {
    document.body.classList.add('pwa-standalone');
}
</script>
<?php endif; ?>

</body>
</html>
