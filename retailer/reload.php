<?php
$page_title = 'Reload E-Funds';
$active_page = 'efunds';

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_role(['retailer']);

$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? '';
    $ref = trim($_POST['reference_number'] ?? '');

    if ($amount < 500 || !in_array($method, ['gcash', 'bank_transfer'])) {
        flash_message('danger', $amount < 500 ? 'Minimum reload amount is ₱500.00.' : 'Please fill in all required fields correctly.');
        redirect(BASE_URL . '/retailer/reload.php');
    }

    // Handle proof upload
    $proof_filename = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['proof']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            flash_message('danger', 'Invalid file type. Only JPG, PNG, GIF allowed.');
            redirect(BASE_URL . '/retailer/reload.php');
        }
        if ($_FILES['proof']['size'] > 2 * 1024 * 1024) {
            flash_message('danger', 'File too large. Max 2MB.');
            redirect(BASE_URL . '/retailer/reload.php');
        }

        $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $proof_filename = 'proof_' . $uid . '_' . time() . '.' . $ext;
        $dest = UPLOAD_PATH . 'proof/' . $proof_filename;
        move_uploaded_file($_FILES['proof']['tmp_name'], $dest);
    }

    $stmt = $conn->prepare("INSERT INTO reload_requests (user_id, amount, method, reference_number, proof_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $uid, $amount, $method, $ref, $proof_filename);
    $stmt->execute();
    $stmt->close();

    flash_message('success', 'Reload request submitted! Amount: ' . format_currency($amount) . '. Please wait for admin approval.');
    redirect(BASE_URL . '/retailer/efunds.php');
}

// Get pending requests
$pending = $conn->query("SELECT * FROM reload_requests WHERE user_id = $uid ORDER BY created_at DESC LIMIT 10");

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    <?php require_once '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php show_flash(); ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Request E-Funds Reload</h6></div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Amount (₱) * (Min ₱500)</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="500" required>
                            </div>
                            <div class="input-group input-group-static my-3">
                                <label class="ms-0">Payment Method *</label>
                                <select name="method" class="form-control" required onchange="toggleGcashQR(this.value)">
                                    <option value="">-- Select --</option>
                                    <option value="gcash">GCash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>

                            <div id="gcashQRSection" style="display:none;" class="my-3 text-center">
                                <div class="card card-body border shadow-none">
                                    <p class="text-sm font-weight-bold mb-2">Scan QR Code to Pay via GCash</p>
                                    <img src="<?php echo BASE_URL; ?>/assetsimg/GCash.jpg" alt="GCash QR Code" style="max-width:260px; border-radius:12px;" class="mx-auto">
                                    <p class="text-xs text-muted mt-2 mb-0">After payment, enter the reference number and upload proof below.</p>
                                </div>
                            </div>

                            <div class="input-group input-group-outline my-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control">
                            </div>
                            <div class="my-3">
                                <label class="form-label d-block mb-2">Upload Proof (screenshot)</label>
                                <label class="btn btn-sm bg-gradient-dark mb-1" style="cursor:pointer;">
                                    <i class="material-icons align-middle text-sm">upload_file</i> Choose File
                                    <input type="file" name="proof" accept="image/*" style="display:none;" onchange="document.getElementById('proofFileName').textContent = this.files[0] ? this.files[0].name : 'No file chosen'">
                                </label>
                                <span id="proofFileName" class="text-sm text-muted ms-2">No file chosen</span>
                                <br><small class="text-muted">JPG, PNG, GIF. Max 2MB.</small>
                            </div>
                            <button type="submit" class="btn bg-gradient-primary w-100">Submit Reload Request</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header pb-0"><h6>Recent Requests</h6></div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Amount</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Method</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $pending->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4"><span class="text-sm font-weight-bold"><?php echo format_currency($r['amount']); ?></span></td>
                                        <td><span class="text-xs"><?php echo strtoupper(str_replace('_', ' ', $r['method'])); ?></span></td>
                                        <td><span class="badge bg-gradient-<?php echo $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                        <td><span class="text-xs"><?php echo date('M d', strtotime($r['created_at'])); ?></span></td>
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

<script>
function toggleGcashQR(value) {
    document.getElementById('gcashQRSection').style.display = value === 'gcash' ? 'block' : 'none';
}
</script>

<?php require_once '../includes/footer.php'; ?>
