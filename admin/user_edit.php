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

// Fallback: parse full_name into last/first/middle if separate fields are empty
if (empty($user['last_name']) && empty($user['first_name']) && !empty($user['full_name'])) {
    $fn = $user['full_name'];
    if (strpos($fn, ',') !== false) {
        // Format: "Last, First Middle"
        $parts = explode(',', $fn, 2);
        $user['last_name'] = trim($parts[0]);
        $rest = trim($parts[1] ?? '');
        $words = preg_split('/\s+/', $rest, 2);
        $user['first_name'] = $words[0] ?? '';
        $user['middle_name'] = $words[1] ?? '';
    } else {
        // Format: "First Last" or single name
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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Personal info
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

    // Account
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? 'active';
    $new_password = $_POST['new_password'] ?? '';
    $agent_id = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;

    // Application info
    $application_type = in_array($_POST['application_type'] ?? '', ['cod', '7days_term']) ? $_POST['application_type'] : null;
    $package_info_input = trim($_POST['package_info'] ?? '');
    $package_info = null;
    if ($package_info_input !== '') {
        $stmt_pkg = $conn->prepare("SELECT slug FROM packages WHERE slug = ? AND status = 'active'");
        $stmt_pkg->bind_param("s", $package_info_input);
        $stmt_pkg->execute();
        if ($stmt_pkg->get_result()->num_rows > 0) {
            $package_info = $package_info_input;
        }
        $stmt_pkg->close();
    }
    $nao_name = trim($_POST['nao_name'] ?? '');
    $salesman_name = trim($_POST['salesman_name'] ?? '');

    // Authorized representative
    $auth_rep_name = trim($_POST['auth_rep_name'] ?? '');
    $auth_rep_relationship = trim($_POST['auth_rep_relationship'] ?? '');
    $auth_rep_gender = in_array($_POST['auth_rep_gender'] ?? '', ['M', 'F']) ? $_POST['auth_rep_gender'] : null;

    // Freezer info
    $freezer_brand = trim($_POST['freezer_brand'] ?? '');
    $freezer_size = trim($_POST['freezer_size'] ?? '');
    $freezer_serial = trim($_POST['freezer_serial'] ?? '');
    $freezer_status = trim($_POST['freezer_status'] ?? '');

    if (empty($last_name) || empty($first_name)) {
        $error = 'Last name and first name are required.';
    } else {
        $sql = "UPDATE users SET full_name=?, last_name=?, first_name=?, middle_name=?, birthday=?, gender=?, sss_gsis=?, tin=?, address=?, tel_no=?, phone=?, email=?, role=?, status=?, agent_id=?, application_type=?, package_info=?, nao_name=?, salesman_name=?, auth_rep_name=?, auth_rep_relationship=?, auth_rep_gender=?, freezer_brand=?, freezer_size=?, freezer_serial=?, freezer_status=?";
        $types = "ssssssssssssssssssssssssss";
        $params = [$full_name, $last_name, $first_name, $middle_name, $birthday, $gender, $sss_gsis, $tin, $address, $tel_no, $phone, $email, $role, $status, $agent_id, $application_type, $package_info, $nao_name, $salesman_name, $auth_rep_name, $auth_rep_relationship, $auth_rep_gender, $freezer_brand, $freezer_size, $freezer_serial, $freezer_status];

        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql .= ", password=?";
            $types .= "s";
            $params[] = $hashed;
        }

        $sql .= " WHERE id=?";
        $types .= "i";
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        flash_message('success', 'User updated successfully.');
        redirect(BASE_URL . '/admin/users.php');
    }
}

// Merge POST values back into $user on validation error so form re-populates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)) {
    $post_fields = ['last_name','first_name','middle_name','birthday','gender','sss_gsis','tin','address','tel_no','phone','email','application_type','package_info','nao_name','salesman_name','auth_rep_name','auth_rep_relationship','auth_rep_gender','freezer_brand','freezer_size','freezer_serial','freezer_status'];
    foreach ($post_fields as $f) {
        $user[$f] = $_POST[$f] ?? $user[$f];
    }
    $user['role'] = $_POST['role'] ?? $user['role'];
    $user['status'] = $_POST['status'] ?? $user['status'];
}

$agents = $conn->query("SELECT id, full_name FROM users WHERE role = 'subdealer' AND status = 'active' ORDER BY full_name");
$packages_result = $conn->query("SELECT slug, name FROM packages WHERE status = 'active' ORDER BY sort_order");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Edit User: <?php echo sanitize($user['full_name']); ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Account Details -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-3 mb-2">Account Details</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo sanitize($user['username']); ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
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

                            <!-- Personal Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Personal Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['last_name']) ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo sanitize($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['first_name']) ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo sanitize($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['middle_name']) ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control" value="<?php echo sanitize($user['middle_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Birthday</label>
                                        <input type="date" name="birthday" class="form-control" value="<?php echo sanitize($user['birthday'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($user['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($user['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['sss_gsis']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">SSS/GSIS #</label>
                                        <input type="text" name="sss_gsis" class="form-control" value="<?php echo sanitize($user['sss_gsis'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['tin']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">TIN #</label>
                                        <input type="text" name="tin" class="form-control" value="<?php echo sanitize($user['tin'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline <?php echo !empty($user['address']) ? 'is-filled' : ''; ?> mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($user['address'] ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['tel_no']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Tel. No.</label>
                                        <input type="text" name="tel_no" class="form-control" value="<?php echo sanitize($user['tel_no'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['phone']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Mobile</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['email']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Application Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Application Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Type of Application</label>
                                        <select name="application_type" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="cod" <?php echo ($user['application_type'] ?? '') === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                            <option value="7days_term" <?php echo ($user['application_type'] ?? '') === '7days_term' ? 'selected' : ''; ?>>7 Days Term</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Package Information</label>
                                        <select name="package_info" class="form-control">
                                            <option value="">-- Select --</option>
                                            <?php while ($pkg = $packages_result->fetch_assoc()): ?>
                                            <option value="<?php echo sanitize($pkg['slug']); ?>" <?php echo ($user['package_info'] ?? '') === $pkg['slug'] ? 'selected' : ''; ?>><?php echo sanitize($pkg['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['nao_name']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">NAO's Name</label>
                                        <input type="text" name="nao_name" class="form-control" value="<?php echo sanitize($user['nao_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['salesman_name']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Salesman Name</label>
                                        <input type="text" name="salesman_name" class="form-control" value="<?php echo sanitize($user['salesman_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-static mb-3">
                                <label class="ms-0">Assign to Agent/Subdealer</label>
                                <select name="agent_id" class="form-control">
                                    <option value="">-- None --</option>
                                    <?php while ($a = $agents->fetch_assoc()): ?>
                                    <option value="<?php echo $a['id']; ?>" <?php echo $user['agent_id'] == $a['id'] ? 'selected' : ''; ?>><?php echo sanitize($a['full_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Authorized Representative -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Authorized Representative</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="input-group input-group-outline <?php echo !empty($user['auth_rep_name']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="auth_rep_name" class="form-control" value="<?php echo sanitize($user['auth_rep_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($user['auth_rep_relationship']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Relationship</label>
                                        <input type="text" name="auth_rep_relationship" class="form-control" value="<?php echo sanitize($user['auth_rep_relationship'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Gender</label>
                                        <select name="auth_rep_gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($user['auth_rep_gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($user['auth_rep_gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Freezer Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Freezer Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['freezer_brand']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Brand</label>
                                        <input type="text" name="freezer_brand" class="form-control" value="<?php echo sanitize($user['freezer_brand'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['freezer_size']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Size</label>
                                        <input type="text" name="freezer_size" class="form-control" value="<?php echo sanitize($user['freezer_size'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['freezer_serial']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Serial #</label>
                                        <input type="text" name="freezer_serial" class="form-control" value="<?php echo sanitize($user['freezer_serial'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($user['freezer_status']) ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Freezer Status</label>
                                        <input type="text" name="freezer_status" class="form-control" value="<?php echo sanitize($user['freezer_status'] ?? ''); ?>">
                                    </div>
                                </div>
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
