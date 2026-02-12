<?php
$page_title = 'Edit User';
$active_page = 'users';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    flash_message('danger', 'User not found.');
    redirect(BASE_URL . '/admin/users.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
    $status = $_POST['status'] ?? 'active';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, role=?, phone=?, address=?, email=?, agent_id=?, status=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $full_name, $role, $phone, $address, $email, $agent_id, $status, $hashed, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, role=?, phone=?, address=?, email=?, agent_id=?, status=? WHERE id=?");
            $stmt->bind_param("sssssssi", $full_name, $role, $phone, $address, $email, $agent_id, $status, $id);
        }
        $stmt->execute();
        $stmt->close();
        flash_message('success', 'User updated successfully.');
        redirect(BASE_URL . '/admin/users.php');
    }
}

$agents = $conn->query("SELECT id, full_name FROM users WHERE role = 'subdealer' AND status = 'active' ORDER BY full_name");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Edit User: <?php echo sanitize($user['full_name']); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($user['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">New Password (leave blank to keep)</label>
                                        <input type="password" name="new_password" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Role</label>
                                        <select name="role" class="form-control">
                                            <option value="retailer" <?php echo $user['role'] === 'retailer' ? 'selected' : ''; ?>>Retailer</option>
                                            <option value="subdealer" <?php echo $user['role'] === 'subdealer' ? 'selected' : ''; ?>>Subdealer</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>">
                            </div>
                            <div class="input-group input-group-static my-3">
                                <label class="ms-0">Assign to Agent/Subdealer</label>
                                <select name="agent_id" class="form-control">
                                    <option value="">-- None --</option>
                                    <?php while ($a = $agents->fetch_assoc()): ?>
                                    <option value="<?php echo $a['id']; ?>" <?php echo $user['agent_id'] == $a['id'] ? 'selected' : ''; ?>><?php echo sanitize($a['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="text-end mt-4">
                                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn bg-gradient-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
