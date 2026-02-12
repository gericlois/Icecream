<?php
$page_title = 'Profile';
$active_page = 'profile';

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($full_name)) {
        flash_message('danger', 'Full name is required.');
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        $stmt->execute();
        $stmt->close();

        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                flash_message('danger', 'Passwords do not match.');
                redirect(BASE_URL . '/profile.php');
            } elseif (strlen($new_password) < 6) {
                flash_message('danger', 'Password must be at least 6 characters.');
                redirect(BASE_URL . '/profile.php');
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $_SESSION['full_name'] = $full_name;
        flash_message('success', 'Profile updated successfully.');
    }
    redirect(BASE_URL . '/profile.php');
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once 'includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Edit Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" value="<?php echo sanitize($user['full_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Username</label>
                                        <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group input-group-outline input-group-static">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>">
                                </div>
                            </div>
                            <hr class="horizontal dark">
                            <h6 class="text-sm">Change Password <small class="text-muted">(leave blank to keep current)</small></h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn bg-gradient-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span style="width:80px;height:80px;border-radius:50%;background:linear-gradient(195deg,#EC407A,#D81B60);display:inline-flex;align-items:center;justify-content:center;">
                                <i class="material-icons text-white" style="font-size:40px;">person</i>
                            </span>
                        </div>
                        <h5 class="mb-1"><?php echo sanitize($user['full_name']); ?></h5>
                        <p class="text-sm text-muted mb-1">@<?php echo sanitize($user['username']); ?></p>
                        <span class="badge bg-gradient-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'subdealer' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        <hr class="horizontal dark my-3">
                        <div class="text-start">
                            <?php if (!empty($user['email'])): ?>
                            <p class="text-sm mb-1"><i class="material-icons text-sm align-middle me-1">email</i> <?php echo sanitize($user['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($user['phone'])): ?>
                            <p class="text-sm mb-1"><i class="material-icons text-sm align-middle me-1">phone</i> <?php echo sanitize($user['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($user['address'])): ?>
                            <p class="text-sm mb-1"><i class="material-icons text-sm align-middle me-1">location_on</i> <?php echo sanitize($user['address']); ?></p>
                            <?php endif; ?>
                            <p class="text-sm mb-0"><i class="material-icons text-sm align-middle me-1">calendar_today</i> Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
