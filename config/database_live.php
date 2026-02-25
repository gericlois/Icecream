<?php
/*
 * DATABASE CONFIGURATION - LIVE (InfinityFree)
 * =============================================
 * Rename this file to database.php on the live server
 */

$db_host = 'sql306.infinityfree.com';
$db_user = 'if0_41104260';
$db_pass = 'jmcIcecream2026';
$db_name = 'if0_41104260_jmc_icecream';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");
