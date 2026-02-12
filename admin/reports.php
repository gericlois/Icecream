<?php
$page_title = 'Reports';
$active_page = 'reports';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to = $_GET['to'] ?? date('Y-m-d');
$group_by = $_GET['group'] ?? 'product';

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');

    if ($group_by === 'product') {
        fputcsv($out, ['Product', 'Flavor', 'Packs Sold', 'Units Sold', 'Revenue']);
        $data = $conn->query("
            SELECT oi.product_name, oi.flavor_name, SUM(oi.quantity_packs) as total_packs,
                   SUM(oi.quantity_units) as total_units, SUM(oi.line_total) as revenue
            FROM order_items oi JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'delivered' AND DATE(o.delivered_at) BETWEEN '$date_from' AND '$date_to'
            GROUP BY oi.product_name, oi.flavor_name ORDER BY revenue DESC
        ");
        while ($r = $data->fetch_assoc()) {
            fputcsv($out, [$r['product_name'], $r['flavor_name'], $r['total_packs'], $r['total_units'], $r['revenue']]);
        }
    }
    fclose($out);
    exit;
}

// Query based on grouping
$report_data = null;
if ($group_by === 'product') {
    $report_data = $conn->query("
        SELECT oi.product_name, oi.flavor_name, SUM(oi.quantity_packs) as total_packs,
               SUM(oi.quantity_units) as total_units, SUM(oi.line_total) as revenue
        FROM order_items oi JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'delivered' AND DATE(o.delivered_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY oi.product_name, oi.flavor_name ORDER BY revenue DESC
    ");
} elseif ($group_by === 'customer') {
    $report_data = $conn->query("
        SELECT u.full_name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
        FROM orders o JOIN users u ON o.user_id = u.id
        WHERE o.status = 'delivered' AND DATE(o.delivered_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY o.user_id ORDER BY revenue DESC
    ");
} elseif ($group_by === 'agent') {
    $report_data = $conn->query("
        SELECT COALESCE(a.full_name, 'Direct') as agent_name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
        FROM orders o LEFT JOIN users a ON o.agent_id = a.id
        WHERE o.status = 'delivered' AND DATE(o.delivered_at) BETWEEN '$date_from' AND '$date_to'
        GROUP BY o.agent_id ORDER BY revenue DESC
    ");
}

// Total
$total_result = $conn->query("
    SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total
    FROM orders WHERE status = 'delivered' AND DATE(delivered_at) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-3">
                                <div class="input-group input-group-outline is-filled">
                                    <label class="form-label">From</label>
                                    <input type="date" name="from" class="form-control" value="<?php echo $date_from; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline is-filled">
                                    <label class="form-label">To</label>
                                    <input type="date" name="to" class="form-control" value="<?php echo $date_to; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-static">
                                    <label class="ms-0">Group By</label>
                                    <select name="group" class="form-control">
                                        <option value="product" <?php echo $group_by === 'product' ? 'selected' : ''; ?>>Product</option>
                                        <option value="customer" <?php echo $group_by === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="agent" <?php echo $group_by === 'agent' ? 'selected' : ''; ?>>Agent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn bg-gradient-primary mb-0">Generate</button>
                                <a href="?from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>&group=<?php echo $group_by; ?>&export=csv" class="btn bg-gradient-dark mb-0">Export CSV</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-sm mb-0">Total Delivered Orders</h6>
                        <h3><?php echo $total_result['cnt']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-sm mb-0">Total Revenue</h6>
                        <h3 class="text-success"><?php echo format_currency($total_result['total']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Report: By <?php echo ucfirst($group_by); ?></h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <?php if ($group_by === 'product'): ?>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Product</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Flavor</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Packs</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Units</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Revenue</th>
                                        <?php elseif ($group_by === 'customer'): ?>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Revenue</th>
                                        <?php elseif ($group_by === 'agent'): ?>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Agent</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Revenue</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($report_data && $report_data->num_rows > 0): ?>
                                    <?php while ($r = $report_data->fetch_assoc()): ?>
                                    <tr>
                                        <?php if ($group_by === 'product'): ?>
                                        <td class="ps-4"><span class="text-sm"><?php echo sanitize($r['product_name']); ?></span></td>
                                        <td><span class="text-sm"><?php echo sanitize($r['flavor_name']); ?></span></td>
                                        <td><span class="text-sm"><?php echo $r['total_packs']; ?></span></td>
                                        <td><span class="text-sm"><?php echo $r['total_units']; ?></span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($r['revenue']); ?></span></td>
                                        <?php else: ?>
                                        <td class="ps-4"><span class="text-sm"><?php echo sanitize($r[array_key_first($r)]); ?></span></td>
                                        <td><span class="text-sm"><?php echo $r['order_count']; ?></span></td>
                                        <td><span class="text-sm font-weight-bold"><?php echo format_currency($r['revenue']); ?></span></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-sm py-4">No data for this period</td></tr>
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
