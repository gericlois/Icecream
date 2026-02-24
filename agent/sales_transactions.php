<?php
$page_title = 'Sales Transactions';
$active_page = 'sales_transactions';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();

// Filters
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$retailer_filter = (int)($_GET['retailer'] ?? 0);
$sort = $_GET['sort'] ?? 'desc';
if (!in_array($sort, ['asc', 'desc'])) $sort = 'desc';

// Build query
$where = "WHERE u.agent_id = ? AND u.role = 'retailer' AND o.status = 'delivered'";
$params = [$uid];
$types = "i";

if ($date_from) {
    $where .= " AND o.delivered_at >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= "s";
}
if ($date_to) {
    $where .= " AND o.delivered_at <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= "s";
}
if ($retailer_filter > 0) {
    $where .= " AND o.user_id = ?";
    $params[] = $retailer_filter;
    $types .= "i";
}

$order_dir = $sort === 'asc' ? 'ASC' : 'DESC';

$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.delivered_at, o.payment_method,
           u.full_name as retailer_name, p.name as package_name, p.subsidy_rate
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN packages p ON u.package_info = p.slug
    $where
    ORDER BY o.delivered_at $order_dir
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// Compute totals
$rows = [];
$total_sales = 0;
$total_override = 0;
while ($row = $transactions->fetch_assoc()) {
    $rate = (float)($row['subsidy_rate'] ?? 0);
    $row['override'] = round($row['total_amount'] * $rate, 2);
    $row['rate'] = $rate;
    $total_sales += (float)$row['total_amount'];
    $total_override += $row['override'];
    $rows[] = $row;
}

// Get retailers for filter dropdown
$retailers = $conn->query("
    SELECT id, full_name FROM users
    WHERE agent_id = $uid AND role = 'retailer' AND status = 'active'
    ORDER BY full_name
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">payments</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Sales</p>
                            <h4 class="mb-0"><?php echo format_currency($total_sales); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">bolt</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Total Over-Ride</p>
                            <h4 class="mb-0"><?php echo format_currency($total_override); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">receipt_long</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0">Transactions</p>
                            <h4 class="mb-0"><?php echo count($rows); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label text-xs text-uppercase text-secondary font-weight-bolder">From Date</label>
                        <input type="date" name="from" class="form-control" value="<?php echo sanitize($date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs text-uppercase text-secondary font-weight-bolder">To Date</label>
                        <input type="date" name="to" class="form-control" value="<?php echo sanitize($date_to); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs text-uppercase text-secondary font-weight-bolder">Retailer</label>
                        <select name="retailer" class="form-control">
                            <option value="0">All Retailers</option>
                            <?php while ($rt = $retailers->fetch_assoc()): ?>
                            <option value="<?php echo $rt['id']; ?>" <?php echo $retailer_filter === $rt['id'] ? 'selected' : ''; ?>><?php echo sanitize($rt['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn bg-gradient-primary mb-0">Filter</button>
                        <a href="<?php echo BASE_URL; ?>/agent/sales_transactions.php" class="btn btn-outline-secondary mb-0">Reset</a>
                    </div>
                    <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header pb-0">
                <div class="row">
                    <div class="col-6"><h6>Sales Transactions</h6></div>
                    <div class="col-6 text-end">
                        <?php
                        $toggle_sort = $sort === 'desc' ? 'asc' : 'desc';
                        $sort_params = $_GET;
                        $sort_params['sort'] = $toggle_sort;
                        $sort_url = '?' . http_build_query($sort_params);
                        ?>
                        <a href="<?php echo $sort_url; ?>" class="btn btn-sm btn-outline-dark mb-0">
                            <i class="material-icons text-sm align-middle">sort</i>
                            Date <?php echo $sort === 'desc' ? '(Newest)' : '(Oldest)'; ?>
                        </a>
                    </div>
                </div>
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
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Payment</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Over-Ride</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center text-sm py-4">No transactions found</td></tr>
                            <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                            <tr style="cursor:pointer" onclick="window.location='<?php echo BASE_URL; ?>/agent/order_view.php?id=<?php echo $row['id']; ?>'">
                                <td class="ps-4"><span class="text-xs"><?php echo date('M d, Y', strtotime($row['delivered_at'])); ?></span></td>
                                <td><span class="text-xs font-weight-bold"><?php echo sanitize($row['order_number']); ?></span></td>
                                <td><span class="text-xs"><?php echo sanitize($row['retailer_name']); ?></span></td>
                                <td><span class="text-xs"><?php echo sanitize($row['package_name'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge bg-gradient-<?php echo $row['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($row['payment_method']); ?></span></td>
                                <td><span class="text-xs font-weight-bold"><?php echo format_currency($row['total_amount']); ?></span></td>
                                <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($row['override']); ?> <span class="text-secondary">(<?php echo $row['rate'] > 0 ? round($row['rate'] * 100, 1) . '%' : '0%'; ?>)</span></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Totals Row -->
                            <tr class="bg-light">
                                <td class="ps-4" colspan="5"><span class="text-sm font-weight-bold">TOTAL</span></td>
                                <td><span class="text-sm font-weight-bold"><?php echo format_currency($total_sales); ?></span></td>
                                <td><span class="text-sm font-weight-bold text-success"><?php echo format_currency($total_override); ?></span></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
