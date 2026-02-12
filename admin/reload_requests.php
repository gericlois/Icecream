<?php
$page_title = 'Reload Requests';
$active_page = 'reload_requests';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['admin_notes'] ?? '');

    $req = $conn->query("SELECT * FROM reload_requests WHERE id = $req_id AND status = 'pending'")->fetch_assoc();
    if ($req) {
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE reload_requests SET status='approved', processed_by=?, processed_at=NOW(), admin_notes=? WHERE id=?");
            $admin_id = current_user_id();
            $stmt->bind_param("isi", $admin_id, $notes, $req_id);
            $stmt->execute();
            $stmt->close();

            credit_efunds($conn, $req['user_id'], $req['amount'], 'reload', 'reload_request', $req_id,
                'Reload via ' . $req['method'] . ' (Ref: ' . $req['reference_number'] . ')', current_user_id());

            flash_message('success', 'Reload request approved. ' . format_currency($req['amount']) . ' credited.');
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE reload_requests SET status='rejected', processed_by=?, processed_at=NOW(), admin_notes=? WHERE id=?");
            $admin_id = current_user_id();
            $stmt->bind_param("isi", $admin_id, $notes, $req_id);
            $stmt->execute();
            $stmt->close();
            flash_message('warning', 'Reload request rejected.');
        }
    }
    redirect(BASE_URL . '/admin/reload_requests.php');
}

$status_filter = $_GET['status'] ?? 'pending';
$where = "WHERE rr.status = '" . $conn->real_escape_string($status_filter) . "'";
if ($status_filter === 'all') $where = "";

$requests = $conn->query("
    SELECT rr.*, u.full_name as user_name, u.username
    FROM reload_requests rr
    JOIN users u ON rr.user_id = u.id
    $where
    ORDER BY rr.created_at DESC
");

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
                        <h6>Reload Requests</h6>
                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending">Pending</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>" href="?status=approved">Approved</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">Rejected</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all">All</a></li>
                        </ul>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">User</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Method</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Reference</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Proof</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($requests->num_rows === 0): ?>
                                    <tr><td colspan="8" class="text-center text-sm py-4">No requests found</td></tr>
                                    <?php else: ?>
                                    <?php while ($r = $requests->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($r['user_name']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($r['amount']); ?></span></td>
                                        <td><span class="badge bg-gradient-info"><?php echo strtoupper(str_replace('_', ' ', $r['method'])); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($r['reference_number'] ?? '-'); ?></span></td>
                                        <td>
                                            <?php if ($r['proof_image']): ?>
                                            <a href="<?php echo BASE_URL . '/uploads/proof/' . sanitize($r['proof_image']); ?>" target="_blank" class="text-xs text-primary">View Proof</a>
                                            <?php else: ?>
                                            <span class="text-xs">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-gradient-<?php echo $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d, h:i A', strtotime($r['created_at'])); ?></span></td>
                                        <td>
                                            <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="admin_notes" value="">
                                                <button type="submit" class="btn btn-sm bg-gradient-success mb-0" onclick="return confirm('Approve this reload of <?php echo format_currency($r['amount']); ?>?')">Approve</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="admin_notes" value="">
                                                <button type="submit" class="btn btn-sm bg-gradient-danger mb-0" onclick="return confirm('Reject this reload request?')">Reject</button>
                                            </form>
                                            <?php else: ?>
                                            <span class="text-xs text-muted">Processed</span>
                                            <?php endif; ?>
                                        </td>
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
