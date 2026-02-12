<?php
// Build notifications based on role
$notifications = [];
$notif_count = 0;

if ($_SESSION['role'] === 'admin') {
    // Pending orders
    $res = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
    $pending_orders = $res ? (int)$res->fetch_assoc()['cnt'] : 0;
    if ($pending_orders > 0) {
        $notifications[] = ['icon' => 'shopping_cart', 'text' => "$pending_orders pending order" . ($pending_orders > 1 ? 's' : ''), 'link' => BASE_URL . '/admin/orders.php', 'color' => 'warning'];
        $notif_count += $pending_orders;
    }
    // Pending registrations
    $res = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE status = 'inactive' AND registered_by IS NULL");
    $pending_reg = $res ? (int)$res->fetch_assoc()['cnt'] : 0;
    if ($pending_reg > 0) {
        $notifications[] = ['icon' => 'person_add', 'text' => "$pending_reg pending registration" . ($pending_reg > 1 ? 's' : ''), 'link' => BASE_URL . '/admin/users.php', 'color' => 'info'];
        $notif_count += $pending_reg;
    }
} elseif ($_SESSION['role'] === 'subdealer') {
    // Orders to process
    $res = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'approved'");
    $approved = $res ? (int)$res->fetch_assoc()['cnt'] : 0;
    if ($approved > 0) {
        $notifications[] = ['icon' => 'local_shipping', 'text' => "$approved order" . ($approved > 1 ? 's' : '') . " ready for delivery", 'link' => BASE_URL . '/subdealer/orders.php', 'color' => 'info'];
        $notif_count += $approved;
    }
} elseif ($_SESSION['role'] === 'retailer') {
    // Order updates
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id = $uid AND status = 'for_delivery'");
    $in_delivery = $res ? (int)$res->fetch_assoc()['cnt'] : 0;
    if ($in_delivery > 0) {
        $notifications[] = ['icon' => 'local_shipping', 'text' => "$in_delivery order" . ($in_delivery > 1 ? 's' : '') . " out for delivery", 'link' => BASE_URL . '/retailer/orders.php', 'color' => 'success'];
        $notif_count += $in_delivery;
    }
}
?>
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 border-radius-xl" id="navbarBlur" data-scroll="true" style="background:rgba(255,255,255,0.9);backdrop-filter:blur(10px);box-shadow:0 2px 12px 0 rgba(0,0,0,0.08);margin-top:1rem;">
    <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;"><?php echo ucfirst($_SESSION['role']); ?></a></li>
                <li class="breadcrumb-item text-sm text-dark active" aria-current="page"><?php echo $page_title ?? 'Dashboard'; ?></li>
            </ol>
            <h6 class="font-weight-bolder mb-0"><?php echo $page_title ?? 'Dashboard'; ?></h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
            <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            </div>
            <ul class="navbar-nav justify-content-end">
                <!-- Cart icon (retailer only) -->
                <?php if ($_SESSION['role'] === 'retailer'): $cart_count = get_cart_count(); ?>
                <li class="nav-item pe-2 d-flex align-items-center">
                    <a href="<?php echo BASE_URL; ?>/retailer/cart.php" class="nav-link text-body p-0 position-relative">
                        <i class="material-icons">shopping_cart</i>
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            <?php echo $cart_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <!-- Notification bell -->
                <li class="nav-item dropdown pe-2 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0 position-relative" id="dropdownNotif" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-icons">notifications</i>
                        <?php if ($notif_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                            <?php echo $notif_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownNotif" style="min-width:280px;">
                        <?php if (empty($notifications)): ?>
                        <li class="mb-0">
                            <a class="dropdown-item border-radius-md" href="javascript:;">
                                <div class="d-flex py-1">
                                    <div class="d-flex flex-column justify-content-center">
                                        <h6 class="text-sm font-weight-normal mb-1 text-secondary">No new notifications</h6>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <li class="mb-1">
                            <a class="dropdown-item border-radius-md" href="<?php echo $notif['link']; ?>">
                                <div class="d-flex py-1">
                                    <div class="my-auto">
                                        <span class="badge bg-gradient-<?php echo $notif['color']; ?> me-3" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                                            <i class="material-icons text-sm"><?php echo $notif['icon']; ?></i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column justify-content-center">
                                        <h6 class="text-sm font-weight-normal mb-0"><?php echo $notif['text']; ?></h6>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-item d-flex align-items-center">
                    <span class="nav-link text-body font-weight-bold px-0">
                        <i class="material-icons me-sm-1">person</i>
                        <span class="d-sm-inline d-none"><?php echo sanitize($_SESSION['full_name']); ?></span>
                    </span>
                </li>
                <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
