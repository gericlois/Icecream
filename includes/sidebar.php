<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="<?php echo BASE_URL; ?>/">
            <img src="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg" alt="JMC Foodies" style="width:32px;height:32px;border-radius:6px;object-fit:cover;" class="me-2">
            <span class="ms-1 font-weight-bold text-white">JMC FOODIES</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">

<?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'dashboard' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/index.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">dashboard</i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'orders' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/orders.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">receipt_long</i>
                    </div>
                    <span class="nav-link-text ms-1">Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'products' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/products.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">icecream</i>
                    </div>
                    <span class="nav-link-text ms-1">Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'users' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/users.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">people</i>
                    </div>
                    <span class="nav-link-text ms-1">Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'reload_requests' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reload_requests.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">account_balance_wallet</i>
                    </div>
                    <span class="nav-link-text ms-1">Reload Requests</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'efunds' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/efunds_manual.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">payments</i>
                    </div>
                    <span class="nav-link-text ms-1">E-Funds</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'subsidies' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/subsidies.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">bolt</i>
                    </div>
                    <span class="nav-link-text ms-1">Subsidies</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'reports' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reports.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">assessment</i>
                    </div>
                    <span class="nav-link-text ms-1">Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'settings' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/settings.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">settings</i>
                    </div>
                    <span class="nav-link-text ms-1">Settings</span>
                </a>
            </li>

<?php elseif ($_SESSION['role'] === 'subdealer'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'dashboard' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/agent/index.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">dashboard</i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'retailers' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/agent/retailers.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">store</i>
                    </div>
                    <span class="nav-link-text ms-1">My Retailers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'orders' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/agent/orders.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">receipt_long</i>
                    </div>
                    <span class="nav-link-text ms-1">Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'new_order' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/agent/order_create.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">add_shopping_cart</i>
                    </div>
                    <span class="nav-link-text ms-1">New Order</span>
                </a>
            </li>

<?php elseif ($_SESSION['role'] === 'retailer'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'dashboard' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/index.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">dashboard</i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'catalog' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/catalog.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">storefront</i>
                    </div>
                    <span class="nav-link-text ms-1">Catalog</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'cart' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/cart.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">shopping_cart</i>
                    </div>
                    <span class="nav-link-text ms-1">Cart <span class="badge bg-danger ms-1" id="sidebarCartBadge"><?php echo get_cart_count() ?: ''; ?></span></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'orders' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/orders.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">receipt_long</i>
                    </div>
                    <span class="nav-link-text ms-1">My Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'efunds' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/efunds.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">account_balance_wallet</i>
                    </div>
                    <span class="nav-link-text ms-1">E-Funds</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'subsidy' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/retailer/subsidy.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">bolt</i>
                    </div>
                    <span class="nav-link-text ms-1">Electric Subsidy</span>
                </a>
            </li>
<?php endif; ?>

            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Account</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?php echo ($active_page ?? '') === 'profile' ? 'active sidebar-active' : ''; ?>" href="<?php echo BASE_URL; ?>/profile.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">person</i>
                    </div>
                    <span class="nav-link-text ms-1">Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/logout.php">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="material-icons opacity-10">logout</i>
                    </div>
                    <span class="nav-link-text ms-1">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
