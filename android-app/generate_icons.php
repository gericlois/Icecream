<?php
// Generate Android mipmap icons from the JMC Foodies icon
$source = __DIR__ . '/../assetsimg/icon/jmc_icon.jpg';
$sizes = [
    'mdpi' => 48,
    'hdpi' => 72,
    'xhdpi' => 96,
    'xxhdpi' => 144,
    'xxxhdpi' => 192,
];

$src = imagecreatefromjpeg($source);

foreach ($sizes as $density => $size) {
    $dest = imagecreatetruecolor($size, $size);
    imagecopyresampled($dest, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
    $path = __DIR__ . "/app/src/main/res/mipmap-{$density}/ic_launcher.png";
    imagepng($dest, $path);
    imagedestroy($dest);
    echo "Created {$density}: {$size}x{$size}\n";
}

imagedestroy($src);
echo "Done!\n";
