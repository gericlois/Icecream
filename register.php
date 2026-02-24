<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$success = '';
if (isset($_SESSION['register_error'])) {
    $error = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sign Up - <?php echo APP_SHORT; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/css/material-dashboard.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css" />
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg">
</head>
<body class="bg-gray-200">
    <div class="login-page" style="background: #f0f2f5 !important; align-items: flex-start; padding: 2rem 0;">
        <div class="login-card" style="max-width: 700px;">
            <div class="card z-index-0">
                <div class="login-header">
                    <img src="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg" alt="JMC Foodies" style="width:120px;height:120px;border-radius:16px;object-fit:cover;" class="mb-2">
                    <p class="mb-0 text-sm">Retailer Application Form</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger text-white text-sm" role="alert">
                        <?php echo sanitize($error); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success text-white text-sm" role="alert">
                        <?php echo sanitize($success); ?>
                    </div>
                    <?php else: ?>

                    <form method="POST" action="<?php echo BASE_URL; ?>/register_process.php" class="text-start">

                        <!-- Account Details -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-3 mb-2">Account Details</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Username *</label>
                                    <input type="text" name="username" class="form-control" required minlength="3">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Password *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                            </div>
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <label class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <!-- Personal Information -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Personal Information</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-static mb-3">
                                    <label class="ms-0">Birthday</label>
                                    <input type="date" name="birthday" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static mb-3">
                                    <label class="ms-0">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">-- Select --</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">SSS/GSIS #</label>
                                    <input type="text" name="sss_gsis" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">TIN #</label>
                                    <input type="text" name="tin" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <label class="form-label">Address *</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Tel. No.</label>
                                    <input type="text" name="tel_no" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Mobile *</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Application Information -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Application Information</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-static mb-3">
                                    <label class="ms-0">Type of Application</label>
                                    <select name="application_type" class="form-control">
                                        <option value="">-- Select --</option>
                                        <option value="cod">Cash on Delivery</option>
                                        <option value="7days_term">7 Days Term</option>
                                    </select>
                                </div>
                                <p class="text-xs text-muted mt-n2">*For 7 Days Term: P1,000.00 one-time collector's fee applies.</p>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-static mb-3">
                                    <label class="ms-0">Package Information</label>
                                    <select name="package_info" class="form-control">
                                        <option value="">-- Select --</option>
                                        <?php
                                        $packages_result = $conn->query("SELECT slug, name FROM packages WHERE status = 'active' ORDER BY sort_order");
                                        while ($pkg = $packages_result->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo sanitize($pkg['slug']); ?>"><?php echo sanitize($pkg['name']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">NAO's Name</label>
                                    <input type="text" name="nao_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Salesman Name</label>
                                    <input type="text" name="salesman_name" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Authorized Representative -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Authorized Representative</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="auth_rep_name" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Relationship</label>
                                    <input type="text" name="auth_rep_relationship" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-static mb-3">
                                    <label class="ms-0">Gender</label>
                                    <select name="auth_rep_gender" class="form-control">
                                        <option value="">-- Select --</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Freezer Information -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Freezer Information</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Brand</label>
                                    <input type="text" name="freezer_brand" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Size</label>
                                    <input type="text" name="freezer_size" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Serial #</label>
                                    <input type="text" name="freezer_serial" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline mb-3">
                                    <label class="form-label">Freezer Status</label>
                                    <input type="text" name="freezer_status" class="form-control">
                                </div>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mt-4 mb-2">Terms and Conditions</h6>
                        <hr class="horizontal dark mt-0 mb-3">
                        <div class="border rounded p-3 mb-3" style="max-height:200px;overflow-y:auto;background:#fafafa;font-size:0.75rem;line-height:1.6;">
                            <p class="font-weight-bold mb-1">TERMS AND CONDITIONS:</p>
                            <p class="mb-2">Upon Signing this form, you are obliged to know more about JMC Foodies Ice Cream Distributions and all the benefits that you can avail. You agreed with full consent and will to be an independent reseller. You are hereby attested as an official reseller of JMC Foodies Ice Cream Distributions and any means of violations from the POLICIES AND PROCEDURE written below will merit a disciplinary action or termination of your resellership.</p>
                            <p class="font-weight-bold mb-1">POLICIES AND PROCEDURES:</p>
                            <ul class="ps-3 mb-0">
                                <li>Understand the Terms and Conditions</li>
                                <li>For the payment of initial products and re-orders only cash on delivery (COD) and e-fund payment via Gcash Cash in is accepted.</li>
                                <li>For Product Bad Orders, only melted and damaged products due to mishandling of delivery and calamity are refundable. Bad Orders due to negligence of resellers are not refundable.</li>
                                <li>For Bad Orders due to mishandling of delivery the full amount of melted or deformed products are refundable. For B.O. due to calamity only 60% of the product value will be refunded.</li>
                                <li>In case of Scheduled Power Interruption NO OPENING of freezer is strictly implemented to avoid damages and deformation on the products. The freezer can sustain the products up to 18 hours of proper handling.</li>
                                <li>In case of unscheduled power interruption same procedures will be applied with Scheduled Power Interruption.</li>
                                <li>In cases of freezer breakdown or any type of power interruption, the reseller is obliged to report immediately to the concerned agent or directly to the hotlines of JMC Foodies Ice Cream Distributions to avoid unwanted damages and delays in both business operation.</li>
                                <li>The freezer is provided in terms of lending-borrowing platform which means that the ownership of the freezer belongs to JMC Foodies Ice Cream Distributions and not to the reseller or any of the employees and agents of the company. Freezer Lending is protected by legal contract as well as the freezer pull out activities. Always transact with documents even with authorized personnel to avoid inconvenience in the future.</li>
                                <li>A 5% Electric Subsidy is applied for resellers with a minimum of P8,000.00 monthly re-order exclusive of the initial package.</li>
                                <li>To qualify every reseller for the free freezer lending-borrowing platform, the reseller must remain active or comply on at least once a week ordering. A minimum of P2,000.00 product reorder is required for every delivery.</li>
                                <li>For Three (3) consecutive weeks of non-ordering the account becomes dormant and is subject for immediate freezer pull-out with a maximum 7 days grace period before a mandatory freezer pull-out take place. Any remaining products is non-refundable upon declaration of account dormancy.</li>
                            </ul>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label text-sm" for="agreeTerms">I have read and agree to the <strong>Terms and Conditions</strong> and <strong>Policies and Procedures</strong>. *</label>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn bg-gradient-primary w-100 mb-2">Submit Application</button>
                        </div>
                    </form>

                    <?php endif; ?>

                    <p class="text-center text-sm mt-2 mb-0">
                        Already have an account? <a href="<?php echo BASE_URL; ?>/index.php" class="text-primary font-weight-bold">Sign In</a>
                    </p>
                </div>
            </div>
            <p class="text-center text-dark text-sm mt-3 mb-0"><?php echo APP_NAME; ?></p>
            <p class="text-center text-secondary text-xs"><?php echo COMPANY_ADDRESS; ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/js/material-dashboard.min.js"></script>
</body>
</html>
