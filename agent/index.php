<?php
$page_title = 'Dashboard';
$active_page = 'dashboard';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

// My retailers count
$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE agent_id = $uid AND status = 'active'")->fetch_assoc();
$retailer_count = $r['cnt'];

// Pending orders (for my retailers)
$r = $conn->query("
    SELECT COUNT(*) as cnt FROM orders
    WHERE status = 'pending' AND (agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid))
")->fetch_assoc();
$pending_orders = $r['cnt'];

// Gross sales to-date (all delivered orders, all time)
$r = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) as total FROM orders
    WHERE status = 'delivered'
    AND (agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid))
")->fetch_assoc();
$gross_sales = $r['total'];

// Total orders
$r = $conn->query("
    SELECT COUNT(*) as cnt FROM orders
    WHERE agent_id = $uid OR user_id IN (SELECT id FROM users WHERE agent_id = $uid)
")->fetch_assoc();
$total_orders = $r['cnt'];

// Over-ride data
$subsidy = calculate_agent_subsidy($conn, $uid, $month, $year);
$progress_pct = $subsidy['min'] > 0 ? min(100, round(($subsidy['grand_total'] / $subsidy['min']) * 100, 1)) : 0;
$remaining = max(0, $subsidy['min'] - $subsidy['grand_total']);
$already_converted = $conn->query("SELECT id FROM electric_subsidy WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1")->num_rows > 0;

// Total purchases (all delivered orders from tagged retailers, all time)
$total_purchases = (float)$conn->query("
    SELECT COALESCE(SUM(o.total_amount), 0) as total
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE u.agent_id = $uid AND u.role = 'retailer' AND o.status = 'delivered'
")->fetch_assoc()['total'];

// Delivery window
$delivery = get_delivery_window();

// Recent orders
$recent = $conn->query("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.agent_id = $uid OR o.user_id IN (SELECT id FROM users WHERE agent_id = $uid)
    ORDER BY o.created_at DESC LIMIT 10
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Stat Cards -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">shopping_cart</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Purchases</p>
                            <h4 class="mb-0"><?php echo format_currency($total_purchases); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Gross Sales To-Date</p>
                            <h4 class="mb-0"><?php echo format_currency($gross_sales); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">store</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">My Retailers</p>
                            <h4 class="mb-0"><?php echo $retailer_count; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">pending_actions</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Pending Orders</p>
                            <h4 class="mb-0"><?php echo $pending_orders; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Over-Ride Quota + Delivery Window -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h6><i class="material-icons align-middle text-warning">bolt</i> Over-Ride Quota - <?php echo date('F Y'); ?></h6>
                            </div>
                            <div class="col-4 text-end">
                                <?php if ($already_converted): ?>
                                <span class="badge bg-gradient-success px-3 py-2">CONVERTED</span>
                                <?php elseif ($subsidy['eligible']): ?>
                                <span class="badge bg-gradient-success px-3 py-2">QUALIFIED</span>
                                <?php else: ?>
                                <span class="badge bg-gradient-warning px-3 py-2"><?php echo $progress_pct; ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-sm font-weight-bold"><?php echo format_currency($subsidy['grand_total']); ?> delivered</span>
                            <span class="text-sm text-secondary">Target: <?php echo format_currency($subsidy['min']); ?></span>
                        </div>
                        <div class="progress mb-3" style="height: 18px; border-radius: 10px;">
                            <div class="progress-bar bg-gradient-<?php echo $subsidy['eligible'] ? 'success' : ($progress_pct >= 50 ? 'info' : 'warning'); ?>"
                                 role="progressbar"
                                 style="width: <?php echo $progress_pct; ?>%; border-radius: 10px;"
                                 aria-valuenow="<?php echo $progress_pct; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo $progress_pct; ?>%
                            </div>
                        </div>

                        <?php if ($already_converted): ?>
                        <div class="alert alert-light text-sm mb-0 border">
                            <i class="material-icons align-middle text-success" style="font-size:18px;">check_circle</i>
                            Over-ride of <strong><?php echo format_currency($subsidy['total_subsidy']); ?></strong> already converted to e-funds this month.
                        </div>
                        <?php elseif ($subsidy['eligible']): ?>
                        <div class="alert alert-light text-sm mb-0 border">
                            <i class="material-icons align-middle text-success" style="font-size:18px;">check_circle</i>
                            Quota reached! Your over-ride of <strong><?php echo format_currency($subsidy['total_subsidy']); ?></strong> is ready.
                            <a href="<?php echo BASE_URL; ?>/agent/subsidy.php" class="text-primary font-weight-bold">Convert to E-Funds &rarr;</a>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-light text-sm mb-0 border">
                            <i class="material-icons align-middle text-warning" style="font-size:18px;">info</i>
                            <strong><?php echo format_currency($remaining); ?></strong> more in delivered retailer orders needed to qualify.
                            <a href="<?php echo BASE_URL; ?>/agent/earnings.php" class="text-primary font-weight-bold">View Details &rarr;</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Delivery Window -->
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-dark shadow-dark text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">local_shipping</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Next Delivery Window</p>
                                <h6 class="mb-0"><?php echo $delivery['label']; ?></h6>
                            </div>
                        </div>
                        <p class="text-xs text-muted mt-2 mb-0">
                            Order cutoff: <strong><?php echo $delivery['cutoff_label']; ?></strong>
                        </p>
                    </div>
                </div>

                <!-- Over-Ride Summary -->
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">bolt</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Potential Over-Ride</p>
                                <h5 class="mb-0"><?php echo format_currency($subsidy['eligible'] ? $subsidy['total_subsidy'] : 0); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="icon icon-shape bg-gradient-dark shadow-dark text-center border-radius-md me-3">
                                <i class="material-icons opacity-10 text-white">receipt_long</i>
                            </div>
                            <div>
                                <p class="text-xs text-secondary mb-0">Total Orders (All Time)</p>
                                <h5 class="mb-0"><?php echo $total_orders; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Retailers + Recent Orders -->
        <div class="row">
            <!-- Top Retailers -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <h6>Top Retailers This Month</h6>
                    </div>
                    <div class="card-body pt-2">
                        <?php
                        $sorted = $subsidy['breakdown'];
                        usort($sorted, fn($a, $b) => $b['orders_total'] <=> $a['orders_total']);
                        $shown = 0;
                        foreach ($sorted as $b):
                            if ($shown >= 5) break;
                            if ($b['orders_total'] <= 0) continue;
                            $r_pct = $subsidy['grand_total'] > 0 ? round(($b['orders_total'] / $subsidy['grand_total']) * 100, 1) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <span class="text-sm font-weight-bold"><?php echo sanitize($b['name']); ?></span>
                                    <span class="text-xs"><?php echo format_currency($b['orders_total']); ?></span>
                                </div>
                                <span class="text-xs text-secondary"><?php echo sanitize($b['package']); ?> (<?php echo $b['rate'] > 0 ? round($b['rate'] * 100, 1) . '%' : '0%'; ?>)</span>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar bg-gradient-info" style="width: <?php echo $r_pct; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php $shown++; endforeach; ?>

                        <?php if ($shown === 0): ?>
                        <p class="text-sm text-secondary text-center py-3">No retailer orders this month</p>
                        <?php endif; ?>

                        <?php if (count($sorted) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="<?php echo BASE_URL; ?>/agent/earnings.php" class="text-primary text-xs font-weight-bold">View All &rarr;</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-lg-8 mb-4">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-6"><h6>Recent Orders</h6></div>
                            <div class="col-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/agent/order_create.php" class="btn btn-sm bg-gradient-primary">New Order</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent->num_rows === 0): ?>
                                    <tr><td colspan="5" class="text-center text-sm py-4">No orders yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($o = $recent->fetch_assoc()): ?>
                                    <tr style="cursor:pointer" onclick="window.location='<?php echo BASE_URL; ?>/agent/order_view.php?id=<?php echo $o['id']; ?>'">
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><span class="text-xs"><?php echo date('M d', strtotime($o['created_at'])); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
