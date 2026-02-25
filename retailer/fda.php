<?php
$page_title = 'Freezer Display Allowance';
$active_page = 'fda';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

// Handle convert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert'])) {
    $fda = calculate_fda($conn, $uid, $month, $year);
    if ($fda['eligible']) {
        $check = $conn->query("SELECT id FROM freezer_allowance WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1");
        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO freezer_allowance (user_id, month, year, total_orders_amount, allowance_amount, converted, converted_at) VALUES (?, ?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE total_orders_amount=VALUES(total_orders_amount), allowance_amount=VALUES(allowance_amount), converted=1, converted_at=NOW()");
            $stmt->bind_param("iiidd", $uid, $month, $year, $fda['total'], $fda['allowance']);
            $stmt->execute();
            $stmt->close();

            credit_efunds($conn, $uid, $fda['allowance'], 'fda', 'fda', null,
                'Freezer Display Allowance for ' . date('F Y') . ' (' . format_currency($fda['allowance']) . ')');

            flash_message('success', 'Freezer Display Allowance of ' . format_currency($fda['allowance']) . ' converted to e-funds!');
        } else {
            flash_message('warning', 'Allowance already converted for this month.');
        }
    } else {
        flash_message('danger', 'Not eligible for Freezer Display Allowance this month.');
    }
    redirect(BASE_URL . '/retailer/fda.php');
}

$fda = calculate_fda($conn, $uid, $month, $year);
$already_converted = $conn->query("SELECT id FROM freezer_allowance WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1")->num_rows > 0;

// History
$history = $conn->query("SELECT * FROM freezer_allowance WHERE user_id = $uid ORDER BY year DESC, month DESC");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Current Month -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6><i class="material-icons align-middle">ac_unit</i> Freezer Display Allowance - <?php echo date('F Y'); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if ($fda['package']): ?>
                        <p class="text-sm">
                            Your package: <strong><?php echo sanitize($fda['package']); ?></strong> —
                            Earn <?php echo format_currency($fda['allowance']); ?>/month freezer display allowance when your monthly delivered orders reach <?php echo format_currency($fda['min']); ?>.
                        </p>
                        <?php else: ?>
                        <div class="alert alert-warning text-white text-sm">No package assigned to your account. Please contact admin to set your package for FDA eligibility.</div>
                        <?php endif; ?>

                        <hr>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <p class="text-sm mb-0">Your Orders</p>
                                <h4><?php echo format_currency($fda['total']); ?></h4>
                            </div>
                            <div class="col-4">
                                <p class="text-sm mb-0">Minimum Required</p>
                                <h4><?php echo format_currency($fda['min']); ?></h4>
                            </div>
                            <div class="col-4">
                                <p class="text-sm mb-0">Allowance</p>
                                <h4 class="<?php echo $fda['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $fda['eligible'] ? format_currency($fda['allowance']) : '₱0.00'; ?>
                                </h4>
                            </div>
                        </div>

                        <?php if ($fda['min'] > 0): ?>
                        <div class="progress subsidy-progress mb-3">
                            <div class="progress-bar bg-gradient-<?php echo $fda['eligible'] ? 'success' : 'info'; ?>"
                                 style="width: <?php echo min(100, ($fda['total'] / $fda['min']) * 100); ?>%">
                                <?php echo round(($fda['total'] / $fda['min']) * 100, 1); ?>%
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($fda['eligible']): ?>
                            <?php if ($already_converted): ?>
                            <div class="alert alert-success text-white">
                                <i class="material-icons align-middle">check_circle</i>
                                Freezer Display Allowance already converted to e-funds for this month!
                            </div>
                            <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="convert" value="1" class="btn bg-gradient-success w-100"
                                        onclick="return confirm('Convert <?php echo format_currency($fda['allowance']); ?> Freezer Display Allowance to e-funds?')">
                                    <i class="material-icons">ac_unit</i> Convert <?php echo format_currency($fda['allowance']); ?> to E-Funds
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning text-white text-sm">
                            You need <?php echo format_currency($fda['min'] - $fda['total']); ?> more in delivered orders to qualify.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header pb-0"><h6>Allowance History</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Allowance</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($history->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No allowance history yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($h = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo date('F', mktime(0,0,0,$h['month'],1)) . ' ' . $h['year']; ?></span></td>
                                        <td><span class="text-sm"><?php echo format_currency($h['total_orders_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($h['allowance_amount']); ?></span></td>
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
