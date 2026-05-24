<?php
$page_title = 'Inventory History';
$active_page = 'inventory';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$flavor_id = (int)($_GET['flavor_id'] ?? 0);
$type_filter = $_GET['type'] ?? '';
$valid_types = ['restock', 'adjustment', 'order', 'cancel_return'];

$conds = [];
if ($flavor_id > 0) $conds[] = "it.product_flavor_id = $flavor_id";
if (in_array($type_filter, $valid_types, true)) {
    $tf = $conn->real_escape_string($type_filter);
    $conds[] = "it.type = '$tf'";
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$logs = $conn->query("
    SELECT it.*, p.name AS product_name, pf.flavor_name, u.full_name AS by_name
    FROM inventory_transactions it
    LEFT JOIN product_flavors pf ON it.product_flavor_id = pf.id
    LEFT JOIN products p ON pf.product_id = p.id
    LEFT JOIN users u ON it.created_by = u.id
    $where
    ORDER BY it.id DESC
    LIMIT 500
");

$type_badges = [
    'restock' => 'bg-gradient-success',
    'adjustment' => 'bg-gradient-warning',
    'order' => 'bg-gradient-info',
    'cancel_return' => 'bg-gradient-secondary',
];

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-6"><h6>Inventory History</h6>
                                <p class="text-sm text-muted mb-0">Last 500 stock movements (newest first).</p>
                            </div>
                            <div class="col-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/admin/inventory.php" class="btn btn-sm bg-gradient-primary mb-0">
                                    <i class="material-icons text-sm">arrow_back</i> Back to Stock
                                </a>
                            </div>
                        </div>
                        <form method="GET" class="d-flex gap-2 mt-3 flex-wrap">
                            <?php if ($flavor_id > 0): ?>
                            <input type="hidden" name="flavor_id" value="<?php echo $flavor_id; ?>">
                            <?php endif; ?>
                            <select name="type" class="form-control form-control-sm" style="max-width:200px;" onchange="this.form.submit()">
                                <option value="">All types</option>
                                <?php foreach ($valid_types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $type_filter === $t ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($flavor_id > 0 || $type_filter): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/inventory_history.php" class="btn btn-sm btn-outline-secondary mb-0">Clear filters</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">When</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Product / Flavor</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Type</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Change</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Balance</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Reference</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($logs && $logs->num_rows > 0): ?>
                                        <?php while ($log = $logs->fetch_assoc()):
                                            $change = (int)$log['change_packs'];
                                            $badge = $type_badges[$log['type']] ?? 'bg-gradient-dark';
                                        ?>
                                        <tr>
                                            <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></span></td>
                                            <td>
                                                <span class="text-sm font-weight-bold"><?php echo sanitize($log['product_name'] ?? '(deleted)'); ?></span>
                                                <span class="text-xs text-secondary d-block"><?php echo sanitize($log['flavor_name'] ?? '—'); ?></span>
                                            </td>
                                            <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $log['type'])); ?></span></td>
                                            <td class="text-center">
                                                <span class="text-sm font-weight-bold <?php echo $change >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo ($change > 0 ? '+' : '') . $change; ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><span class="text-sm"><?php echo (int)$log['balance_after']; ?></span></td>
                                            <td>
                                                <?php if ($log['reference_type'] === 'order' && $log['reference_id']): ?>
                                                <a href="<?php echo BASE_URL; ?>/admin/order_view.php?id=<?php echo (int)$log['reference_id']; ?>" class="text-xs">Order #<?php echo (int)$log['reference_id']; ?></a>
                                                <?php else: ?>
                                                <span class="text-xs text-secondary"><?php echo sanitize($log['notes'] ?? '—'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="text-xs"><?php echo sanitize($log['by_name'] ?? 'System'); ?></span></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted text-sm">No stock movements recorded yet.</td></tr>
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
