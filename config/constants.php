<?php
date_default_timezone_set('Asia/Manila');

define('APP_NAME', 'JMC FOODIES ICE CREAM DISTRIBUTIONS');
define('APP_SHORT', 'JMC Foodies');
define('COMPANY_ADDRESS', '#116 Purok 1 Barangay Isla Santa Rosa, Nueva Ecija, Philippines 3101');
define('COMPANY_TIN', '000-420-482-187');
define('COMPANY_HOTLINE', '0956 667 3569');
define('COMPANY_EMAIL', 'jmcfoodiesdistributions@gmail.com');

/*
 * BASE_URL CONFIGURATION
 * ======================
 * Auto-detects environment:
 *   - Local (localhost / 127.0.0.1) -> '/Icecream' (app runs in a subfolder)
 *   - Live  (deployed at domain root) -> '' (empty string)
 */
$host_header = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($host_header, 'localhost') !== false)
         || (strpos($host_header, '127.0.0.1') !== false);
define('BASE_URL', $is_local ? '/Icecream' : '');

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
