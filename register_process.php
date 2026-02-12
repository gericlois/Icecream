<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

// Account details
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

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
$package_info = in_array($_POST['package_info'] ?? '', ['starter_pack', 'premium_pack']) ? $_POST['package_info'] : null;
$payment_type = in_array($_POST['payment_type'] ?? '', ['cash', 'check', 'online_transfer']) ? $_POST['payment_type'] : null;
$payment_details = trim($_POST['payment_details'] ?? '');

// Authorized representative
$auth_rep_name = trim($_POST['auth_rep_name'] ?? '');
$auth_rep_relationship = trim($_POST['auth_rep_relationship'] ?? '');
$auth_rep_gender = in_array($_POST['auth_rep_gender'] ?? '', ['M', 'F']) ? $_POST['auth_rep_gender'] : null;

// Freezer info
$freezer_brand = trim($_POST['freezer_brand'] ?? '');
$freezer_size = trim($_POST['freezer_size'] ?? '');
$freezer_serial = trim($_POST['freezer_serial'] ?? '');
$freezer_status = trim($_POST['freezer_status'] ?? '');

// Validation
if (empty($last_name) || empty($first_name) || empty($username) || empty($password)) {
    $_SESSION['register_error'] = 'Please fill in all required fields.';
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if (strlen($username) < 3) {
    $_SESSION['register_error'] = 'Username must be at least 3 characters.';
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'Password must be at least 6 characters.';
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['register_error'] = 'Username is already taken. Please choose another.';
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}
$stmt->close();

// Create user with inactive status (pending admin approval)
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'subdealer';
$status = 'inactive';

$stmt = $conn->prepare("INSERT INTO users (username, password, full_name, last_name, first_name, middle_name, birthday, gender, sss_gsis, tin, tel_no, role, phone, address, email, application_type, package_info, payment_type, payment_details, auth_rep_name, auth_rep_relationship, auth_rep_gender, freezer_brand, freezer_size, freezer_serial, freezer_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssssssssssssssssssss",
    $username, $hashed_password, $full_name,
    $last_name, $first_name, $middle_name,
    $birthday, $gender, $sss_gsis, $tin, $tel_no,
    $role, $phone, $address, $email,
    $application_type, $package_info, $payment_type, $payment_details,
    $auth_rep_name, $auth_rep_relationship, $auth_rep_gender,
    $freezer_brand, $freezer_size, $freezer_serial, $freezer_status,
    $status
);

if ($stmt->execute()) {
    $_SESSION['register_success'] = 'Application submitted successfully! Please wait for admin approval before logging in.';
} else {
    $_SESSION['register_error'] = 'Registration failed. Please try again.';
}
$stmt->close();

header('Location: ' . BASE_URL . '/register.php');
exit;
