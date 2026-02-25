<?php
$page_title = 'Add Package';
$active_page = 'packages';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['admin']);

$id = (int)($_GET['id'] ?? 0);
$package = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$package) {
        flash_message('danger', 'Package not found.');
        redirect(BASE_URL . '/admin/packages.php');
    }

    $page_title = 'Edit: ' . $package['name'];
}

$error = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    // Check if any users reference this package
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE package_info = ?");
    $slug = $package['slug'];
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    if ($count > 0) {
        $error = "Cannot delete this package — $count user(s) are using it. Set it to inactive instead.";
    } else {
        $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        flash_message('success', 'Package deleted.');
        redirect(BASE_URL . '/admin/packages.php');
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    $subsidy_rate = (float)($_POST['subsidy_rate'] ?? 0) / 100; // Convert % to decimal
    $subsidy_min_orders = (float)($_POST['subsidy_min_orders'] ?? 0);
    $freezer_display_allowance = (float)($_POST['freezer_display_allowance'] ?? 0);

    // Auto-generate slug from name if empty
    if (empty($slug) && !empty($name)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $slug = trim($slug, '_');
    }

    if (empty($name) || empty($slug)) {
        $error = 'Package name is required.';
    } else {
        // Check slug uniqueness
        $check_sql = $id > 0
            ? "SELECT id FROM packages WHERE slug = ? AND id != ?"
            : "SELECT id FROM packages WHERE slug = ?";
        $stmt = $conn->prepare($check_sql);
        if ($id > 0) {
            $stmt->bind_param("si", $slug, $id);
        } else {
            $stmt->bind_param("s", $slug);
        }
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'A package with this slug already exists.';
        }
        $stmt->close();

        if (empty($error)) {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE packages SET name=?, slug=?, description=?, sort_order=?, status=?, subsidy_rate=?, subsidy_min_orders=?, freezer_display_allowance=? WHERE id=?");
                $stmt->bind_param("sssisdddi", $name, $slug, $description, $sort_order, $status, $subsidy_rate, $subsidy_min_orders, $freezer_display_allowance, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO packages (name, slug, description, sort_order, status, subsidy_rate, subsidy_min_orders, freezer_display_allowance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssisddd", $name, $slug, $description, $sort_order, $status, $subsidy_rate, $subsidy_min_orders, $freezer_display_allowance);
            }
            $stmt->execute();
            $stmt->close();

            flash_message('success', 'Package saved successfully.');
            redirect(BASE_URL . '/admin/packages.php');
        }
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
                        <h6><?php echo $package ? 'Edit Package' : 'Add Package'; ?></h6>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger text-white text-sm"><?php echo sanitize($error); ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo $package ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Package Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo sanitize($package['name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline <?php echo $package ? 'is-filled' : ''; ?> my-3">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug" class="form-control" value="<?php echo sanitize($package['slug'] ?? ''); ?>" placeholder="Auto-generated if empty">
                                    </div>
                                </div>
                            </div>
                            <div class="input-group input-group-outline <?php echo !empty($package['description']) ? 'is-filled' : ''; ?> my-3">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control" value="<?php echo sanitize($package['description'] ?? ''); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?php echo $package['sort_order'] ?? 0; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-static my-3">
                                        <label class="ms-0">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active" <?php echo ($package['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($package['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Electric Subsidy</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Subsidy Rate (%)</label>
                                        <input type="number" name="subsidy_rate" class="form-control" value="<?php echo ($package['subsidy_rate'] ?? 0) * 100; ?>" step="0.1" min="0" max="100">
                                    </div>
                                    <p class="text-xs text-muted mt-n2">e.g. 2 = 2%, 5 = 5%</p>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Min. Monthly Orders (₱)</label>
                                        <input type="number" name="subsidy_min_orders" class="form-control" value="<?php echo $package['subsidy_min_orders'] ?? 0; ?>" step="1" min="0">
                                    </div>
                                    <p class="text-xs text-muted mt-n2">Minimum delivered orders to qualify for subsidy</p>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Freezer Display Allowance</h6>
                            <hr class="horizontal dark mt-0 mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group input-group-outline is-filled my-3">
                                        <label class="form-label">Allowance Amount (₱/month)</label>
                                        <input type="number" name="freezer_display_allowance" class="form-control" value="<?php echo $package['freezer_display_allowance'] ?? 0; ?>" step="1" min="0">
                                    </div>
                                    <p class="text-xs text-muted mt-n2">Fixed monthly allowance when retailer meets order quota</p>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <?php if ($package): ?>
                                <button type="submit" name="delete" value="1" class="btn btn-outline-danger me-auto float-start" onclick="return confirm('Are you sure you want to delete this package?')">Delete</button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>/admin/packages.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn bg-gradient-primary">Save Package</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
