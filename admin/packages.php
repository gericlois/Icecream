<?php
$page_title = 'Packages';
$active_page = 'packages';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$packages = $conn->query("SELECT * FROM packages ORDER BY sort_order, name");

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
                        <div class="row align-items-center">
                            <div class="col-6"><h6>Package Management</h6></div>
                            <div class="col-6 text-end">
                                <a href="<?php echo BASE_URL; ?>/admin/package_edit.php" class="btn btn-sm bg-gradient-primary">
                                    <i class="material-icons text-sm">add</i> Add Package
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Package</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Subsidy Rate</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Min. Quota</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">FDA</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($p = $packages->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-primary me-2">inventory_2</i>
                                                <h6 class="mb-0 text-sm"><?php echo sanitize($p['name']); ?></h6>
                                            </div>
                                        </td>
                                        <td><span class="text-xs font-weight-bold"><?php echo ($p['subsidy_rate'] * 100); ?>%</span></td>
                                        <td><span class="text-xs"><?php echo format_currency($p['subsidy_min_orders']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold"><?php echo format_currency($p['freezer_display_allowance']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $p['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/package_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm bg-gradient-dark mb-0">Edit</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
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
