<?php

include_once(__DIR__ . "/vendor/autoload.php");
include_once(__DIR__ . "/src/GradientGenerator.php");
include_once(__DIR__ . "/src/IslandGenerator.php");

ini_set('display_errors',0);

$size = 256; // default map size
if (isset($_GET['size']) && !empty($_GET['size']) && is_numeric($_GET['size'])) {
    $size = (int) $_GET['size'];
    $size = min(max($size, 16), 2048);
}

$Island = new IslandGenerator($size);

if (isset($_GET['seed']) && !empty($_GET['seed'])) {
    $_GET['seed'] = preg_replace("/[^a-z0-9]+/i", "_", $_GET['seed']);
    $Island->setSeed($_GET['seed']);
}

if (isset($_GET['gradient']) && !empty($_GET['gradient'])) {
    if (!in_array(strtolower($_GET['gradient']), \GradientGenerator::STYLES)) {
        $_GET['gradient'] = GradientGenerator::STYLE_DEFAULT;
    }
    $Island->setGradientStyle($_GET['gradient']);
}

$Island->setDoUpscale(1024);

$fname     = [
    $Island->getSeed(),
    $Island->getGradientStyle(),
    $Island->getSize(),
];
$file_name = implode("_", $fname) . ".png";
$file_name = urlencode($file_name);
$file_path = "/islands/" . $file_name;

header("Content-Type: image/png");
header("Content-Disposition: filename={$file_name}");
if (file_exists(__DIR__ . $file_path) && is_file(__DIR__ . $file_path)) {
    readfile(__DIR__ . $file_path);
    die();
}

$Island->render();
imagepng($Island->getImage(), __DIR__ . $file_path);
imagepng($Island->getImage());
imagedestroy($Island->getImage());
die();
