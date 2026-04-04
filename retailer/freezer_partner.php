<?php
$page_title = 'Freezer Partner';
$active_page = 'freezer_partner';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

$partner = calculate_freezer_partner($conn, $uid, $month, $year);

// Handle convert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
    if ($partner['eligible']) {
        $check = $conn->query("SELECT id FROM freezer_partner WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1");
        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO freezer_partner (user_id, month, year, total_orders_amount, partner_amount, converted, converted_at) VALUES (?, ?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE total_orders_amount=VALUES(total_orders_amount), partner_amount=VALUES(partner_amount), converted=1, converted_at=NOW()");
            $stmt->bind_param("iiidd", $uid, $month, $year, $partner['total_orders'], $partner['partner_amount']);
            $stmt->execute();
            $stmt->close();

            credit_efunds($conn, $uid, $partner['partner_amount'], 'subsidy', 'freezer_partner', null,
                'Freezer partner earnings for ' . date('F Y') . ' (3% of ' . format_currency($partner['total_orders']) . ', code: ' . $partner['freezer_code'] . ')');

            flash_message('success', 'Freezer partner earnings of ' . format_currency($partner['partner_amount']) . ' converted to e-funds!');
        } else {
            flash_message('warning', 'Freezer partner earnings already converted for this month.');
        }
    } else {
        flash_message('danger', 'Not eligible for freezer partner earnings this month.');
    }
    redirect(BASE_URL . '/retailer/freezer_partner.php');
}

$already_converted = $conn->query("SELECT id FROM freezer_partner WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1")->num_rows > 0;

// History
$history = $conn->query("SELECT * FROM freezer_partner WHERE user_id = $uid ORDER BY year DESC, month DESC");

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
                        <h6><i class="material-icons align-middle">handshake</i> Freezer Partner Earnings - <?php echo date('F Y'); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if (!$partner['is_ice_cream_house']): ?>
                        <div class="alert alert-info text-white text-sm">
                            <i class="material-icons align-middle">info</i>
                            This benefit is exclusive to <strong>Ice Cream House</strong> package holders.
                            You earn 3% from every delivered order of retailers tagged with your freezer code.
                        </div>
                        <?php elseif (empty($partner['freezer_code'])): ?>
                        <div class="alert alert-warning text-white text-sm">
                            <i class="material-icons align-middle">warning</i>
                            Your freezer code is not set. Please contact admin to assign your freezer code.
                        </div>
                        <?php else: ?>
                        <p class="text-sm">
                            As an <strong>Ice Cream House</strong> retailer with freezer code <strong><?php echo sanitize($partner['freezer_code']); ?></strong>,
                            you earn <strong>3%</strong> from every delivered order of retailers tagged with your freezer code.
                        </p>
                        <p class="text-sm mb-1"><strong>Formula:</strong> Partner Orders x 3%</p>

                        <hr>
                        <div class="row text-center mb-3">
                            <div class="col-3">
                                <p class="text-sm mb-0">Freezer Code</p>
                                <h4><?php echo sanitize($partner['freezer_code']); ?></h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Partner Retailers</p>
                                <h4><?php echo $partner['partner_count']; ?></h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Partners' Orders</p>
                                <h4><?php echo format_currency($partner['total_orders']); ?></h4>
                            </div>
                            <div class="col-3">
                                <p class="text-sm mb-0">Your 3%</p>
                                <h4 class="<?php echo $partner['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $partner['eligible'] ? format_currency($partner['partner_amount']) : '₱0.00'; ?>
                                </h4>
                            </div>
                        </div>

                        <?php if ($partner['eligible']): ?>
                            <?php if ($already_converted): ?>
                            <div class="alert alert-success text-white">
                                <i class="material-icons align-middle">check_circle</i>
                                Freezer partner earnings already converted to e-funds for this month!
                            </div>
                            <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="convert" value="1" class="btn bg-gradient-success w-100"
                                        onclick="return confirm('Convert <?php echo format_currency($partner['partner_amount']); ?> freezer partner earnings to e-funds?')">
                                    <i class="material-icons">handshake</i> Convert <?php echo format_currency($partner['partner_amount']); ?> to E-Funds
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning text-white text-sm">
                            No delivered orders from your freezer partner retailers this month yet.
                        </div>
                        <?php endif; ?>

                        <!-- Breakdown Table -->
                        <?php if (!empty($partner['breakdown'])): ?>
                        <h6 class="text-sm mt-4 mb-2">Breakdown by Partner Retailer</h6>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Delivered Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($partner['breakdown'] as $b): ?>
                                    <tr>
                                        <td class="ps-2"><span class="text-xs"><?php echo sanitize($b['name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($b['package']); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($b['orders_total']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($b['earning']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-light">
                                        <td class="ps-2" colspan="2"><span class="text-sm font-weight-bold">TOTAL</span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($partner['total_orders']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($partner['partner_amount']); ?></span></td>
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
                    <div class="card-header pb-0"><h6>Freezer Partner History</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Partner Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Earnings (3%)</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($history->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No freezer partner history yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($h = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo date('F', mktime(0,0,0,$h['month'],1)) . ' ' . $h['year']; ?></span></td>
                                        <td><span class="text-sm"><?php echo format_currency($h['total_orders_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($h['partner_amount']); ?></span></td>
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
