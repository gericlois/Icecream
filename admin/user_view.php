<?php
$page_title = 'View User';
$active_page = 'users';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("
    SELECT u.*, a.full_name as agent_name, p.name as package_name, p.slug as package_slug,
           p.subsidy_rate, p.subsidy_min_orders, p.freezer_display_allowance, p.registration_commission
    FROM users u
    LEFT JOIN users a ON u.agent_id = a.id
    LEFT JOIN packages p ON u.package_info = p.slug
    WHERE u.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    flash_message('danger', 'User not found.');
    redirect(BASE_URL . '/admin/users.php');
}

$page_title = 'View: ' . $user['full_name'];

// Current month
$month = (int)($_GET['month'] ?? date('m'));
$year = (int)($_GET['year'] ?? date('Y'));
if ($month < 1 || $month > 12) $month = (int)date('m');
if ($year < 2020 || $year > 2099) $year = (int)date('Y');
$period_label = date('F Y', mktime(0, 0, 0, $month, 1, $year));

// Navigation months
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
$is_current = $month === (int)date('m') && $year === (int)date('Y');

// === Orders ===
$stmt = $conn->prepare("
    SELECT * FROM orders
    WHERE user_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("iii", $id, $month, $year);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Order stats
$total_orders_amount = 0;
$order_counts = ['pending' => 0, 'approved' => 0, 'for_delivery' => 0, 'delivered' => 0, 'cancelled' => 0];
foreach ($orders as $o) {
    $order_counts[$o['status']]++;
    if ($o['status'] !== 'cancelled') $total_orders_amount += (float)$o['total_amount'];
}

// Delivered total
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'delivered' AND MONTH(delivered_at) = ? AND YEAR(delivered_at) = ?");
$stmt->bind_param("iii", $id, $month, $year);
$stmt->execute();
$delivered_total = (float)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// === Subsidy (for retailers) ===
$subsidy = null;
$subsidy_converted = false;
if ($user['role'] === 'retailer') {
    $subsidy = calculate_subsidy($conn, $id, $month, $year);
    $subsidy_converted = $conn->query("SELECT id FROM electric_subsidy WHERE user_id = $id AND month = $month AND year = $year AND converted = 1")->num_rows > 0;
}

// === FDA (for retailers) ===
$fda = null;
$fda_converted = false;
if ($user['role'] === 'retailer') {
    $fda = calculate_fda($conn, $id, $month, $year);
    $fda_converted = $conn->query("SELECT id FROM freezer_allowance WHERE user_id = $id AND month = $month AND year = $year AND converted = 1")->num_rows > 0;
}

// === Town Override (for Ice Cream House) ===
$override = null;
$override_converted = false;
if ($user['role'] === 'retailer' && ($user['package_slug'] ?? '') === 'ice_cream_house') {
    $override = calculate_town_override($conn, $id, $month, $year);
    $override_converted = $conn->query("SELECT id FROM town_override WHERE user_id = $id AND month = $month AND year = $year AND converted = 1")->num_rows > 0;
}

// === Agent Override (for subdealers) ===
$agent_subsidy = null;
if ($user['role'] === 'subdealer') {
    $agent_subsidy = calculate_agent_subsidy($conn, $id, $month, $year);
}

// === Agent Commissions (for subdealers) ===
$commissions = [];
if ($user['role'] === 'subdealer') {
    $stmt = $conn->prepare("
        SELECT ac.*, u.full_name as retailer_name, p.name as package_name
        FROM agent_commissions ac
        JOIN users u ON ac.retailer_id = u.id
        LEFT JOIN packages p ON ac.package_slug = p.slug
        WHERE ac.agent_id = ?
        ORDER BY ac.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $commissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// === E-Funds Transactions ===
$stmt = $conn->prepare("SELECT * FROM efunds_transactions WHERE user_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("iii", $id, $month, $year);
$stmt->execute();
$efunds_txns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// === Registered Retailers (for subdealers) ===
$tagged_retailers = [];
if ($user['role'] === 'subdealer') {
    $tagged_retailers = $conn->query("
        SELECT u.id, u.full_name, u.town, u.status, u.created_at, p.name as package_name
        FROM users u LEFT JOIN packages p ON u.package_info = p.slug
        WHERE u.agent_id = $id AND u.role = 'retailer'
        ORDER BY u.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- User Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span style="width:60px;height:60px;border-radius:50%;background:linear-gradient(195deg,<?php echo $user['role'] === 'admin' ? '#EF5350,#E53935' : ($user['role'] === 'subdealer' ? '#FFA726,#FB8C00' : '#42A5F5,#1E88E5'); ?>);display:inline-flex;align-items:center;justify-content:center;">
                                    <i class="material-icons text-white" style="font-size:28px;">person</i>
                                </span>
                            </div>
                            <div class="col">
                                <h5 class="mb-0"><?php echo sanitize($user['full_name']); ?></h5>
                                <p class="text-sm text-secondary mb-0">
                                    @<?php echo sanitize($user['username']); ?> &middot;
                                    <span class="badge bg-gradient-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'subdealer' ? 'warning' : 'info'); ?>"><?php echo ucfirst($user['role']); ?></span>
                                    <span class="badge bg-gradient-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($user['status']); ?></span>
                                    <?php if ($user['package_name']): ?>
                                    &middot; <strong><?php echo sanitize($user['package_name']); ?></strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <span class="text-sm me-3"><i class="material-icons text-sm align-middle">phone</i> <?php echo sanitize($user['phone'] ?? '-'); ?></span>
                                <span class="text-sm me-3"><i class="material-icons text-sm align-middle">location_on</i> <?php echo sanitize($user['address'] ?? '-'); ?></span>
                                <span class="text-sm me-3"><i class="material-icons text-sm align-middle">account_balance_wallet</i> E-Funds: <strong><?php echo format_currency($user['efunds_balance']); ?></strong></span>
                                <span class="text-sm"><i class="material-icons text-sm align-middle">savings</i> Earnings: <strong><?php echo format_currency($user['earnings_balance']); ?></strong></span>
                            </div>
                            <div class="col-auto">
                                <a href="<?php echo BASE_URL; ?>/admin/user_edit.php?id=<?php echo $id; ?>" class="btn btn-sm bg-gradient-dark mb-0">Edit</a>
                                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary mb-0">Back</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?id=<?php echo $id; ?>&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <i class="material-icons text-sm">chevron_left</i> <?php echo date('M Y', mktime(0,0,0,$prev_month,1,$prev_year)); ?>
                            </a>
                            <h6 class="mb-0"><?php echo $period_label; ?></h6>
                            <?php if (!$is_current): ?>
                            <a href="?id=<?php echo $id; ?>&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <?php echo date('M Y', mktime(0,0,0,$next_month,1,$next_year)); ?> <i class="material-icons text-sm">chevron_right</i>
                            </a>
                            <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary mb-0 disabled">Current</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">receipt_long</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Orders</p>
                            <h4 class="mb-0"><?php echo count($orders); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">local_shipping</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Delivered</p>
                            <h4 class="mb-0"><?php echo format_currency($delivered_total); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">account_balance_wallet</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">E-Funds Balance</p>
                            <h4 class="mb-0"><?php echo format_currency($user['efunds_balance']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10"><?php echo $user['role'] === 'subdealer' ? 'groups' : 'bolt'; ?></i>
                        </div>
                        <div class="text-end pt-1">
                            <?php if ($user['role'] === 'subdealer'): ?>
                            <p class="text-sm mb-0">Tagged Retailers</p>
                            <h4 class="mb-0"><?php echo count($tagged_retailers); ?></h4>
                            <?php elseif ($subsidy): ?>
                            <p class="text-sm mb-0">Subsidy</p>
                            <h4 class="mb-0"><?php echo $subsidy['eligible'] ? format_currency($subsidy['subsidy']) : '₱0.00'; ?></h4>
                            <?php else: ?>
                            <p class="text-sm mb-0">Subsidy</p>
                            <h4 class="mb-0">N/A</h4>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user['role'] === 'retailer'): ?>
        <!-- Retailer Earnings -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Earnings — <?php echo $period_label; ?></h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Details</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Electric Subsidy -->
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><i class="material-icons text-warning text-sm align-middle me-1">bolt</i> Electric Subsidy</span></td>
                                        <td>
                                            <span class="text-xs">Orders: <?php echo format_currency($subsidy['total']); ?> / Min: <?php echo format_currency($subsidy['min']); ?></span>
                                            <?php if ($subsidy['rate'] > 0): ?><br><span class="text-xs text-secondary">Rate: <?php echo round($subsidy['rate'] * 100, 1); ?>%</span><?php endif; ?>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $subsidy['eligible'] ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($subsidy['eligible'] ? $subsidy['subsidy'] : 0); ?></span></td>
                                        <td>
                                            <?php if ($subsidy_converted): ?><span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($subsidy['eligible']): ?><span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?><span class="badge bg-gradient-secondary">Not qualified</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <!-- FDA -->
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><i class="material-icons text-info text-sm align-middle me-1">ac_unit</i> Freezer Allowance</span></td>
                                        <td><span class="text-xs"><?php echo $fda['package'] ? format_currency($fda['allowance']) . '/month' : 'No package'; ?></span></td>
                                        <td><span class="text-sm font-weight-bold <?php echo ($fda['eligible'] ?? false) ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency(($fda['eligible'] ?? false) ? $fda['allowance'] : 0); ?></span></td>
                                        <td>
                                            <?php if ($fda_converted): ?><span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($fda['eligible'] ?? false): ?><span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?><span class="badge bg-gradient-secondary">Not eligible</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if ($override): ?>
                                    <!-- Town Override -->
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><i class="material-icons text-primary text-sm align-middle me-1">store</i> Town Override</span></td>
                                        <td>
                                            <span class="text-xs">Town: <?php echo sanitize($user['town'] ?? '-'); ?></span><br>
                                            <span class="text-xs text-secondary">2% of <?php echo format_currency($override['total_orders']); ?> (<?php echo count($override['breakdown']); ?> retailers)</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $override['eligible'] ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($override['eligible'] ? $override['override_amount'] : 0); ?></span></td>
                                        <td>
                                            <?php if ($override_converted): ?><span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($override['eligible']): ?><span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?><span class="badge bg-gradient-secondary">No earnings</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user['role'] === 'subdealer'): ?>
        <!-- Subdealer Earnings -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header pb-0"><h6>Gross Retail Over-Ride — <?php echo $period_label; ?></h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Delivered Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Over-Ride</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($agent_subsidy['breakdown'])): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No retailer data</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($agent_subsidy['breakdown'] as $b): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($b['name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($b['package']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($b['orders_total']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($b['subsidy']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td class="ps-4" colspan="2">
                                            <span class="text-sm font-weight-bold">TOTAL</span>
                                            <span class="text-xs text-secondary ms-2">(Min: <?php echo format_currency($agent_subsidy['min']); ?>)</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($agent_subsidy['grand_total']); ?></span></td>
                                        <td>
                                            <span class="text-sm font-weight-bold text-success"><?php echo format_currency($agent_subsidy['total_subsidy']); ?></span>
                                            <?php if ($agent_subsidy['eligible']): ?>
                                            <span class="badge bg-gradient-success ms-1">Qualified</span>
                                            <?php else: ?>
                                            <span class="badge bg-gradient-warning ms-1">Not yet</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header pb-0"><h6>Registration Commissions</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($commissions)): ?>
                                    <tr><td colspan="3" class="text-center text-sm py-4">No commissions</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($commissions as $c): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold"><?php echo sanitize($c['retailer_name']); ?></span><br>
                                            <span class="text-xs text-secondary"><?php echo sanitize($c['package_name'] ?? $c['package_slug']); ?></span>
                                        </td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($c['amount']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $c['status'] === 'credited' ? 'success' : 'warning'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tagged Retailers -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Tagged Retailers (<?php echo count($tagged_retailers); ?>)</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Town</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Registered</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tagged_retailers)): ?>
                                    <tr><td colspan="6" class="text-center text-sm py-4">No tagged retailers</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($tagged_retailers as $r): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($r['full_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($r['package_name'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($r['town'] ?? '-'); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span></td>
                                        <td><a href="?id=<?php echo $r['id']; ?>" class="btn btn-sm bg-gradient-info mb-0 py-1 px-2">View</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Orders -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-6"><h6>Orders — <?php echo $period_label; ?> (<?php echo count($orders); ?>)</h6></div>
                            <div class="col-6 text-end">
                                <span class="text-xs me-2">
                                    <?php echo $order_counts['delivered']; ?> delivered &middot;
                                    <?php echo $order_counts['pending']; ?> pending &middot;
                                    <?php echo $order_counts['cancelled']; ?> cancelled
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Payment</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                    <tr><td colspan="6" class="text-center text-sm py-4">No orders this month</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $o['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($o['payment_method']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><a href="<?php echo BASE_URL; ?>/admin/order_view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm bg-gradient-dark mb-0 py-1 px-2">View</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- E-Funds Transactions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>E-Funds Transactions — <?php echo $period_label; ?></h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Description</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Balance After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($efunds_txns)): ?>
                                    <tr><td colspan="5" class="text-center text-sm py-4">No transactions this month</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($efunds_txns as $t): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y g:i A', strtotime($t['created_at'])); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $t['type'] === 'reload' ? 'success' : ($t['type'] === 'payment' ? 'danger' : 'info'); ?>"><?php echo ucfirst($t['type']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($t['description'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs font-weight-bold <?php echo $t['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($t['amount'] >= 0 ? '+' : '') . format_currency($t['amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($t['balance_after']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Details -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Personal Information</h6></div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td class="text-xs text-secondary">Full Name</td><td class="text-xs"><?php echo sanitize($user['full_name']); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Birthday</td><td class="text-xs"><?php echo $user['birthday'] ? date('M d, Y', strtotime($user['birthday'])) : '-'; ?></td></tr>
                            <tr><td class="text-xs text-secondary">Gender</td><td class="text-xs"><?php echo $user['gender'] === 'M' ? 'Male' : ($user['gender'] === 'F' ? 'Female' : '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Phone</td><td class="text-xs"><?php echo sanitize($user['phone'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Email</td><td class="text-xs"><?php echo sanitize($user['email'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Province</td><td class="text-xs"><?php echo sanitize($user['province'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Town</td><td class="text-xs"><?php echo sanitize($user['town'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Barangay</td><td class="text-xs"><?php echo sanitize($user['barangay'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Purok/Subdivision</td><td class="text-xs"><?php echo sanitize($user['purok_subdivision'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">SSS/GSIS</td><td class="text-xs"><?php echo sanitize($user['sss_gsis'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">TIN</td><td class="text-xs"><?php echo sanitize($user['tin'] ?? '-'); ?></td></tr>
                            <?php if ($user['agent_name']): ?>
                            <tr><td class="text-xs text-secondary">Agent</td><td class="text-xs"><?php echo sanitize($user['agent_name']); ?></td></tr>
                            <?php endif; ?>
                            <tr><td class="text-xs text-secondary">Registered</td><td class="text-xs"><?php echo date('M d, Y g:i A', strtotime($user['created_at'])); ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <?php if ($user['role'] === 'retailer'): ?>
                <div class="card mb-4">
                    <div class="card-header pb-0"><h6>Application Details</h6></div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><td class="text-xs text-secondary">Package</td><td class="text-xs"><?php echo sanitize($user['package_name'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Application Type</td><td class="text-xs"><?php echo $user['application_type'] ? strtoupper(str_replace('_', ' ', $user['application_type'])) : '-'; ?></td></tr>
                            <tr><td class="text-xs text-secondary">Auth. Representative</td><td class="text-xs"><?php echo sanitize($user['auth_rep_name'] ?? '-'); ?> <?php echo $user['auth_rep_relationship'] ? '(' . sanitize($user['auth_rep_relationship']) . ')' : ''; ?></td></tr>
                            <tr><td class="text-xs text-secondary">Freezer</td><td class="text-xs"><?php echo sanitize($user['freezer_brand'] ?? '-'); ?> <?php echo sanitize($user['freezer_size'] ?? ''); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Freezer Serial</td><td class="text-xs"><?php echo sanitize($user['freezer_serial'] ?? '-'); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Freezer Status</td><td class="text-xs"><?php echo sanitize($user['freezer_status'] ?? '-'); ?></td></tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                <div class="card">
                    <div class="card-header pb-0"><h6>Package Benefits</h6></div>
                    <div class="card-body">
                        <?php if ($user['package_name']): ?>
                        <table class="table table-sm">
                            <tr><td class="text-xs text-secondary">Package</td><td class="text-xs font-weight-bold"><?php echo sanitize($user['package_name']); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Subsidy Rate</td><td class="text-xs"><?php echo round(($user['subsidy_rate'] ?? 0) * 100, 1); ?>%</td></tr>
                            <tr><td class="text-xs text-secondary">Min. Orders for Subsidy</td><td class="text-xs"><?php echo format_currency($user['subsidy_min_orders'] ?? 0); ?></td></tr>
                            <tr><td class="text-xs text-secondary">Freezer Allowance</td><td class="text-xs"><?php echo format_currency($user['freezer_display_allowance'] ?? 0); ?>/month</td></tr>
                            <tr><td class="text-xs text-secondary">Reg. Commission</td><td class="text-xs"><?php echo format_currency($user['registration_commission'] ?? 0); ?></td></tr>
                        </table>
                        <?php else: ?>
                        <p class="text-sm text-muted">No package assigned.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
