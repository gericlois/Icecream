<?php
$page_title = 'Town Override';
$active_page = 'town_override';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

$override = calculate_town_override($conn, $uid, $month, $year);

// Handle convert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
    if ($override['eligible']) {
        $check = $conn->query("SELECT id FROM town_override WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1");
        if ($check->num_rows === 0) {
            $total_town_convert = $override['override_amount'] + ($override['rebate_amount'] ?? 0);
            $stmt = $conn->prepare("INSERT INTO town_override (user_id, month, year, total_orders_amount, override_amount, converted, converted_at) VALUES (?, ?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE total_orders_amount=VALUES(total_orders_amount), override_amount=VALUES(override_amount), converted=1, converted_at=NOW()");
            $stmt->bind_param("iiidd", $uid, $month, $year, $override['total_orders'], $total_town_convert);
            $stmt->execute();
            $stmt->close();

            $total_town_earning = $override['override_amount'] + ($override['rebate_amount'] ?? 0);
            credit_efunds($conn, $uid, $total_town_earning, 'subsidy', 'town_override', null,
                'Town override & rebate for ' . date('F Y') . ' (2% + 3.5% of ' . format_currency($override['total_orders']) . ' x ' . ($override['factor'] ?? 0.63) . ' from ' . $override['town'] . ')');

            flash_message('success', 'Town override & rebate of ' . format_currency($total_town_earning) . ' converted to e-funds!');
        } else {
            flash_message('warning', 'Town override already converted for this month.');
        }
    } else {
        flash_message('danger', 'Not eligible for town override this month.');
    }
    redirect(BASE_URL . '/retailer/town_override.php');
}

$already_converted = $conn->query("SELECT id FROM town_override WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1")->num_rows > 0;

// History
$history = $conn->query("SELECT * FROM town_override WHERE user_id = $uid ORDER BY year DESC, month DESC");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Current Month -->
        <div class="row mb-4">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6><i class="material-icons align-middle">store</i> Town Override - <?php echo date('F Y'); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if (!$override['is_ice_cream_house']): ?>
                        <div class="alert alert-info text-white text-sm">
                            <i class="material-icons align-middle">info</i>
                            This benefit is exclusive to <strong>Ice Cream House</strong> package holders.
                            You earn 2% from Starter Pack and Premium Pack retailers' delivered orders in your town.
                        </div>
                        <?php elseif (empty($override['town'])): ?>
                        <div class="alert alert-warning text-white text-sm">
                            <i class="material-icons align-middle">warning</i>
                            Your town is not set in your profile. Please update your profile to enable town override calculation.
                        </div>
                        <?php else: ?>
                        <p class="text-sm">
                            As an <strong>Ice Cream House</strong> retailer in <strong><?php echo sanitize($override['town']); ?></strong>,
                            you earn <strong>2%</strong> override and <strong>3.5%</strong> re-order rebate from all delivered orders of Starter Pack &amp; Premium Pack retailers in your town.
                        </p>
                        <p class="text-sm mb-1"><strong>Override Formula:</strong> Town Orders x <?php echo $override['factor']; ?> x 2%</p>
                        <p class="text-sm mb-1"><strong>Rebate Formula:</strong> Town Orders x <?php echo $override['factor']; ?> x 3.5%</p>

                        <hr>
                        <div class="row text-center mb-3">
                            <div class="col-3">
                                <p class="text-sm mb-0">Town Retailers' Orders</p>
                                <h4><?php echo format_currency($override['total_orders']); ?></h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Override (2%)</p>
                                <h4 class="<?php echo $override['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $override['eligible'] ? format_currency($override['override_amount']) : '₱0.00'; ?>
                                </h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Rebate (3.5%)</p>
                                <h4 class="<?php echo $override['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $override['eligible'] ? format_currency($override['rebate_amount']) : '₱0.00'; ?>
                                </h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Total</p>
                                <h4 class="<?php echo $override['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $override['eligible'] ? format_currency($override['override_amount'] + $override['rebate_amount']) : '₱0.00'; ?>
                                </h4>
                            </div>
                        </div>

                        <?php if ($override['eligible']): ?>
                            <?php if ($already_converted): ?>
                            <div class="alert alert-success text-white">
                                <i class="material-icons align-middle">check_circle</i>
                                Town override already converted to e-funds for this month!
                            </div>
                            <?php else: ?>
                            <form method="POST">
                                <?php $total_town_amount = $override['override_amount'] + ($override['rebate_amount'] ?? 0); ?>
                                <button type="submit" name="convert" value="1" class="btn bg-gradient-success w-100"
                                        onclick="return confirm('Convert <?php echo format_currency($total_town_amount); ?> town override & rebate to e-funds?')">
                                    <i class="material-icons">store</i> Convert <?php echo format_currency($total_town_amount); ?> to E-Funds
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning text-white text-sm">
                            No delivered orders from Starter Pack / Premium Pack retailers in your town this month yet.
                        </div>
                        <?php endif; ?>

                        <!-- Breakdown Table -->
                        <?php if (!empty($override['breakdown'])): ?>
                        <h6 class="text-sm mt-4 mb-2">Breakdown by Retailer</h6>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Delivered Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 2%</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3.5%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($override['breakdown'] as $b): ?>
                                    <tr>
                                        <td class="ps-2"><span class="text-xs"><?php echo sanitize($b['name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($b['package']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($b['orders_total']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($b['override']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($b['rebate']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td class="ps-2" colspan="2"><span class="text-sm font-weight-bold">TOTAL</span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($override['total_orders']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($override['override_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($override['rebate_amount']); ?></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header pb-0"><h6>Town Override History</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Town Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Override (2%)</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($history->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No town override history yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($h = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo date('F', mktime(0,0,0,$h['month'],1)) . ' ' . $h['year']; ?></span></td>
                                        <td><span class="text-sm"><?php echo format_currency($h['total_orders_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($h['override_amount']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $h['converted'] ? 'success' : 'warning'; ?>"><?php echo $h['converted'] ? 'Converted' : 'Pending'; ?></span></td>
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
