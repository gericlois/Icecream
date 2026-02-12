<?php
$page_title = 'Catalog';
$active_page = 'catalog';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$search = trim($_GET['search'] ?? '');
$where = "";
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where = "AND (p.name LIKE '%$s%' OR pf.flavor_name LIKE '%$s%')";
}

$products = $conn->query("
    SELECT p.id as product_id, p.name as product_name, p.qty_per_pack, p.unit_price,
           pf.id as flavor_id, pf.flavor_name
    FROM products p
    JOIN product_flavors pf ON p.id = pf.product_id
    WHERE p.status = 'active' AND pf.status = 'active' $where
    ORDER BY p.sort_order, p.name, pf.sort_order
");

// Group by product
$grouped = [];
while ($row = $products->fetch_assoc()) {
    $grouped[$row['product_name']]['info'] = [
        'qty_per_pack' => $row['qty_per_pack'],
        'unit_price' => $row['unit_price']
    ];
    $grouped[$row['product_name']]['flavors'][] = [
        'id' => $row['flavor_id'],
        'name' => $row['flavor_name']
    ];
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <!-- Search -->
        <div class="row mb-4">
            <div class="col-12">
                <form method="GET" class="input-group input-group-outline">
                    <input type="text" name="search" class="form-control" placeholder="Search products or flavors..." value="<?php echo sanitize($search); ?>">
                    <button class="btn bg-gradient-primary mb-0" type="submit">
                        <i class="material-icons">search</i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Product Accordion -->
        <div class="accordion" id="catalogAccordion">
            <?php $idx = 0; foreach ($grouped as $product_name => $data): $collapseId = 'cat' . $idx; ?>
            <div class="accordion-item border mb-2" style="border-radius:0.5rem;overflow:hidden;">
                <h2 class="accordion-header" id="heading<?php echo $idx; ?>">
                    <?php $img_path = 'assetsimg/icecream/' . $product_name . '.png'; ?>
                    <button class="accordion-button <?php echo $idx === 0 ? '' : 'collapsed'; ?> py-3" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $idx === 0 ? 'true' : 'false'; ?>" style="background:#fff;box-shadow:none;">
                        <div class="d-flex align-items-center w-100">
                            <?php if (file_exists(__DIR__ . '/../' . $img_path)): ?>
                            <img src="<?php echo BASE_URL . '/' . $img_path; ?>" alt="<?php echo sanitize($product_name); ?>" class="ms-2" style="width:40px;height:40px;object-fit:contain;">
                            <?php endif; ?>
                            <strong class="text-dark ms-3"><?php echo sanitize($product_name); ?></strong>
                            <span class="ms-2 text-muted text-sm">(<?php echo $data['info']['qty_per_pack']; ?>/pack | <?php echo format_currency($data['info']['unit_price']); ?>/unit)</span>
                            <span class="badge bg-gradient-info ms-auto me-3"><?php echo count($data['flavors']); ?> flavors</span>
                        </div>
                    </button>
                </h2>
                <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $idx === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $idx; ?>" data-bs-parent="#catalogAccordion">
                    <div class="accordion-body p-3">
                        <div class="row">
                            <?php foreach ($data['flavors'] as $flavor): ?>
                            <div class="col-6 col-sm-6 col-lg-4 col-xl-3 mb-3">
                                <div class="card product-card h-100 shadow-sm">
                                    <div class="card-body text-center p-3">
                                        <?php if (file_exists(__DIR__ . '/../' . $img_path)): ?>
                                        <img src="<?php echo BASE_URL . '/' . $img_path; ?>" alt="<?php echo sanitize($product_name); ?>" style="width:60px;height:60px;object-fit:contain;" class="mb-2">
                                        <?php endif; ?>
                                        <p class="text-sm font-weight-bold mb-1"><?php echo sanitize($flavor['name']); ?></p>
                                        <p class="text-xs mb-2">
                                            <span class="badge bg-gradient-dark"><?php echo $data['info']['qty_per_pack']; ?>/pack</span>
                                            <span class="badge bg-gradient-success"><?php echo format_currency($data['info']['unit_price']); ?>/unit</span>
                                        </p>
                                        <p class="text-sm font-weight-bold text-primary mb-2">
                                            Pack Total: <?php echo format_currency($data['info']['qty_per_pack'] * $data['info']['unit_price']); ?>
                                        </p>
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <input type="number" class="form-control form-control-sm qty-input" value="1" min="1" style="width:60px;">
                                            <button class="btn btn-sm bg-gradient-primary mb-0" onclick="addToCart(<?php echo $flavor['id']; ?>, this)">
                                                Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php $idx++; endforeach; ?>
        </div>

        <?php if (empty($grouped)): ?>
        <div class="text-center py-5">
            <i class="material-icons" style="font-size:48px;color:#ccc;">search_off</i>
            <p class="text-muted mt-2">No products found<?php echo $search ? ' for "' . sanitize($search) . '"' : ''; ?></p>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
