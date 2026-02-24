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
    // Account
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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

    if (empty($last_name) || empty($first_name) || empty($username) || empty($password)) {
        $error = 'Last name, first name, username, and password are required.';
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
            $stmt2 = $conn->prepare("INSERT INTO users (username, password, full_name, last_name, first_name, middle_name, birthday, gender, sss_gsis, tin, tel_no, role, phone, address, email, application_type, package_info, auth_rep_name, auth_rep_relationship, auth_rep_gender, freezer_brand, freezer_size, freezer_serial, freezer_status, nao_name, salesman_name, agent_id, registered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("ssssssssssssssssssssssssssii", $username, $hashed, $full_name, $last_name, $first_name, $middle_name, $birthday, $gender, $sss_gsis, $tin, $tel_no, $role, $phone, $address, $email, $application_type, $package_info, $auth_rep_name, $auth_rep_relationship, $auth_rep_gender, $freezer_brand, $freezer_size, $freezer_serial, $freezer_status, $nao_name, $salesman_name, $agent_id, $agent_id);
            $stmt2->execute();
            $stmt2->close();
            flash_message('success', 'Retailer registered successfully.');
            redirect(BASE_URL . '/agent/retailers.php');
        }
        $stmt->close();
    }
}

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
                    <div class="card-header pb-0"><h6>Register New Retailer</h6></div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Account Details -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-3 mb-2">Account Details</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['username'] ?? '') ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo sanitize($_POST['username'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline my-3">
                                        <label class="form-label">Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Personal Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['last_name'] ?? '') ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="last_name" class="form-control" value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['first_name'] ?? '') ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="first_name" class="form-control" value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['middle_name'] ?? '') ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" class="form-control" value="<?php echo sanitize($_POST['middle_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Birthday</label>
                                        <input type="date" name="birthday" class="form-control" value="<?php echo sanitize($_POST['birthday'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($_POST['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($_POST['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['sss_gsis'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">SSS/GSIS #</label>
                                        <input type="text" name="sss_gsis" class="form-control" value="<?php echo sanitize($_POST['sss_gsis'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['tin'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">TIN #</label>
                                        <input type="text" name="tin" class="form-control" value="<?php echo sanitize($_POST['tin'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline <?php echo !empty($_POST['address'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($_POST['address'] ?? ''); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['tel_no'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Tel. No.</label>
                                        <input type="text" name="tel_no" class="form-control" value="<?php echo sanitize($_POST['tel_no'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['phone'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Mobile *</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($_POST['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['email'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo sanitize($_POST['email'] ?? ''); ?>">
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
                                            <option value="cod" <?php echo ($_POST['application_type'] ?? '') === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                            <option value="7days_term" <?php echo ($_POST['application_type'] ?? '') === '7days_term' ? 'selected' : ''; ?>>7 Days Term</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-muted mt-n2">*For 7 Days Term: P1,000.00 one-time collector's fee applies.</p>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Package Information</label>
                                        <select name="package_info" class="form-control">
                                            <option value="">-- Select --</option>
                                            <?php while ($pkg = $packages_result->fetch_assoc()): ?>
                                            <option value="<?php echo sanitize($pkg['slug']); ?>" <?php echo ($_POST['package_info'] ?? '') === $pkg['slug'] ? 'selected' : ''; ?>><?php echo sanitize($pkg['name']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['nao_name'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">NAO's Name</label>
                                        <input type="text" name="nao_name" class="form-control" value="<?php echo sanitize($_POST['nao_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['salesman_name'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Salesman Name</label>
                                        <input type="text" name="salesman_name" class="form-control" value="<?php echo sanitize($_POST['salesman_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Authorized Representative -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Authorized Representative</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['auth_rep_name'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="auth_rep_name" class="form-control" value="<?php echo sanitize($_POST['auth_rep_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['auth_rep_relationship'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Relationship</label>
                                        <input type="text" name="auth_rep_relationship" class="form-control" value="<?php echo sanitize($_POST['auth_rep_relationship'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-static mb-3">
                                        <label class="ms-0">Gender</label>
                                        <select name="auth_rep_gender" class="form-control">
                                            <option value="">-- Select --</option>
                                            <option value="M" <?php echo ($_POST['auth_rep_gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo ($_POST['auth_rep_gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Freezer Information -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Freezer Information</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['freezer_brand'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Brand</label>
                                        <input type="text" name="freezer_brand" class="form-control" value="<?php echo sanitize($_POST['freezer_brand'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['freezer_size'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Size</label>
                                        <input type="text" name="freezer_size" class="form-control" value="<?php echo sanitize($_POST['freezer_size'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['freezer_serial'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Serial #</label>
                                        <input type="text" name="freezer_serial" class="form-control" value="<?php echo sanitize($_POST['freezer_serial'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['freezer_status'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Freezer Status</label>
                                        <input type="text" name="freezer_status" class="form-control" value="<?php echo sanitize($_POST['freezer_status'] ?? ''); ?>">
                                    </div>
                                </div>
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
