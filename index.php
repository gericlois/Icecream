<?php
require_once 'config/constants.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    switch ($_SESSION['role']) {
        case 'admin': redirect(BASE_URL . '/admin/index.php'); break;
        case 'subdealer': redirect(BASE_URL . '/agent/index.php'); break;
        case 'retailer': redirect(BASE_URL . '/retailer/index.php'); break;
    }
}

$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - JMC Foodies</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/css/material-dashboard.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css" />
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#E91E63">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg">
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg">
</head>
<body class="bg-gray-200">
    <div class="login-page" style="background: #f0f2f5 !important;">
        <div class="login-card">
            <div class="card z-index-0">
                <div class="login-header">
                    <img src="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg" alt="JMC Foodies" style="width:120px;height:120px;border-radius:16px;object-fit:cover;" class="mb-2">
                    <p class="mb-0 text-sm">Ice Cream Distribution System</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger text-white text-sm" role="alert">
                        <?php echo sanitize($error); ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo BASE_URL; ?>/login_process.php" class="text-start">
                        <div class="input-group input-group-outline my-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required autofocus>
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn bg-gradient-primary w-100 my-4 mb-2">Sign In</button>
                        </div>
                        <p class="text-center text-sm mt-2 mb-0">
                            Contact your agent or admin to register.
                        </p>
                    </form>
                </div>
            </div>
            <p class="text-center text-dark text-sm mt-3 mb-0"><?php echo APP_NAME; ?></p>
            <p class="text-center text-secondary text-xs"><?php echo COMPANY_ADDRESS; ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/js/material-dashboard.min.js"></script>
</body>
</html>
