<?php
$page_title = 'E-Funds';
$active_page = 'efunds';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$user = $conn->query("SELECT efunds_balance FROM users WHERE id = $uid")->fetch_assoc();

$transactions = $conn->query("
    SELECT * FROM efunds_transactions
    WHERE user_id = $uid
    ORDER BY created_at DESC
    LIMIT 50
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Balance Card -->
        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <div class="card bg-gradient-primary">
                    <div class="card-body text-center text-white p-4">
                        <i class="material-icons" style="font-size:48px;">account_balance_wallet</i>
                        <p class="text-sm mb-1 opacity-8">Current Balance</p>
                        <h2 class="mb-3"><?php echo format_currency($user['efunds_balance']); ?></h2>
                        <a href="<?php echo BASE_URL; ?>/retailer/reload.php" class="btn btn-white btn-sm">
                            <i class="material-icons text-sm">add</i> Reload E-Funds
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Transaction History</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Description</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($transactions->num_rows === 0): ?>
                                    <tr><td colspan="5" class="text-center text-sm py-4">No transactions yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($t = $transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo date('M d, h:i A', strtotime($t['created_at'])); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $t['amount'] >= 0 ? 'success' : 'danger'; ?>"><?php echo ucfirst($t['type']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($t['description'] ?? '-'); ?></span></td>
                                        <td class="text-end"><span class="text-sm font-weight-bold <?php echo $t['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($t['amount'] >= 0 ? '+' : '') . format_currency($t['amount']); ?></span></td>
                                        <td class="text-end"><span class="text-xs"><?php echo format_currency($t['balance_after']); ?></span></td>
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
