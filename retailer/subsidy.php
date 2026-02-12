<?php
$page_title = 'Electric Subsidy';
$active_page = 'subsidy';

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
    $subsidy = calculate_subsidy($conn, $uid, $month, $year);
    if ($subsidy['eligible']) {
        // Check if already converted
        $check = $conn->query("SELECT id FROM electric_subsidy WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1");
        if ($check->num_rows === 0) {
            // Insert or update subsidy record
            $stmt = $conn->prepare("INSERT INTO electric_subsidy (user_id, month, year, total_orders_amount, subsidy_amount, converted, converted_at) VALUES (?, ?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE total_orders_amount=VALUES(total_orders_amount), subsidy_amount=VALUES(subsidy_amount), converted=1, converted_at=NOW()");
            $stmt->bind_param("iiidd", $uid, $month, $year, $subsidy['total'], $subsidy['subsidy']);
            $stmt->execute();
            $stmt->close();

            credit_efunds($conn, $uid, $subsidy['subsidy'], 'subsidy', 'subsidy', null,
                'Electric subsidy for ' . date('F Y') . ' (' . format_currency($subsidy['total']) . ' x 0.88 x 5%)');

            flash_message('success', 'Subsidy of ' . format_currency($subsidy['subsidy']) . ' converted to e-funds!');
        } else {
            flash_message('warning', 'Subsidy already converted for this month.');
        }
    } else {
        flash_message('danger', 'Not eligible for subsidy this month.');
    }
    redirect(BASE_URL . '/retailer/subsidy.php');
}

$subsidy = calculate_subsidy($conn, $uid, $month, $year);
$already_converted = $conn->query("SELECT id FROM electric_subsidy WHERE user_id = $uid AND month = $month AND year = $year AND converted = 1")->num_rows > 0;

// History
$history = $conn->query("SELECT * FROM electric_subsidy WHERE user_id = $uid ORDER BY year DESC, month DESC");

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
                        <h6><i class="material-icons align-middle">bolt</i> Electric Subsidy - <?php echo date('F Y'); ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="text-sm">Earn 5% electric subsidy when your monthly delivered orders reach <?php echo format_currency($subsidy['min']); ?>.</p>
                        <p class="text-sm mb-1"><strong>Formula:</strong> Total Orders x 0.88 x 5%</p>

                        <hr>
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <p class="text-sm mb-0">Your Orders</p>
                                <h4><?php echo format_currency($subsidy['total']); ?></h4>
                            </div>
                            <div class="col-4">
                                <p class="text-sm mb-0">Minimum Required</p>
                                <h4><?php echo format_currency($subsidy['min']); ?></h4>
                            </div>
                            <div class="col-4">
                                <p class="text-sm mb-0">Subsidy Amount</p>
                                <h4 class="<?php echo $subsidy['eligible'] ? 'text-success' : 'text-muted'; ?>">
                                    <?php echo $subsidy['eligible'] ? format_currency($subsidy['subsidy']) : '₱0.00'; ?>
                                </h4>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="progress subsidy-progress mb-3">
                            <div class="progress-bar bg-gradient-<?php echo $subsidy['eligible'] ? 'success' : 'warning'; ?>"
                                 style="width: <?php echo min(100, ($subsidy['total'] / $subsidy['min']) * 100); ?>%">
                                <?php echo round(($subsidy['total'] / $subsidy['min']) * 100, 1); ?>%
                            </div>
                        </div>

                        <?php if ($subsidy['eligible']): ?>
                            <?php if ($already_converted): ?>
                            <div class="alert alert-success text-white">
                                <i class="material-icons align-middle">check_circle</i>
                                Subsidy already converted to e-funds for this month!
                            </div>
                            <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="convert" value="1" class="btn bg-gradient-success w-100"
                                        onclick="return confirm('Convert <?php echo format_currency($subsidy['subsidy']); ?> subsidy to e-funds?')">
                                    <i class="material-icons">bolt</i> Convert <?php echo format_currency($subsidy['subsidy']); ?> to E-Funds
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning text-white text-sm">
                            You need <?php echo format_currency($subsidy['min'] - $subsidy['total']); ?> more in delivered orders to qualify.
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
                    <div class="card-header pb-0"><h6>Subsidy History</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Subsidy</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($history->num_rows === 0): ?>
                                    <tr><td colspan="4" class="text-center text-sm py-4">No subsidy history yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($h = $history->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm"><?php echo date('F', mktime(0,0,0,$h['month'],1)) . ' ' . $h['year']; ?></span></td>
                                        <td><span class="text-sm"><?php echo format_currency($h['total_orders_amount']); ?></span></td>
                                        <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($h['subsidy_amount']); ?></span></td>
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
