<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    <title><?php echo isset($page_title) ? sanitize($page_title) . ' - ' : ''; ?>JMC Foodies</title>
    <link rel="icon" type="image/jpeg" href="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700&display=swap" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/material-dashboard@3.0.9/assets/css/material-dashboard.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css" />

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'retailer'): ?>
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#E91E63">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="JMC Foodies">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assetsimg/icon/jmc_icon.jpg">
    <meta name="description" content="Order ice cream products from JMC Foodies Ice Cream Distributions">
    <?php endif; ?>
</head>
<body class="g-sidenav-show bg-gray-200 <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'retailer') ? 'retailer-view' : ''; ?>">
