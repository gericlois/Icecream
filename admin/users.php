<?php
$page_title = 'Users';
$active_page = 'users';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user'])) {
    $uid = (int)$_POST['user_id'];
    $action = $_POST['action_user'];
    if ($action === 'approve') {
        $conn->query("UPDATE users SET status = 'active' WHERE id = $uid");
        flash_message('success', 'User account approved successfully.');
    } elseif ($action === 'reject') {
        $conn->query("DELETE FROM users WHERE id = $uid AND status = 'inactive'");
        flash_message('success', 'Registration rejected and removed.');
    }
    redirect(BASE_URL . '/admin/users.php' . (isset($_GET['role']) ? '?role=' . $_GET['role'] : ''));
}

$role_filter = $_GET['role'] ?? 'all';
$where = "";
if (in_array($role_filter, ['admin', 'subdealer', 'retailer'])) {
    $where = "WHERE u.role = '" . $conn->real_escape_string($role_filter) . "'";
}

$users = $conn->query("
    SELECT u.*, a.full_name as agent_name
    FROM users u
    LEFT JOIN users a ON u.agent_id = a.id
    $where
    ORDER BY u.created_at DESC
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
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6>User Management</h6>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/admin/user_create.php" class="btn btn-sm bg-gradient-primary">
                                    <i class="material-icons text-sm">add</i> Add User
                                </a>
                            </div>
                        </div>
                        <!-- Filter tabs -->
                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item"><a class="nav-link <?php echo $role_filter === 'all' ? 'active' : ''; ?>" href="?role=all">All</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $role_filter === 'admin' ? 'active' : ''; ?>" href="?role=admin">Admins</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $role_filter === 'subdealer' ? 'active' : ''; ?>" href="?role=subdealer">Subdealers</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo $role_filter === 'retailer' ? 'active' : ''; ?>" href="?role=retailer">Retailers</a></li>
                        </ul>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">User</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Role</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Phone</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Agent</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">E-Funds</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($u = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?php echo sanitize($u['full_name']); ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?php echo sanitize($u['username']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-gradient-<?php echo $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'subdealer' ? 'warning' : 'info'); ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($u['phone'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($u['agent_name'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($u['efunds_balance']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $u['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                        <td>
                                            <?php if ($u['status'] === 'inactive'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" name="action_user" value="approve" class="btn btn-sm bg-gradient-success mb-0">Approve</button>
                                                <button type="submit" name="action_user" value="reject" class="btn btn-sm bg-gradient-danger mb-0" onclick="return confirm('Reject this registration?')">Reject</button>
                                            </form>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>/admin/user_edit.php?id=<?php echo $u['id']; ?>" class="btn btn-sm bg-gradient-dark mb-0">Edit</a>
                                        </td>
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
