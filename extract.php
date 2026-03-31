<?php
/**
 * One-time extraction script for deployment
 * Upload this file + Icecream.zip to htdocs/
 * Visit: yourdomain.com/extract.php
 * DELETE THIS FILE AFTER USE
 */

$zip_file = __DIR__ . '/Icecream.zip';

if (!file_exists($zip_file)) {
    die('Error: Icecream.zip not found. Upload it to the same folder as this script.');
}

$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo(__DIR__);
    $zip->close();

    // Delete the zip and this script
    unlink($zip_file);
    unlink(__FILE__);

    echo '<h2 style="font-family:sans-serif;color:green;">Deployment successful!</h2>';
    echo '<p style="font-family:sans-serif;">Files extracted. Zip and extract script auto-deleted.</p>';
    echo '<p style="font-family:sans-serif;"><a href="/">Go to site &rarr;</a></p>';
} else {
    die('Error: Could not open zip file.');
}
