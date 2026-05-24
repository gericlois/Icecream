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

// Get all retailers grouped by package
$retailers = $conn->query("
    SELECT u.*, p.name as package_name, p.slug as package_slug, p.subsidy_rate, p.sort_order as pkg_sort
    FROM users u
    LEFT JOIN packages p ON u.package_info = p.slug
    WHERE u.agent_id = $uid AND u.role = 'retailer'
    ORDER BY p.sort_order ASC, p.name ASC, u.full_name ASC
");

// Group by package
$by_package = [];
$total_count = 0;
while ($r = $retailers->fetch_assoc()) {
    $pkg = $r['package_name'] ?? 'No Package';
    if (!isset($by_package[$pkg])) {
        $by_package[$pkg] = [
            'slug' => $r['package_slug'] ?? '',
            'rate' => (float)($r['subsidy_rate'] ?? 0),
            'retailers' => [],
        ];
    }
    $by_package[$pkg]['retailers'][] = $r;
    $total_count++;
}

// Package filter
$filter = $_GET['package'] ?? 'all';

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row mb-4">
            <div class="col-6"><h5>My Retailers <span class="text-secondary text-sm">(<?php echo $total_count; ?> total)</span></h5></div>
            <div class="col-6 text-end">
                <a href="<?php echo BASE_URL; ?>/agent/retailer_create.php" class="btn btn-sm bg-gradient-primary">
                    <i class="material-icons text-sm">add</i> Register Retailer
                </a>
            </div>
        </div>

        <!-- Package Summary Cards -->
        <div class="row mb-4">
            <?php foreach ($by_package as $pkg_name => $pkg_data): ?>
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-header p-3 pt-2">
                        <div class="icon icon-lg icon-shape bg-gradient-<?php
                            $rate = $pkg_data['rate'];
                            echo $rate >= 0.05 ? 'success' : ($rate >= 0.03 ? 'info' : ($rate > 0 ? 'warning' : 'secondary'));
                        ?> shadow text-center border-radius-xl mt-n4 position-absolute">
                            <i class="material-icons opacity-10">inventory_2</i>
                        </div>
                        <div class="text-end pt-1">
                            <p class="text-sm mb-0"><?php echo sanitize($pkg_name); ?></p>
                            <h4 class="mb-0"><?php echo count($pkg_data['retailers']); ?></h4>
                        </div>
                    </div>
                    <hr class="dark horizontal my-0">
                    <div class="card-footer p-3">
                        <p class="mb-0 text-sm">
                            Over-ride rate: <span class="font-weight-bold"><?php echo $rate > 0 ? round($rate * 100, 1) . '%' : 'N/A'; ?></span>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- Retailer Lists by Package -->
        <?php foreach ($by_package as $pkg_name => $pkg_data):
            $rate = $pkg_data['rate'];
            $color = $rate >= 0.05 ? 'success' : ($rate >= 0.03 ? 'info' : ($rate > 0 ? 'warning' : 'secondary'));
        ?>
        <div class="card mb-4">
            <div class="card-header pb-0">
                <div class="row align-items-center">
                    <div class="col-6">
                        <h6>
                            <span class="badge bg-gradient-<?php echo $color; ?> me-2"><?php echo sanitize($pkg_name); ?></span>
                            <span class="text-secondary text-sm font-weight-normal"><?php echo count($pkg_data['retailers']); ?> retailer<?php echo count($pkg_data['retailers']) !== 1 ? 's' : ''; ?></span>
                        </h6>
                    </div>
                    <div class="col-6 text-end">
                        <span class="text-xs text-secondary">Over-ride rate: <strong><?php echo $rate > 0 ? round($rate * 100, 1) . '%' : 'N/A'; ?></strong></span>
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
                            <?php foreach ($pkg_data['retailers'] as $r): ?>
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
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($by_package)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <p class="text-sm text-secondary mb-3">No retailers registered yet</p>
                <a href="<?php echo BASE_URL; ?>/agent/retailer_create.php" class="btn bg-gradient-primary">
                    <i class="material-icons text-sm">add</i> Register Retailer
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
