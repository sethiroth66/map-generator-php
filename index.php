<?php
include_once(__DIR__ . "/src/GradientGenerator.php");

$sample_sizes = [16,32,64,128,256,512,1024,2048];
$sample_sizes_count = count($sample_sizes);
$seed = 'example';
if ( isset( $_GET['seed'] ) && !empty( $_GET['seed'] ) ){
    $_GET['seed'] = preg_replace("/[^a-z0-9]+/i","_",$_GET['seed']);
    $seed = $_GET['seed'];
}

$gradients = GradientGenerator::STYLES;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Random island map generator.</title>
    <style>
        table, th, td{
            border: 1px solid black;
        }
    </style>
</head>
<body>
<form method="get">
    <label for="seed">Seed: </label>
    <input type="text" id="seed" name="seed" placeholder="Seed" value="<?= $_GET['seed'] ?? '' ?>"/>
    <input type="submit" value="Generate Islands">
</form>

    <br>
<table >
    <thead>
    <tr><th colspan="<?= $sample_sizes_count ?>"><strong>Size Density</strong></th></tr>
    <tr>
        <?php
        foreach ($sample_sizes as $size) {
            $scaled = ($size<1024)?"<br />(Up-scaled to 1024)":"";
            echo "<th>{$size}px {$scaled}</th>";
        }
        ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($gradients as $gradient) { ?>
        <tr>
            <td colspan="<?= $sample_sizes_count ?>" style="text-align: center"><?= ucfirst($gradient) ?> Gradient Filter</td>
        </tr>

    <tr>
        <?php
        foreach ($sample_sizes as $size) {
            $params = http_build_query([
                'seed' => $seed,
                'size' => $size,
                'gradient' => $gradient,
            ]);
            $e_seed = htmlentities($seed);
            $url = "/render_island.php?".$params;
            $alt = "Random island generated for seed '{$e_seed}' at {$size} pixels using '{$gradient}' gradient filter";
            echo <<<HTML
<td style="background: #003db5">
    <label>
        <a href="{$url}" target="_blank">
            <img alt="{$alt}" title="{$alt}" src="{$url}" height='200px' width='200px' />
        </a>
    </label>
</td>
HTML;
        }

        ?>
    </tr>
    <?php } ?>
    </tbody>
</table>

</body>
</html>

