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
    $sss_gsis = '';
    $tin = '';
    $address = trim($_POST['address'] ?? '');
    $tel_no = '';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Application info — COD only
    $application_type = 'cod';
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
    $nao_name = '';
    $salesman_name = '';

    // Authorized representative
    $auth_rep_name = trim($_POST['auth_rep_name'] ?? '');
    $auth_rep_relationship = trim($_POST['auth_rep_relationship'] ?? '');
    $auth_rep_gender = in_array($_POST['auth_rep_gender'] ?? '', ['M', 'F']) ? $_POST['auth_rep_gender'] : null;

    // Freezer info (to be filled by admin)
    $freezer_brand = '';
    $freezer_size = '';
    $freezer_serial = '';
    $freezer_status = '';

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
            $status = 'inactive'; // Pending admin approval
            $stmt2 = $conn->prepare("INSERT INTO users (username, password, full_name, last_name, first_name, middle_name, birthday, gender, sss_gsis, tin, tel_no, role, phone, address, email, application_type, package_info, auth_rep_name, auth_rep_relationship, auth_rep_gender, freezer_brand, freezer_size, freezer_serial, freezer_status, nao_name, salesman_name, agent_id, registered_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("ssssssssssssssssssssssssssiis", $username, $hashed, $full_name, $last_name, $first_name, $middle_name, $birthday, $gender, $sss_gsis, $tin, $tel_no, $role, $phone, $address, $email, $application_type, $package_info, $auth_rep_name, $auth_rep_relationship, $auth_rep_gender, $freezer_brand, $freezer_size, $freezer_serial, $freezer_status, $nao_name, $salesman_name, $agent_id, $agent_id, $status);
            $stmt2->execute();
            $stmt2->close();
            flash_message('success', 'Retailer registered successfully! Pending admin approval before they can login.');
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
                            <div class="input-group input-group-outline <?php echo !empty($_POST['address'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" name="address" class="form-control" value="<?php echo sanitize($_POST['address'] ?? ''); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo !empty($_POST['phone'] ?? '') ? 'is-filled' : ''; ?> mb-3">
                                        <label class="form-label">Mobile *</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($_POST['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
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
                                        <input type="text" class="form-control" value="Cash-on-Delivery" readonly>
                                    </div>
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

                            <!-- Authorized Representative -->
                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Authorized Representative</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <p class="text-xs text-muted mt-n2 mb-3">Person authorized of receiving stocks, notices and make transactions in your absence.</p>
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
