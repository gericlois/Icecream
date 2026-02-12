<?php
$page_title = 'Profile';
$active_page = 'profile';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();
$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $phone, $address, $email, $uid);
        $stmt->execute();
        $stmt->close();

        $_SESSION['full_name'] = $full_name;
        flash_message('success', 'Profile updated successfully.');
        redirect(BASE_URL . '/retailer/profile.php');
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            flash_message('danger', 'Current password is incorrect.');
        } elseif (strlen($new_pass) < 6) {
            flash_message('danger', 'New password must be at least 6 characters.');
        } elseif ($new_pass !== $confirm) {
            flash_message('danger', 'Passwords do not match.');
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $uid);
            $stmt->execute();
            $stmt->close();
            flash_message('success', 'Password changed successfully.');
        }
        redirect(BASE_URL . '/retailer/profile.php');
    }
}

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
                    <div class="card-header pb-0"><h6>Profile Information</h6></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($user['full_name']); ?>" required>
                            </div>
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>">
                            </div>
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? ''); ?>">
                            </div>
                            <button type="submit" name="update_profile" class="btn bg-gradient-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Change Password</h6></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn bg-gradient-dark">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
