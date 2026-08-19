<?php
// Icon generator: builds TWO separate icon sets from two different
// sources, per the project owner's explicit instruction —
//   - Website/PWA icons (favicon, apple-touch-icon, manifest icon-192/
//     512, used by the browser tab and "Add to Home Screen"): generated
//     from assets/emoji-source.png, a 1024x1024 render of the 🧹 emoji
//     (Twemoji artwork, CC-BY 4.0) on the app's primary brand colour.
//   - Android APK launcher icon (assets/icons/apk-icon-*.png — NOT
//     referenced by manifest.json, only by scripts/build_twa.js when
//     scaffolding the Android project): generated from the hand-provided
//     assets/logo-source.png (a small, slightly-cropped screenshot —
//     cropped to the real circular badge, masked to a transparent
//     circle, upscaled with a sharpen pass to counter upscale softness).
// Re-run whenever either source image changes; commit the generated PNGs.

$srcDir = __DIR__ . '/../assets';
$dir = __DIR__ . '/../assets/icons';
if (!is_dir($dir)) mkdir($dir, 0755, true);

function make_size(\GdImage $master, int $masterSize, int $n, string $path): void
{
    $out = imagecreatetruecolor($n, $n);
    imagesavealpha($out, true);
    imagealphablending($out, false);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $master, 0, 0, 0, 0, $n, $n, $masterSize, $masterSize);
    imagepng($out, $path);
    imagedestroy($out);
}

function sharpen(\GdImage $img): void
{
    $kernel = [
        [0, -0.15, 0],
        [-0.15, 1.6, -0.15],
        [0, -0.15, 0],
    ];
    imageconvolution($img, $kernel, array_sum(array_map('array_sum', $kernel)), 0);
}

// ---------- 1. Website/PWA icons, from the emoji render ----------

$emojiMaster = imagecreatefrompng("$srcDir/emoji-source.png");
if (!$emojiMaster) {
    fwrite(STDERR, "Could not read $srcDir/emoji-source.png\n");
    exit(1);
}
$emojiSize = imagesx($emojiMaster); // already 1024x1024, full-bleed, no cropping needed

imagepng($emojiMaster, "$dir/logo-master.png");
make_size($emojiMaster, $emojiSize, 512, "$dir/icon-512.png");
make_size($emojiMaster, $emojiSize, 192, "$dir/icon-192.png");
make_size($emojiMaster, $emojiSize, 180, "$dir/apple-touch-icon.png");
make_size($emojiMaster, $emojiSize, 32, "$dir/favicon-32.png");
make_size($emojiMaster, $emojiSize, 16, "$dir/favicon-16.png");
imagedestroy($emojiMaster);
echo "Website/PWA icons generated in $dir (from emoji-source.png)\n";

// ---------- 2. Android APK launcher icon, from the real logo ----------

$src = imagecreatefrompng("$srcDir/logo-source.png");
if (!$src) {
    fwrite(STDERR, "Could not read $srcDir/logo-source.png\n");
    exit(1);
}

// Crop out the stray partial badge visible on the left edge of the
// source screenshot -- bounding box of the real circular badge, found
// by scanning for non-near-black content starting right of the sliver.
$cropX = 11; $cropY = 2; $cropSize = 155; // clean square, badge only
$crop = imagecreatetruecolor($cropSize, $cropSize);
imagesavealpha($crop, true);
imagealphablending($crop, false);
imagefill($crop, 0, 0, imagecolorallocatealpha($crop, 0, 0, 0, 127));
imagealphablending($crop, true);
imagecopy($crop, $src, 0, 0, $cropX, $cropY, $cropSize, $cropSize);
imagedestroy($src);

// Mask out the black backing square so only the circular badge remains,
// on a transparent canvas (lets Android/iOS apply their own icon mask
// shape cleanly instead of showing black corners). A geometric circular
// mask (the badge itself is a circle) rather than color-keying near-black
// pixels -- the source is a compressed screenshot, so its background
// isn't uniformly near-black right up to the edge and color-keying left
// a speckled halo.
imagealphablending($crop, false);
$cx = $cropSize / 2; $cy = $cropSize / 2; $radius = $cropSize / 2 - 1;
$feather = 1.5;
for ($y = 0; $y < $cropSize; $y++) {
    for ($x = 0; $x < $cropSize; $x++) {
        $dist = sqrt(($x + 0.5 - $cx) ** 2 + ($y + 0.5 - $cy) ** 2);
        if ($dist > $radius + $feather) {
            imagesetpixel($crop, $x, $y, imagecolorallocatealpha($crop, 0, 0, 0, 127));
        } elseif ($dist > $radius - $feather) {
            $coverage = 1 - (($dist - ($radius - $feather)) / (2 * $feather));
            $coverage = max(0, min(1, $coverage));
            $rgb = imagecolorat($crop, $x, $y);
            $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
            $alpha = (int) round(127 * (1 - $coverage));
            imagesetpixel($crop, $x, $y, imagecolorallocatealpha($crop, $r, $g, $b, $alpha));
        }
    }
}
imagealphablending($crop, true);

// Upscale to a hi-res master (1024px) with smooth resampling, then a
// light unsharp pass to counter the softness of a ~6.6x upscale.
$masterSize = 1024;
$master = imagecreatetruecolor($masterSize, $masterSize);
imagesavealpha($master, true);
imagealphablending($master, false);
imagefill($master, 0, 0, imagecolorallocatealpha($master, 0, 0, 0, 127));
imagecopyresampled($master, $crop, 0, 0, 0, 0, $masterSize, $masterSize, $cropSize, $cropSize);
imagedestroy($crop);

sharpen($master);
imagepng($master, "$dir/apk-logo-master.png");
make_size($master, $masterSize, 512, "$dir/apk-icon-512.png");
make_size($master, $masterSize, 192, "$dir/apk-icon-192.png");
imagedestroy($master);

echo "APK launcher icon generated in $dir (from logo-source.png)\n";
