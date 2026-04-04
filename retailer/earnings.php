<?php
$page_title = 'My Earnings';
$active_page = 'earnings';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();

// Get user info
$user = $conn->query("SELECT u.*, p.slug as package_slug, p.name as package_name FROM users u LEFT JOIN packages p ON u.package_info = p.slug WHERE u.id = $uid")->fetch_assoc();
$is_ice_cream_house = ($user['package_slug'] ?? '') === 'ice_cream_house';

// Month filter
$filter_month = (int)($_GET['month'] ?? date('m'));
$filter_year = (int)($_GET['year'] ?? date('Y'));
if ($filter_month < 1 || $filter_month > 12) $filter_month = (int)date('m');
if ($filter_year < 2020 || $filter_year > 2099) $filter_year = (int)date('Y');

$period_label = date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year));

// === 1. Town Override Earnings (2% from Starter/Premium in same town) ===
$override = calculate_town_override($conn, $uid, $filter_month, $filter_year);
$override_converted = $conn->query("SELECT id FROM town_override WHERE user_id = $uid AND month = $filter_month AND year = $filter_year AND converted = 1")->num_rows > 0;

// === 2. Electric Subsidy ===
$subsidy = calculate_subsidy($conn, $uid, $filter_month, $filter_year);
$subsidy_converted = $conn->query("SELECT id FROM electric_subsidy WHERE user_id = $uid AND month = $filter_month AND year = $filter_year AND converted = 1")->num_rows > 0;

// === 3. Freezer Partner (ICH only - 3% from partner retailers) ===
$freezer_partner = calculate_freezer_partner($conn, $uid, $filter_month, $filter_year);
$freezer_partner_converted = $conn->query("SELECT id FROM freezer_partner WHERE user_id = $uid AND month = $filter_month AND year = $filter_year AND converted = 1")->num_rows > 0;

// === 4. FDA ===
$fda = calculate_fda($conn, $uid, $filter_month, $filter_year);
$fda_converted = $conn->query("SELECT id FROM freezer_allowance WHERE user_id = $uid AND month = $filter_month AND year = $filter_year AND converted = 1")->num_rows > 0;

// === 4. Total converted e-funds this month (from efunds_transactions) ===
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM efunds_transactions WHERE user_id = ? AND type = 'subsidy' AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
$stmt->bind_param("iii", $uid, $filter_month, $filter_year);
$stmt->execute();
$total_converted = (float)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// === 5. Transaction-level detail: all delivered orders from town retailers (for ICH) ===
$town_transactions = [];
if ($is_ice_cream_house && !empty($user['town'])) {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_number, o.total_amount, o.delivered_at,
               u.full_name as retailer_name, p.name as package_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN packages p ON u.package_info = p.slug
        WHERE u.town = ? AND u.id != ?
          AND p.slug IN ('starter_pack', 'premium_pack')
          AND u.role = 'retailer' AND u.status = 'active'
          AND o.status = 'delivered'
          AND MONTH(o.delivered_at) = ? AND YEAR(o.delivered_at) = ?
        ORDER BY o.delivered_at DESC
    ");
    $stmt->bind_param("siii", $user['town'], $uid, $filter_month, $filter_year);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $factor = $override['factor'] ?? 0.63;
        $row['override_amount'] = round($row['total_amount'] * $factor * 0.02, 2);
        $row['rebate_amount'] = round($row['total_amount'] * $factor * 0.035, 2);
        $town_transactions[] = $row;
    }
    $stmt->close();
}

// Grand totals for summary
$subsidy_amount = $subsidy['eligible'] ? $subsidy['subsidy'] : 0;
$fda_amount = $fda['eligible'] ? $fda['allowance'] : 0;
$override_amount = $override['eligible'] ? $override['override_amount'] : 0;
$rebate_amount = $override['eligible'] ? ($override['rebate_amount'] ?? 0) : 0;
$freezer_partner_amount = $freezer_partner['eligible'] ? $freezer_partner['partner_amount'] : 0;
$grand_total_earnings = $subsidy_amount + $fda_amount + $override_amount + $rebate_amount + $freezer_partner_amount;

// Navigation months
$prev_month = $filter_month - 1;
$prev_year = $filter_year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $filter_month + 1;
$next_year = $filter_year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
$is_current = $filter_month === (int)date('m') && $filter_year === (int)date('Y');

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Month Navigation -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <i class="material-icons text-sm">chevron_left</i> <?php echo date('M Y', mktime(0,0,0,$prev_month,1,$prev_year)); ?>
                            </a>
                            <h5 class="mb-0"><i class="material-icons align-middle">trending_up</i> Earnings — <?php echo $period_label; ?></h5>
                            <?php if (!$is_current): ?>
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-sm btn-outline-dark mb-0">
                                <?php echo date('M Y', mktime(0,0,0,$next_month,1,$next_year)); ?> <i class="material-icons text-sm">chevron_right</i>
                            </a>
                            <?php else: ?>
                            <span class="btn btn-sm btn-outline-secondary mb-0 disabled">Current Month</span>
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
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">account_balance_wallet</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Earnings</p>
                            <h4 class="mb-0"><?php echo format_currency($grand_total_earnings); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($is_ice_cream_house): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">store</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Town Override (2%)</p>
                            <h4 class="mb-0"><?php echo format_currency($override_amount); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">redeem</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Re-order Rebate (3.5%)</p>
                            <h4 class="mb-0"><?php echo format_currency($rebate_amount); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($freezer_partner_amount > 0 || !empty($freezer_partner['freezer_code'])): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-secondary shadow text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">handshake</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Freezer Partner (3%)</p>
                            <h4 class="mb-0"><?php echo format_currency($freezer_partner_amount); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-warning shadow-warning text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">bolt</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Electric Subsidy</p>
                            <h4 class="mb-0"><?php echo format_currency($subsidy_amount); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">ac_unit</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Freezer Allowance</p>
                            <h4 class="mb-0"><?php echo format_currency($fda_amount); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Breakdown -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Earnings Breakdown</h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Earning Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Details</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($is_ice_cream_house): ?>
                                    <!-- Town Override -->
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-info me-2">store</i>
                                                <div>
                                                    <h6 class="mb-0 text-sm">Town Override</h6>
                                                    <p class="text-xs text-secondary mb-0">2% from Starter & Premium in <?php echo sanitize($user['town'] ?? 'N/A'); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs">Town orders: <?php echo format_currency($override['total_orders']); ?></span><br>
                                            <span class="text-xs text-secondary"><?php echo count($override['breakdown']); ?> retailer(s)</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $override_amount > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($override_amount); ?></span></td>
                                        <td>
                                            <?php if ($override_converted): ?>
                                                <span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($override_amount > 0): ?>
                                                <span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">No earnings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/retailer/town_override.php" class="btn btn-sm bg-gradient-dark mb-0">View</a>
                                        </td>
                                    </tr>

                                    <!-- ICH Re-order Rebate -->
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-dark me-2">redeem</i>
                                                <div>
                                                    <h6 class="mb-0 text-sm">Re-order Rebate</h6>
                                                    <p class="text-xs text-secondary mb-0">3.5% from Starter & Premium in <?php echo sanitize($user['town'] ?? 'N/A'); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs">Town orders: <?php echo format_currency($override['total_orders']); ?></span><br>
                                            <span class="text-xs text-secondary"><?php echo count($override['breakdown']); ?> retailer(s)</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $rebate_amount > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($rebate_amount); ?></span></td>
                                        <td>
                                            <?php if ($override_converted): ?>
                                                <span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($rebate_amount > 0): ?>
                                                <span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">No earnings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/retailer/town_override.php" class="btn btn-sm bg-gradient-dark mb-0">View</a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php if ($is_ice_cream_house && !empty($freezer_partner['freezer_code'])): ?>
                                    <!-- Freezer Partner -->
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-secondary me-2">handshake</i>
                                                <div>
                                                    <h6 class="mb-0 text-sm">Freezer Partner</h6>
                                                    <p class="text-xs text-secondary mb-0">3% from partners (code: <?php echo sanitize($freezer_partner['freezer_code']); ?>)</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs">Partner orders: <?php echo format_currency($freezer_partner['total_orders']); ?></span><br>
                                            <span class="text-xs text-secondary"><?php echo $freezer_partner['partner_count']; ?> partner(s)</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $freezer_partner_amount > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($freezer_partner_amount); ?></span></td>
                                        <td>
                                            <?php if ($freezer_partner_converted): ?>
                                                <span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($freezer_partner_amount > 0): ?>
                                                <span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">No earnings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/retailer/freezer_partner.php" class="btn btn-sm bg-gradient-dark mb-0">View</a>
                                        </td>
                                    </tr>
                                    <?php endif; ?>

                                    <!-- Electric Subsidy -->
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-warning me-2">bolt</i>
                                                <div>
                                                    <h6 class="mb-0 text-sm">Electric Subsidy</h6>
                                                    <p class="text-xs text-secondary mb-0"><?php echo $subsidy['package'] ? round($subsidy['rate'] * 100, 1) . '% rate' : 'No package'; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs">Your orders: <?php echo format_currency($subsidy['total']); ?></span><br>
                                            <span class="text-xs text-secondary">Min: <?php echo format_currency($subsidy['min']); ?></span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $subsidy_amount > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($subsidy_amount); ?></span></td>
                                        <td>
                                            <?php if ($subsidy_converted): ?>
                                                <span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($subsidy['eligible']): ?>
                                                <span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">Not qualified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/retailer/subsidy.php" class="btn btn-sm bg-gradient-dark mb-0">View</a>
                                        </td>
                                    </tr>

                                    <!-- FDA -->
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-primary me-2">ac_unit</i>
                                                <div>
                                                    <h6 class="mb-0 text-sm">Freezer Display Allowance</h6>
                                                    <p class="text-xs text-secondary mb-0"><?php echo $fda['package'] ? format_currency($fda['allowance']) . '/month' : 'No package'; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs">Fixed monthly allowance</span>
                                        </td>
                                        <td><span class="text-sm font-weight-bold <?php echo $fda_amount > 0 ? 'text-success' : 'text-muted'; ?>"><?php echo format_currency($fda_amount); ?></span></td>
                                        <td>
                                            <?php if ($fda_converted): ?>
                                                <span class="badge bg-gradient-success">Converted</span>
                                            <?php elseif ($fda['eligible']): ?>
                                                <span class="badge bg-gradient-warning">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-gradient-secondary">Not eligible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/retailer/fda.php" class="btn btn-sm bg-gradient-dark mb-0">View</a>
                                        </td>
                                    </tr>

                                    <!-- Totals -->
                                    <tr class="bg-light">
                                        <td class="ps-4" colspan="2"><span class="text-sm font-weight-bold">TOTAL EARNINGS</span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($grand_total_earnings); ?></span></td>
                                        <td colspan="2">
                                            <?php if ($total_converted > 0): ?>
                                            <span class="text-xs"><?php echo format_currency($total_converted); ?> converted to e-funds</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_ice_cream_house && !empty($town_transactions)): ?>
        <!-- Town Override Transaction Detail -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex align-items-center">
                            <i class="material-icons text-info me-2">store</i>
                            <h6 class="mb-0">Town Override Transactions — <?php echo sanitize($user['town']); ?></h6>
                        </div>
                        <p class="text-xs text-secondary mt-1 mb-0">Individual delivered orders from Starter Pack &amp; Premium Pack retailers in your town</p>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Order Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 2%</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3.5%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $txn_total = 0;
                                    $txn_override = 0;
                                    $txn_rebate = 0;
                                    foreach ($town_transactions as $txn):
                                        $txn_total += $txn['total_amount'];
                                        $txn_override += $txn['override_amount'];
                                        $txn_rebate += $txn['rebate_amount'];
                                    ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y', strtotime($txn['delivered_at'])); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo sanitize($txn['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($txn['retailer_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($txn['package_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($txn['total_amount']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($txn['override_amount']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($txn['rebate_amount']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td class="ps-4" colspan="4"><span class="text-sm font-weight-bold">TOTAL (<?php echo count($town_transactions); ?> transactions)</span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($txn_total); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($txn_override); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($txn_rebate); ?></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- E-Funds Conversion History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>E-Funds Conversions — <?php echo $period_label; ?></h6>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Description</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Balance After</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT * FROM efunds_transactions WHERE user_id = ? AND type = 'subsidy' AND MONTH(created_at) = ? AND YEAR(created_at) = ? ORDER BY created_at DESC");
                                    $stmt->bind_param("iii", $uid, $filter_month, $filter_year);
                                    $stmt->execute();
                                    $conversions = $stmt->get_result();
                                    $stmt->close();
                                    ?>
                                    <?php if ($conversions->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No conversions this month</td></tr>
                                    <?php else: ?>
                                    <?php while ($c = $conversions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y g:i A', strtotime($c['created_at'])); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($c['description']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success">+<?php echo format_currency($c['amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($c['balance_after']); ?></span></td>
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
