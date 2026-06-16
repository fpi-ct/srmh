<?php

if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required\n");
    exit(1);
}

@mkdir(__DIR__.'/public/icons', 0777, true);

function makeIcon(int $size, string $path): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    $indigo = imagecolorallocate($img, 79, 70, 229);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefilledellipse($img, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.88), (int) ($size * 0.88), $indigo);
    imagestring($img, 5, (int) ($size * 0.28), (int) ($size * 0.38), 'S', $white);
    imagepng($img, $path);
    imagedestroy($img);
}

makeIcon(192, __DIR__.'/public/icons/icon-192.png');
makeIcon(512, __DIR__.'/public/icons/icon-512.png');
makeIcon(72, __DIR__.'/public/icons/badge-72.png');

echo "icons created\n";
