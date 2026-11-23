<?php
$im = imagecreatetruecolor(400, 300);
$bg = imagecolorallocate($im, 240, 248, 255);
$blue = imagecolorallocate($im, 79, 70, 229);
$dark = imagecolorallocate($im, 30, 41, 59);
$gray = imagecolorallocate($im, 100, 116, 139);
$white = imagecolorallocate($im, 255, 255, 255);

imagefill($im, 0, 0, $bg);
imagefilledrectangle($im, 0, 0, 400, 50, $blue);
imagestring($im, 5, 100, 15, 'CALCULUS WORKSHEET', $white);
imagestring($im, 4, 20, 70, 'Problem 1: Find dy/dx of y = x^3 + 2x', $dark);
imagestring($im, 4, 20, 100, 'Problem 2: Evaluate integral of sin(x)dx', $dark);
imagestring($im, 4, 20, 130, 'Problem 3: Compute lim x->0 sin(x)/x', $dark);
imagestring($im, 3, 20, 170, 'Student: Juan Dela Cruz', $gray);
imagestring($im, 3, 20, 190, 'Date: November 23, 2026', $gray);
imagestring($im, 3, 20, 220, 'Score: 28/30 - Excellent!', $dark);

imagepng($im, __DIR__ . '/uploads/sample_calculus.png');
imagedestroy($im);
echo "Image created!\n";
?>
