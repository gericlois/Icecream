<?php
$page_title = 'Orders';
$active_page = 'orders';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$conditions = [];
$params = [];
$types = "";

if (in_array($status_filter, ['pending', 'approved', 'for_delivery', 'delivered', 'cancelled'])) {
    $conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search !== '') {
    $conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR a.full_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Count total for pagination
$count_sql = "SELECT COUNT(*) as cnt FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN users a ON o.agent_id = a.id $where";
if ($types) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $total_rows = (int)$conn->query($count_sql)->fetch_assoc()['cnt'];
}

$total_pages = max(1, ceil($total_rows / $per_page));
if ($page > $total_pages) $page = $total_pages;

// Fetch orders
$sql = "
    SELECT o.*, u.full_name as customer_name, a.full_name as agent_name,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users a ON o.agent_id = a.id
    $where
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
";

if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();
} else {
    $orders = $conn->query($sql);
}

// Build query string helper for pagination links
function build_query_string($params_override = []) {
    $base = [];
    if (isset($_GET['status']) && $_GET['status'] !== 'all') $base['status'] = $_GET['status'];
    if (isset($_GET['q']) && trim($_GET['q']) !== '') $base['q'] = trim($_GET['q']);
    $merged = array_merge($base, $params_override);
    return $merged ? '?' . http_build_query($merged) : '?status=all';
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6>Order Management</h6>
                            </div>
                            <div class="col-md-6">
                                <form method="GET" class="d-flex gap-2">
                                    <?php if ($status_filter !== 'all'): ?>
                                    <input type="hidden" name="status" value="<?php echo sanitize($status_filter); ?>">
                                    <?php endif; ?>
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Search orders, customers, agents...</label>
                                        <input type="text" name="q" class="form-control" value="<?php echo sanitize($search); ?>">
                                    </div>
                                    <button type="submit" class="btn bg-gradient-dark mb-0 px-3"><i class="material-icons text-sm">search</i></button>
                                    <?php if ($search !== ''): ?>
                                    <a href="<?php echo build_query_string(['q' => null]); ?>" class="btn btn-outline-secondary mb-0 px-3"><i class="material-icons text-sm">close</i></a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">All</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">Pending</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">Approved</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'for_delivery' ? 'active' : ''; ?>" href="?status=for_delivery<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">For Delivery</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>" href="?status=delivered<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">Delivered</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>" href="?status=cancelled<?php echo $search ? '&q=' . urlencode($search) : ''; ?>">Cancelled</a></li>
                        </ul>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <?php if ($search !== ''): ?>
                        <div class="px-4 pt-3">
                            <span class="text-sm text-secondary">Showing <?php echo $total_rows; ?> result<?php echo $total_rows !== 1 ? 's' : ''; ?> for "<strong><?php echo sanitize($search); ?></strong>"</span>
                        </div>
                        <?php endif; ?>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Order #</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Agent</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Items</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Payment</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Delivery</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($orders->num_rows === 0): ?>
                                    <tr><td colspan="10" class="text-center text-sm py-4">No orders found</td></tr>
                                    <?php else: ?>
                                    <?php while ($o = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($o['order_number']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['customer_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($o['agent_name'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo $o['item_count']; ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $o['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($o['payment_method']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($o['total_amount']); ?></span></td>
                                        <td><?php echo get_status_badge($o['status']); ?></td>
                                        <td><span class="text-xs"><?php echo $o['delivery_start_date'] ? date('M d', strtotime($o['delivery_start_date'])) . '-' . date('d', strtotime($o['delivery_end_date'])) : '-'; ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, Y', strtotime($o['created_at'])); ?></span></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/order_view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm bg-gradient-info mb-0">View</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2">
                            <span class="text-sm text-secondary">
                                Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_rows; ?> orders)
                            </span>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Previous -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_query_string(['page' => $page - 1]); ?>">
                                            <i class="material-icons text-sm">chevron_left</i>
                                        </a>
                                    </li>

                                    <?php
                                    // Show page numbers
                                    $start_pg = max(1, $page - 2);
                                    $end_pg = min($total_pages, $page + 2);

                                    if ($start_pg > 1): ?>
                                    <li class="page-item"><a class="page-link" href="<?php echo build_query_string(['page' => 1]); ?>">1</a></li>
                                    <?php if ($start_pg > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_pg; $i <= $end_pg; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_query_string(['page' => $i]); ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>

                                    <?php if ($end_pg < $total_pages): ?>
                                    <?php if ($end_pg < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="<?php echo build_query_string(['page' => $total_pages]); ?>"><?php echo $total_pages; ?></a></li>
                                    <?php endif; ?>

                                    <!-- Next -->
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_query_string(['page' => $page + 1]); ?>">
                                            <i class="material-icons text-sm">chevron_right</i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
