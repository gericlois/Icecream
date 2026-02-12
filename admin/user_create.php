<?php
$page_title = 'Add User';
$active_page = 'users';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'retailer';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;

    if (empty($full_name) || empty($username) || empty($password)) {
        $error = 'Full name, username, and password are required.';
    } else {
        // Check duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $registered_by = current_user_id();
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, phone, address, email, agent_id, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", $username, $hashed, $full_name, $role, $phone, $address, $email, $agent_id, $registered_by);
            $stmt->execute();
            $stmt->close();
            flash_message('success', 'User created successfully.');
            redirect(BASE_URL . '/admin/users.php');
        }
        $stmt->close();
    }
}

// Get subdealers for agent dropdown
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
                        <h6>Register New User</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Full Name *</label>
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
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Role</label>
                                        <select name="role" class="form-control" id="roleSelect" onchange="toggleAgentField()">
                                            <option value="retailer">Retailer</option>
                                            <option value="subdealer">Subdealer/Agent</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($_POST['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($_POST['address'] ?? ''); ?>">
                            </div>
                            <div id="agentField" class="input-group input-group-static my-3">
                                <label class="ms-0">Assign to Agent/Subdealer</label>
                                <select name="agent_id" class="form-control">
                                    <option value="">-- None --</option>
                                    <?php while ($a = $agents->fetch_assoc()): ?>
                                    <option value="<?php echo $a['id']; ?>"><?php echo sanitize($a['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="text-end mt-4">
                                <a href="<?php echo BASE_URL; ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn bg-gradient-primary">Create User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleAgentField() {
    document.getElementById('agentField').style.display =
        document.getElementById('roleSelect').value === 'retailer' ? 'flex' : 'none';
}
toggleAgentField();
</script>

<?php require_once '../includes/footer.php'; ?>
