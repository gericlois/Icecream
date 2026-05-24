<?php
$page_title = 'Inventory';
$active_page = 'inventory';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

// Handle stock save (absolute set + low-stock threshold)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_stock'])) {
    $flavor_id = (int)($_POST['flavor_id'] ?? 0);
    $stock_value = max(0, (int)($_POST['stock_value'] ?? 0));
    $threshold = max(0, (int)($_POST['threshold'] ?? 0));
    $notes = trim($_POST['notes'] ?? '');

    $fl = $conn->query("SELECT id FROM product_flavors WHERE id = $flavor_id")->fetch_assoc();
    if ($fl) {
        $stmt = $conn->prepare("UPDATE product_flavors SET low_stock_threshold = ? WHERE id = ?");
        $stmt->bind_param("ii", $threshold, $flavor_id);
        $stmt->execute();
        $stmt->close();

        set_stock($conn, $flavor_id, $stock_value, $notes !== '' ? $notes : null, current_user_id());
        flash_message('success', 'Stock updated.');
    } else {
        flash_message('danger', 'Flavor not found.');
    }
    redirect(BASE_URL . '/admin/inventory.php');
}

$search = trim($_GET['search'] ?? '');
$where = "";
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where = "AND (p.name LIKE '%$s%' OR pf.flavor_name LIKE '%$s%')";
}

$rows = $conn->query("
    SELECT p.name AS product_name, p.qty_per_pack, p.unit_price,
           pf.id AS flavor_id, pf.flavor_name, pf.status,
           pf.stock_packs, pf.low_stock_threshold
    FROM products p
    JOIN product_flavors pf ON p.id = pf.product_id
    WHERE 1=1 $where
    ORDER BY p.sort_order, p.name, pf.sort_order
");

// Group by product + compute summary stats
$grouped = [];
$total_flavors = 0;
$out_count = 0;
$low_count = 0;
while ($row = $rows->fetch_assoc()) {
    $total_flavors++;
    $stock = (int)$row['stock_packs'];
    $threshold = (int)$row['low_stock_threshold'];
    if ($stock <= 0) {
        $out_count++;
    } elseif ($threshold > 0 && $stock <= $threshold) {
        $low_count++;
    }
    $grouped[$row['product_name']]['info'] = [
        'qty_per_pack' => $row['qty_per_pack'],
        'unit_price' => $row['unit_price'],
    ];
    $grouped[$row['product_name']]['flavors'][] = $row;
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Summary cards -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="icon icon-shape bg-gradient-info text-center rounded-circle me-3">
                            <i class="material-icons opacity-10">inventory</i>
                        </div>
                        <div>
                            <p class="text-sm mb-0 text-capitalize">Tracked Flavors</p>
                            <h5 class="mb-0"><?php echo $total_flavors; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="icon icon-shape bg-gradient-warning text-center rounded-circle me-3">
                            <i class="material-icons opacity-10">warning</i>
                        </div>
                        <div>
                            <p class="text-sm mb-0 text-capitalize">Low Stock</p>
                            <h5 class="mb-0"><?php echo $low_count; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="icon icon-shape bg-gradient-danger text-center rounded-circle me-3">
                            <i class="material-icons opacity-10">remove_shopping_cart</i>
                        </div>
                        <div>
                            <p class="text-sm mb-0 text-capitalize">Out of Stock</p>
                            <h5 class="mb-0"><?php echo $out_count; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row align-items-center">
                            <div class="col-md-6"><h6>Stock Management</h6>
                                <p class="text-sm text-muted mb-0">Stock is counted in <strong>packs</strong>. Edit a value &mdash; it saves automatically.</p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <a href="<?php echo BASE_URL; ?>/admin/inventory_history.php" class="btn btn-sm bg-gradient-dark mb-0">
                                    <i class="material-icons text-sm">history</i> View History
                                </a>
                            </div>
                        </div>
                        <form method="GET" class="input-group input-group-outline mt-3" style="max-width:360px;">
                            <input type="text" name="search" class="form-control" placeholder="Search product or flavor..." value="<?php echo sanitize($search); ?>">
                            <button class="btn bg-gradient-primary mb-0" type="submit"><i class="material-icons">search</i></button>
                        </form>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <?php if (empty($grouped)): ?>
                        <div class="text-center py-5">
                            <i class="material-icons" style="font-size:48px;color:#ccc;">inventory_2</i>
                            <p class="text-muted mt-2">No flavors found<?php echo $search ? ' for "' . sanitize($search) . '"' : ''; ?>.</p>
                        </div>
                        <?php else: ?>
                        <?php
                        // One <form> per flavor, placed outside the table. Inputs in the
                        // table cells associate to it via the HTML5 form="" attribute.
                        foreach ($grouped as $data) {
                            foreach ($data['flavors'] as $f) {
                                echo '<form id="invf_' . (int)$f['flavor_id'] . '" method="POST" class="d-none">';
                                echo '<input type="hidden" name="flavor_id" value="' . (int)$f['flavor_id'] . '">';
                                echo '</form>';
                            }
                        }
                        ?>
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Product / Flavor</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center">Current</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center" style="width:120px;">Set Stock</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-center" style="width:120px;">Low Alert</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Note</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grouped as $product_name => $data): ?>
                                    <tr class="bg-light">
                                        <td colspan="6" class="ps-4 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="material-icons text-primary me-2">icecream</i>
                                                <strong class="text-sm"><?php echo sanitize($product_name); ?></strong>
                                                <span class="text-xs text-muted ms-2">(<?php echo $data['info']['qty_per_pack']; ?>/pack | <?php echo format_currency($data['info']['unit_price']); ?>/unit)</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php foreach ($data['flavors'] as $f):
                                        $stock = (int)$f['stock_packs'];
                                        $threshold = (int)$f['low_stock_threshold'];
                                        if ($stock <= 0) {
                                            $badge = 'bg-gradient-danger'; $label = 'Out (' . $stock . ')';
                                        } elseif ($threshold > 0 && $stock <= $threshold) {
                                            $badge = 'bg-gradient-warning'; $label = 'Low (' . $stock . ')';
                                        } else {
                                            $badge = 'bg-gradient-success'; $label = $stock . ' packs';
                                        }
                                    ?>
                                    <?php $ff = 'invf_' . (int)$f['flavor_id']; ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-sm"><?php echo sanitize($f['flavor_name']); ?></span>
                                            <?php if ($f['status'] !== 'active'): ?>
                                            <span class="badge bg-gradient-secondary text-xxs ms-1">inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><span class="badge stock-badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
                                        <td>
                                            <input type="number" form="<?php echo $ff; ?>" name="stock_value" class="form-control form-control-sm text-center" value="<?php echo $stock; ?>" min="0">
                                        </td>
                                        <td>
                                            <input type="number" form="<?php echo $ff; ?>" name="threshold" class="form-control form-control-sm text-center" value="<?php echo $threshold; ?>" min="0" title="Warn when stock falls to this many packs (0 = off)">
                                        </td>
                                        <td>
                                            <input type="text" form="<?php echo $ff; ?>" name="notes" class="form-control form-control-sm" placeholder="optional">
                                        </td>
                                        <td class="text-end pe-3" style="min-width:90px;">
                                            <span class="save-status text-xs"></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function showStatus(el, text, cls) {
        if (!el) return;
        el.textContent = text;
        el.className = 'save-status text-xs ' + (cls || '');
    }

    function saveRow(formId) {
        var stockEl = document.querySelector('input[name="stock_value"][form="' + formId + '"]');
        if (!stockEl) return;
        var thrEl  = document.querySelector('input[name="threshold"][form="' + formId + '"]');
        var noteEl = document.querySelector('input[name="notes"][form="' + formId + '"]');
        var row    = stockEl.closest('tr');
        var badge  = row.querySelector('.stock-badge');
        var status = row.querySelector('.save-status');
        var fid    = parseInt(formId.replace('invf_', ''), 10);

        showStatus(status, 'Saving…', 'text-muted');

        fetch(BASE_URL + '/admin/inventory_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                flavor_id: fid,
                stock_value: parseInt(stockEl.value, 10) || 0,
                threshold: parseInt(thrEl ? thrEl.value : 0, 10) || 0,
                notes: noteEl ? noteEl.value : ''
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                if (badge) { badge.className = 'badge stock-badge ' + d.badge_class; badge.textContent = d.label; }
                if (noteEl) { noteEl.value = ''; }
                showStatus(status, '✓ Saved', 'text-success');
                setTimeout(function () { showStatus(status, '', ''); }, 1500);
            } else {
                showStatus(status, '✗ ' + (d.message || 'Failed'), 'text-danger');
            }
        })
        .catch(function () { showStatus(status, '✗ Network error', 'text-danger'); });
    }

    // Auto-save when a stock or threshold value is changed (fires on blur / commit)
    document.querySelectorAll('input[name="stock_value"], input[name="threshold"]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var formId = inp.getAttribute('form');
            if (formId) saveRow(formId);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
