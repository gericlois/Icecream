<?php
/**
 * Deletes all files and folders in htdocs/ except this script
 * Upload to htdocs/, visit: yourdomain.com/delete_all.php
 * Use before extracting a fresh deploy
 */

function deleteAll($dir, $selfScript) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (realpath($path) === $selfScript) continue;
        if (is_dir($path)) {
            deleteAll($path, $selfScript);
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}

$self = realpath(__FILE__);
deleteAll(__DIR__, $self);

// Delete self
unlink($self);

echo '<h2 style="font-family:sans-serif;color:green;">All files deleted.</h2>';
echo '<p style="font-family:sans-serif;">Upload your zip + extract.php and run extraction.</p>';
