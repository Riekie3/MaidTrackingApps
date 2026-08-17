<?php
// One-off icon generator (run once, commit the output PNGs, delete or
// ignore this script's need to re-run). Simple geometric house mark —
// full-bleed square fill so it works as a maskable PWA icon, in the
// primary pastel blue from assets/css/app.css.

$primary = [74, 109, 156]; // #4A6D9C
$white = [255, 255, 255];

function make_icon(int $n, array $bg, array $fg, string $path): void
{
    $img = imagecreatetruecolor($n, $n);
    imagesavealpha($img, true);
    $bgColor = imagecolorallocate($img, $bg[0], $bg[1], $bg[2]);
    $fgColor = imagecolorallocate($img, $fg[0], $fg[1], $fg[2]);
    imagefill($img, 0, 0, $bgColor);

    // House glyph, centered, sized for a safe zone within ~66% of canvas.
    $roofW = $n * 0.50; $roofH = $n * 0.22;
    $bodyW = $n * 0.40; $bodyH = $n * 0.28;
    $cx = $n / 2;
    $bodyTop = $n * 0.50;
    $bodyBottom = $bodyTop + $bodyH;

    // Body (rounded via simple rect — small scale hides sharp corners)
    imagefilledrectangle($img, (int)($cx - $bodyW/2), (int)$bodyTop, (int)($cx + $bodyW/2), (int)$bodyBottom, $fgColor);

    // Roof (triangle)
    $roofTop = $bodyTop - $roofH;
    $points = [
        (int)$cx, (int)$roofTop,
        (int)($cx - $roofW/2), (int)$bodyTop,
        (int)($cx + $roofW/2), (int)$bodyTop,
    ];
    imagefilledpolygon($img, $points, 3, $fgColor);

    // Door cutout (background color, small rect near bottom center)
    $doorW = $n * 0.10; $doorH = $n * 0.14;
    imagefilledrectangle($img, (int)($cx - $doorW/2), (int)($bodyBottom - $doorH), (int)($cx + $doorW/2), (int)$bodyBottom, $bgColor);

    imagepng($img, $path);
    imagedestroy($img);
}

$dir = __DIR__ . '/../assets/icons';
if (!is_dir($dir)) mkdir($dir, 0755, true);

make_icon(512, $primary, $white, "$dir/icon-512.png");
make_icon(192, $primary, $white, "$dir/icon-192.png");
make_icon(180, $primary, $white, "$dir/apple-touch-icon.png");
make_icon(32, $primary, $white, "$dir/favicon-32.png");

echo "Icons generated in $dir\n";
