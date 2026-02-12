<?php
$page_title = 'My Retailers';
$active_page = 'retailers';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['subdealer']);

$uid = current_user_id();
$retailers = $conn->query("
    SELECT * FROM users WHERE agent_id = $uid AND role = 'retailer' ORDER BY full_name
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="card">
            <div class="card-header pb-0">
                <div class="row">
                    <div class="col-6"><h6>My Retailers</h6></div>
                    <div class="col-6 text-end">
                        <a href="<?php echo BASE_URL; ?>/agent/retailer_create.php" class="btn btn-sm bg-gradient-primary">
                            <i class="material-icons text-sm">add</i> Register Retailer
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Name</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Phone</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Address</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">E-Funds</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($retailers->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center text-sm py-4">No retailers yet</td></tr>
                            <?php else: ?>
                            <?php while ($r = $retailers->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <h6 class="mb-0 text-sm"><?php echo sanitize($r['full_name']); ?></h6>
                                    <p class="text-xs text-secondary mb-0"><?php echo sanitize($r['username']); ?></p>
                                </td>
                                <td><span class="text-xs"><?php echo sanitize($r['phone'] ?? '-'); ?></span></td>
                                <td><span class="text-xs"><?php echo sanitize($r['address'] ?? '-'); ?></span></td>
                                <td><span class="text-xs font-weight-bold"><?php echo format_currency($r['efunds_balance']); ?></span></td>
                                <td><span class="badge bg-gradient-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
