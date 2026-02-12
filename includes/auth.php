<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function require_role($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        http_response_code(403);
        echo '<h1>403 - Access Denied</h1><p>You do not have permission to access this page.</p>';
        echo '<a href="' . BASE_URL . '/index.php">Go to Login</a>';
        exit;
    }
}

function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? 0;
}

function current_role() {
    return $_SESSION['role'] ?? '';
}
