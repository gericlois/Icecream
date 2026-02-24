<?php
$page_title = 'Settings';
$active_page = 'settings';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['efunds_discount_percent', 'subsidy_factor', 'agent_subsidy_min_orders',
             'company_name', 'company_address', 'company_tin', 'company_hotline'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $val, $key);
            $stmt->execute();
            $stmt->close();
        }
    }
    flash_message('success', 'Settings updated successfully.');
    redirect(BASE_URL . '/admin/settings.php');
}

// Load current settings
$settings = [];
$r = $conn->query("SELECT * FROM settings");
while ($s = $r->fetch_assoc()) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <form method="POST">
                    <div class="card mb-4">
                        <div class="card-header pb-0"><h6>E-Funds & Subsidy Settings</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">E-Funds Discount (%)</label>
                                        <input type="number" name="efunds_discount_percent" class="form-control" value="<?php echo sanitize($settings['efunds_discount_percent'] ?? '0'); ?>" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Subsidy Factor</label>
                                        <input type="number" name="subsidy_factor" class="form-control" value="<?php echo sanitize($settings['subsidy_factor'] ?? '0.88'); ?>" step="0.01" min="0" max="1">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Agent Over-Ride Min. Orders (₱)</label>
                                        <input type="number" name="agent_subsidy_min_orders" class="form-control" value="<?php echo sanitize($settings['agent_subsidy_min_orders'] ?? '8000'); ?>" step="1" min="0">
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-muted">Retailer subsidy rate and minimum quota are configured per package in <a href="<?php echo BASE_URL; ?>/admin/packages.php">Package Management</a>. Agent over-ride uses the same package rates but requires the combined minimum above.</p>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header pb-0"><h6>Company Information</h6></div>
                        <div class="card-body">
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?php echo sanitize($settings['company_name'] ?? ''); ?>">
                            </div>
                            <div class="input-group input-group-outline is-filled my-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="company_address" class="form-control" value="<?php echo sanitize($settings['company_address'] ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">TIN</label>
                                        <input type="text" name="company_tin" class="form-control" value="<?php echo sanitize($settings['company_tin'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Hotline</label>
                                        <input type="text" name="company_hotline" class="form-control" value="<?php echo sanitize($settings['company_hotline'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn bg-gradient-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
