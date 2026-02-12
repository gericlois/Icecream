<?php
$page_title = 'Edit Product';
$active_page = 'products';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$product = null;
$flavors = [];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        flash_message('danger', 'Product not found.');
        redirect(BASE_URL . '/admin/products.php');
    }

    $page_title = 'Edit: ' . $product['name'];
    $r = $conn->query("SELECT * FROM product_flavors WHERE product_id = $id ORDER BY sort_order");
    while ($f = $r->fetch_assoc()) $flavors[] = $f;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $qty_per_pack = (int)($_POST['qty_per_pack'] ?? 1);
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $flavor_names = $_POST['flavor_names'] ?? [];
    $flavor_ids = $_POST['flavor_ids'] ?? [];

    if (empty($name) || $qty_per_pack < 1 || $unit_price < 0) {
        $error = 'Name, qty per pack, and unit price are required.';
    } else {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE products SET name=?, qty_per_pack=?, unit_price=?, sort_order=?, status=? WHERE id=?");
            $stmt->bind_param("sidssi", $name, $qty_per_pack, $unit_price, $sort_order, $status, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, qty_per_pack, unit_price, sort_order, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sidis", $name, $qty_per_pack, $unit_price, $sort_order, $status);
            $stmt->execute();
            $id = $conn->insert_id;
            $stmt->close();
        }

        // Handle flavors - delete removed, update/insert others
        $existing_ids = array_filter(array_map('intval', $flavor_ids));
        if (!empty($existing_ids)) {
            $ids_str = implode(',', $existing_ids);
            $conn->query("DELETE FROM product_flavors WHERE product_id = $id AND id NOT IN ($ids_str)");
        } else {
            $conn->query("DELETE FROM product_flavors WHERE product_id = $id");
        }

        foreach ($flavor_names as $i => $fname) {
            $fname = trim($fname);
            if (empty($fname)) continue;
            $fid = (int)($flavor_ids[$i] ?? 0);
            $sort = $i + 1;
            if ($fid > 0) {
                $stmt = $conn->prepare("UPDATE product_flavors SET flavor_name=?, sort_order=? WHERE id=? AND product_id=?");
                $stmt->bind_param("siii", $fname, $sort, $fid, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO product_flavors (product_id, flavor_name, sort_order) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $id, $fname, $sort);
            }
            $stmt->execute();
            $stmt->close();
        }

        flash_message('success', 'Product saved successfully.');
        redirect(BASE_URL . '/admin/products.php');
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6><?php echo $product ? 'Edit Product' : 'Add Product'; ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo $product ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Product Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo sanitize($product['name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Qty Per Pack</label>
                                        <input type="number" name="qty_per_pack" class="form-control" value="<?php echo $product['qty_per_pack'] ?? 1; ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Unit Price</label>
                                        <input type="number" name="unit_price" class="form-control" value="<?php echo $product['unit_price'] ?? 0; ?>" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo $product['sort_order'] ?? 0; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6>Flavors</h6>
                            <div id="flavorsContainer">
                                <?php if (!empty($flavors)): ?>
                                    <?php foreach ($flavors as $i => $f): ?>
                                    <div class="flavor-row d-flex align-items-center mb-2">
                                        <input type="hidden" name="flavor_ids[]" value="<?php echo $f['id']; ?>">
                                        <div class="input-group input-group-outline is-filled flex-grow-1 me-2">
                                            <label class="form-label">Flavor Name</label>
                                            <input type="text" name="flavor_names[]" class="form-control" value="<?php echo sanitize($f['flavor_name']); ?>">
                                        </div>
                                        <button type="button" class="btn btn-sm bg-gradient-danger mb-0" onclick="this.closest('.flavor-row').remove()">
                                            <i class="material-icons text-sm">delete</i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm bg-gradient-info" onclick="addFlavor()">
                                <i class="material-icons text-sm">add</i> Add Flavor
                            </button>

                            <div class="text-end mt-4">
                                <a href="<?php echo BASE_URL; ?>/admin/products.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn bg-gradient-primary">Save Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function addFlavor() {
    const container = document.getElementById('flavorsContainer');
    const row = document.createElement('div');
    row.className = 'flavor-row d-flex align-items-center mb-2';
    row.innerHTML = `
        <input type="hidden" name="flavor_ids[]" value="0">
        <div class="input-group input-group-outline flex-grow-1 me-2">
            <label class="form-label">Flavor Name</label>
            <input type="text" name="flavor_names[]" class="form-control">
        </div>
        <button type="button" class="btn btn-sm bg-gradient-danger mb-0" onclick="this.closest('.flavor-row').remove()">
            <i class="material-icons text-sm">delete</i>
        </button>
    `;
    container.appendChild(row);
}
</script>

<?php require_once '../includes/footer.php'; ?>
