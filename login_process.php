<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter username and password.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, full_name, role, agent_id, status FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['login_error'] = 'Invalid username or password.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['status'] !== 'active') {
    $_SESSION['login_error'] = 'Your account is pending approval. Please wait for admin confirmation.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Invalid username or password.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['agent_id'] = $user['agent_id'];
$_SESSION['logged_in'] = true;

// Redirect based on role
switch ($user['role']) {
    case 'admin':
        header('Location: ' . BASE_URL . '/admin/index.php');
        break;
    case 'subdealer':
        header('Location: ' . BASE_URL . '/agent/index.php');
        break;
    case 'retailer':
        header('Location: ' . BASE_URL . '/retailer/index.php');
        break;
    default:
        header('Location: ' . BASE_URL . '/index.php');
}
exit;
