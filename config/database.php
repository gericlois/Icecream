<?php
/*
 * DATABASE CONFIGURATION
 * =====================
 * For LOCAL (XAMPP):
 *   $db_host = 'localhost';
 *   $db_user = 'root';
 *   $db_pass = '';
 *   $db_name = 'jmc_icecream';
 *
 * For INFINITYFREE:
 *   $db_host = 'sql__.infinityfree.com';  (check your panel for exact host)
 *   $db_user = 'if0_XXXXXXX';             (from InfinityFree MySQL panel)
 *   $db_pass = 'your_password_here';       (from InfinityFree MySQL panel)
 *   $db_name = 'if0_XXXXXXX_jmc_icecream'; (from InfinityFree MySQL panel)
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'jmc_icecream';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");
