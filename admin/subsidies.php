<?php
$page_title = 'Electric Subsidies';
$active_page = 'subsidies';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$subsidies = $conn->query("
    SELECT es.*, u.full_name
    FROM electric_subsidy es
    JOIN users u ON es.user_id = u.id
    ORDER BY es.year DESC, es.month DESC
");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0"><h6>Electric Subsidy Records</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">User</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Period</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Total Orders</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Subsidy Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Converted</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($subsidies->num_rows === 0): ?>
                                    <tr><td colspan="6" class="text-center text-sm py-4">No subsidy records yet</td></tr>
                                    <?php else: ?>
                                    <?php while ($s = $subsidies->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-xs font-weight-bold"><?php echo sanitize($s['full_name']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('F', mktime(0, 0, 0, $s['month'], 1)) . ' ' . $s['year']; ?></span></td>
                                        <td><span class="text-xs"><?php echo format_currency($s['total_orders_amount']); ?></span></td>
                                        <td><span class="text-xs font-weight-bold text-success"><?php echo format_currency($s['subsidy_amount']); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $s['converted'] ? 'success' : 'warning'; ?>"><?php echo $s['converted'] ? 'Yes' : 'No'; ?></span></td>
                                        <td><span class="text-xs"><?php echo $s['converted_at'] ? date('M d, Y', strtotime($s['converted_at'])) : '-'; ?></span></td>
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
