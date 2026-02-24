<?php
$page_title = 'Profile';
$active_page = 'profile';

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT u.*, p.name as package_name FROM users u LEFT JOIN packages p ON u.package_info = p.slug WHERE u.id = $user_id")->fetch_assoc();

// Fallback: parse full_name into last/first/middle if separate fields are empty
if (empty($user['last_name']) && empty($user['first_name']) && !empty($user['full_name'])) {
    $fn = $user['full_name'];
    if (strpos($fn, ',') !== false) {
        $parts = explode(',', $fn, 2);
        $user['last_name'] = trim($parts[0]);
        $rest = trim($parts[1] ?? '');
        $words = preg_split('/\s+/', $rest, 2);
        $user['first_name'] = $words[0] ?? '';
        $user['middle_name'] = $words[1] ?? '';
    } else {
        $words = preg_split('/\s+/', $fn);
        if (count($words) >= 2) {
            $user['last_name'] = array_pop($words);
            $user['first_name'] = array_shift($words);
            $user['middle_name'] = implode(' ', $words);
        } else {
            $user['first_name'] = $fn;
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $full_name = trim("$last_name, $first_name $middle_name");
    $birthday = $_POST['birthday'] ?: null;
    $gender = in_array($_POST['gender'] ?? '', ['M', 'F']) ? $_POST['gender'] : null;
    $sss_gsis = trim($_POST['sss_gsis'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $tel_no = trim($_POST['tel_no'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $auth_rep_name = trim($_POST['auth_rep_name'] ?? '');
    $auth_rep_relationship = trim($_POST['auth_rep_relationship'] ?? '');
    $auth_rep_gender = in_array($_POST['auth_rep_gender'] ?? '', ['M', 'F']) ? $_POST['auth_rep_gender'] : null;
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($last_name) || empty($first_name)) {
        flash_message('danger', 'Last name and first name are required.');
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, last_name=?, first_name=?, middle_name=?, birthday=?, gender=?, sss_gsis=?, tin=?, address=?, tel_no=?, phone=?, email=?, auth_rep_name=?, auth_rep_relationship=?, auth_rep_gender=? WHERE id=?");
        $stmt->bind_param("sssssssssssssssi", $full_name, $last_name, $first_name, $middle_name, $birthday, $gender, $sss_gsis, $tin, $address, $tel_no, $phone, $email, $auth_rep_name, $auth_rep_relationship, $auth_rep_gender, $user_id);
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
                            <!-- Personal Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-2 mb-2">Personal Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Last Name *</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo sanitize($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>First Name *</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo sanitize($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control" value="<?php echo sanitize($user['middle_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Birthday</label>
                                        <input type="date" name="birthday" class="form-control" value="<?php echo sanitize($user['birthday'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($user['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($user['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>SSS/GSIS #</label>
                                        <input type="text" name="sss_gsis" class="form-control" value="<?php echo sanitize($user['sss_gsis'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>TIN #</label>
                                        <input type="text" name="tin" class="form-control" value="<?php echo sanitize($user['tin'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="input-group input-group-outline input-group-static">
                                    <label>Address</label>
                                    <input type="text" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Tel. No.</label>
                                        <input type="text" name="tel_no" class="form-control" value="<?php echo sanitize($user['tel_no'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Mobile</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Authorized Representative -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Authorized Representative</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Full Name</label>
                                        <input type="text" name="auth_rep_name" class="form-control" value="<?php echo sanitize($user['auth_rep_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Relationship</label>
                                        <input type="text" name="auth_rep_relationship" class="form-control" value="<?php echo sanitize($user['auth_rep_relationship'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="input-group input-group-outline input-group-static">
                                        <label>Gender</label>
                                        <select name="auth_rep_gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($user['auth_rep_gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($user['auth_rep_gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Change Password <small class="text-muted fw-normal text-capitalize">(leave blank to keep current)</small></h6>
                            <hr class="horizontal dark mt-0 mb-3">
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
                <!-- Profile Card -->
                <div class="card mb-4">
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

                <!-- Application Info (read-only) -->
                <?php if ($user['role'] === 'retailer'): ?>
                <div class="card mb-4">
                    <div class="card-header pb-0"><h6 class="text-sm">Application Info</h6></div>
                    <div class="card-body pt-2">
                        <p class="text-sm mb-2">
                            <span class="text-secondary">Package:</span>
                            <strong><?php echo sanitize($user['package_name'] ?? 'Not assigned'); ?></strong>
                        </p>
                        <p class="text-sm mb-2">
                            <span class="text-secondary">Application Type:</span>
                            <strong><?php
                                $types = ['cod' => 'Cash on Delivery', '7days_term' => '7 Days Term'];
                                echo $types[$user['application_type'] ?? ''] ?? 'Not set';
                            ?></strong>
                        </p>
                        <?php if (!empty($user['nao_name'])): ?>
                        <p class="text-sm mb-2"><span class="text-secondary">NAO:</span> <?php echo sanitize($user['nao_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($user['salesman_name'])): ?>
                        <p class="text-sm mb-0"><span class="text-secondary">Salesman:</span> <?php echo sanitize($user['salesman_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Freezer Info (read-only) -->
                <?php if (!empty($user['freezer_brand']) || !empty($user['freezer_serial'])): ?>
                <div class="card">
                    <div class="card-header pb-0"><h6 class="text-sm">Freezer Info</h6></div>
                    <div class="card-body pt-2">
                        <?php if (!empty($user['freezer_brand'])): ?>
                        <p class="text-sm mb-2"><span class="text-secondary">Brand:</span> <?php echo sanitize($user['freezer_brand']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($user['freezer_size'])): ?>
                        <p class="text-sm mb-2"><span class="text-secondary">Size:</span> <?php echo sanitize($user['freezer_size']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($user['freezer_serial'])): ?>
                        <p class="text-sm mb-2"><span class="text-secondary">Serial #:</span> <?php echo sanitize($user['freezer_serial']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($user['freezer_status'])): ?>
                        <p class="text-sm mb-0"><span class="text-secondary">Status:</span> <?php echo sanitize($user['freezer_status']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
