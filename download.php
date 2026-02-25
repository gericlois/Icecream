<?php
require_once 'config/constants.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Download App - <?php echo APP_SHORT; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/css/material-dashboard.min.css" />
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#E91E63">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/img/icons/icon-152x152.png">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/icons/icon-96x96.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #ffffff; min-height: 100vh; }

        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            text-align: center;
            color: #333;
        }

        .app-icon {
            width: 120px;
            height: 120px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(233, 30, 99, 0.4);
            margin-bottom: 1.5rem;
        }
        .app-icon img { width: 100%; height: 100%; object-fit: cover; }

        .hero h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .hero .tagline {
            font-size: 1rem;
            color: #666;
            margin-bottom: 2rem;
        }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            max-width: 400px;
            width: 100%;
            margin-bottom: 2.5rem;
        }
        .feature-item {
            background: #f5f5f5;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }
        .feature-item .material-icons {
            font-size: 32px;
            color: #E91E63;
            margin-bottom: 0.5rem;
        }
        .feature-item h3 {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .feature-item p {
            font-size: 0.7rem;
            color: #888;
            line-height: 1.3;
        }

        .install-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(195deg, #FFC107, #FFB300);
            color: #333;
            border: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(255, 193, 7, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .install-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(255, 193, 7, 0.5);
            color: #333;
        }
        .install-btn .material-icons { font-size: 22px; }

        .login-link {
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }
        .login-link:hover { color: #fff; }

        .instructions {
            max-width: 400px;
            width: 100%;
            background: #f5f5f5;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: none;
        }
        .instructions.show { display: block; }
        .instructions h3 {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #E91E63;
        }

        .step {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            align-items: flex-start;
        }
        .step-num {
            background: #E91E63;
            color: #ffffff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .step-text {
            font-size: 0.8rem;
            color: #555;
            line-height: 1.4;
        }

        .footer-text {
            font-size: 0.75rem;
            color: #999;
            line-height: 1.5;
        }

        @media (min-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .features { grid-template-columns: repeat(4, 1fr); max-width: 600px; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="app-icon">
            <img src="<?php echo BASE_URL; ?>/assets/img/icons/icon-192x192.png" alt="JMC Foodies">
        </div>

        <h1><i class="material-icons" style="font-size:28px;vertical-align:middle;">icecream</i> <?php echo APP_SHORT; ?></h1>
        <p class="tagline">Ice Cream Distribution System</p>

        <div class="features">
            <div class="feature-item">
                <i class="material-icons">shopping_cart</i>
                <h3>Order Online</h3>
                <p>Browse catalog & place orders anytime</p>
            </div>
            <div class="feature-item">
                <i class="material-icons">account_balance_wallet</i>
                <h3>E-Funds Wallet</h3>
                <p>Reload via GCash & get discounts</p>
            </div>
            <div class="feature-item">
                <i class="material-icons">bolt</i>
                <h3>Electric Subsidy</h3>
                <p>Earn 5% subsidy on your orders</p>
            </div>
            <div class="feature-item">
                <i class="material-icons">receipt_long</i>
                <h3>Track Orders</h3>
                <p>Real-time order status updates</p>
            </div>
        </div>

        <!-- Download APK Button -->
        <a href="https://drive.google.com/uc?export=download&id=1EEixO_pxrb3WimuFj9dRi_6CJ5Gs0ZDI" class="install-btn">
            <i class="material-icons">android</i>
            Download for Android
        </a>

        <p style="font-size:0.8rem;color:#999;margin-bottom:1.5rem;">Version 1.0 &bull; APK File</p>

        <a href="<?php echo BASE_URL; ?>/index.php" class="login-link">Already have access? Sign in here</a>

        <!-- Android Install Instructions -->
        <div class="instructions show">
            <h3><i class="material-icons" style="font-size:18px;vertical-align:middle;">android</i> How to Install</h3>
            <div class="step">
                <span class="step-num">1</span>
                <span class="step-text">Tap <strong>"Download for Android"</strong> above</span>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <span class="step-text">Open the downloaded <strong>jmc-foodies.apk</strong> file</span>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <span class="step-text">If prompted, allow <strong>"Install from unknown sources"</strong> in Settings</span>
            </div>
            <div class="step">
                <span class="step-num">4</span>
                <span class="step-text">Tap <strong>"Install"</strong> and open the app</span>
            </div>
        </div>

        <!-- iPhone Instructions -->
        <div class="instructions show">
            <h3><i class="material-icons" style="font-size:18px;vertical-align:middle;">phone_iphone</i> iPhone Users</h3>
            <div class="step">
                <span class="step-num">1</span>
                <span class="step-text">Open <strong><?php echo BASE_URL ?: 'this site'; ?></strong> in <strong>Safari</strong></span>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <span class="step-text">Tap the <strong>Share button</strong> <span style="font-size:16px;">&#x2191;</span> at the bottom</span>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <span class="step-text">Tap <strong>"Add to Home Screen"</strong> to install as a web app</span>
            </div>
        </div>

        <p class="footer-text">
            <?php echo APP_NAME; ?><br>
            <?php echo COMPANY_ADDRESS; ?><br>
            <?php echo COMPANY_HOTLINE; ?>
        </p>
    </div>

    <script>
        // If already installed as PWA, redirect to login
        if (window.matchMedia('(display-mode: standalone)').matches) {
            window.location.href = '<?php echo BASE_URL; ?>/index.php';
        }
    </script>
</body>
</html>
