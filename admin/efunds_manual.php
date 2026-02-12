<?php
$page_title = 'Manual E-Funds';
$active_page = 'efunds';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($user_id > 0 && $amount != 0 && !empty($reason)) {
        $type = $amount > 0 ? 'reload' : 'adjustment';
        credit_efunds($conn, $user_id, $amount, $type, 'manual', null, 'Manual: ' . $reason, current_user_id());
        flash_message('success', 'E-funds adjusted by ' . format_currency($amount) . ' successfully.');
        redirect(BASE_URL . '/admin/efunds_manual.php');
    } else {
        flash_message('danger', 'All fields are required. Amount cannot be zero.');
    }
}

$users = $conn->query("SELECT id, full_name, username, role, efunds_balance FROM users WHERE status = 'active' ORDER BY full_name");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Manual E-Funds Adjustment</h6></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="input-group input-group-static my-3">
                                <label class="ms-0">Select User *</label>
                                <select name="user_id" class="form-control" required>
                                    <option value="">-- Select User --</option>
                                    <?php while ($u = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo sanitize($u['full_name']); ?> (<?php echo $u['username']; ?>) - Balance: <?php echo format_currency($u['efunds_balance']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Amount (positive=credit, negative=debit) *</label>
                                <input type="number" name="amount" class="form-control" step="0.01" required>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Reason/Notes *</label>
                                <input type="text" name="reason" class="form-control" required>
                            </div>
                            <button type="submit" class="btn bg-gradient-primary" onclick="return confirm('Are you sure you want to adjust e-funds?')">Apply Adjustment</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Recent Transactions</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">User</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $txns = $conn->query("
                                        SELECT et.*, u.full_name FROM efunds_transactions et
                                        JOIN users u ON et.user_id = u.id
                                        ORDER BY et.created_at DESC LIMIT 20
                                    ");
                                    while ($t = $txns->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs"><?php echo sanitize($t['full_name']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $t['amount'] >= 0 ? 'success' : 'danger'; ?>"><?php echo ucfirst($t['type']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold <?php echo $t['amount'] >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ($t['amount'] >= 0 ? '+' : '') . format_currency($t['amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, h:i A', strtotime($t['created_at'])); ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
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
