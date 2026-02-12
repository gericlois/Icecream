<?php
$page_title = 'Register Retailer';
$active_page = 'retailers';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name) || empty($username) || empty($password)) {
        $error = 'Full name, username, and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $agent_id = current_user_id();
            $role = 'retailer';
            $stmt2 = $conn->prepare("INSERT INTO users (username, password, full_name, role, phone, address, agent_id, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("ssssssii", $username, $hashed, $full_name, $role, $phone, $address, $agent_id, $agent_id);
            $stmt2->execute();
            $stmt2->close();
            flash_message('success', 'Retailer registered successfully.');
            redirect(BASE_URL . '/agent/retailers.php');
        }
        $stmt->close();
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header pb-0"><h6>Register New Retailer</h6></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Full Name / Store Name *</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($_POST['full_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo sanitize($_POST['username'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($_POST['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($_POST['address'] ?? ''); ?>">
                            </div>
                            <div class="text-end mt-4">
                                <a href="<?php echo BASE_URL; ?>/agent/retailers.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn bg-gradient-primary">Register Retailer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
