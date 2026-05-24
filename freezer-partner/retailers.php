<?php
$page_title = 'My Retailers';
$active_page = 'retailers';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['freezer_partner']);

$uid = current_user_id();
$month = (int)date('m');
$year = (int)date('Y');

$retailers = $conn->query("
    SELECT u.*, p.name as package_name,
        COALESCE((SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'delivered' AND MONTH(delivered_at) = $month AND YEAR(delivered_at) = $year), 0) as monthly_delivered,
        COALESCE((SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'delivered'), 0) as all_time_delivered
    FROM users u LEFT JOIN packages p ON u.package_info = p.slug
    WHERE u.freezer_partner_id = $uid AND u.role = 'retailer'
    ORDER BY monthly_delivered DESC
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>My Retailers (linked via Freezer Code)</h6>
                        <p class="text-xs text-secondary mb-0">You earn 3% of every delivered order from retailers using your freezers.</p>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Retailer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Freezer Code</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Town</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"><?php echo date('M Y'); ?> Delivered</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Your 3%</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">All-Time</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($retailers->num_rows === 0): ?>
                                    <tr><td colspan="8" class="text-center text-sm py-4">No retailers linked to your freezers yet. Contact admin to link retailers.</td></tr>
                                    <?php else: ?>
                                    <?php while ($r = $retailers->fetch_assoc()): ?>
                                    <?php $monthly_earn = round($r['monthly_delivered'] * 0.03, 2); ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold"><?php echo sanitize($r['full_name']); ?></span><br>
                                            <span class="text-xs text-secondary"><?php echo sanitize($r['phone'] ?? '-'); ?></span>
                                        </td>
                                        <td><span class="text-xs"><?php echo sanitize($r['freezer_serial'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($r['package_name'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs"><?php echo sanitize($r['town'] ?? '-'); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($r['monthly_delivered']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($monthly_earn); ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($r['all_time_delivered']); ?></span></td>
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
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
