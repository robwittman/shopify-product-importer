<?php

require_once '../src/common.php';

$auth = new StdClass();

$dbUrl = 'pgsql:dbname=d87rvfgh36nanm;host=ec2-54-243-38-139.compute-1.amazonaws.com;port=5432';
$pdo = new PDO($dbUrl, 'hrnwqcuynpdhzz', 'd0efbf30bd92da97f84a83b8195fbae44b8d3dae030ba3509508979f1e949491');
$stmt = $pdo->prepare("SELECT * FROM shops WHERE myshopify_domain = ? LIMIT 1");
if ($stmt->execute(array($argv[1].'.myshopify.com')) === false) {
    die("Query failed");
}

$shop = $stmt->fetchObject();
if (!$shop) {
    exit("Shop with domain {$argv[1]}.myshopify.com does not exist\n");
}
$params = array(
    'limit' => 250,
    'page' => 1,
    'vendor' => 'Canvus Print'
);

do {
    $res = callShopify($shop, '/admin/products.json', 'GET', $params);
    $products = $res->products;
    $res = null;
    $count = count($products);

    foreach ($products as $product) {
        $sizePosition = 0;
        foreach ($product->options as $option) {
            if ($option->name == 'Size') {
                $sizePosition = $option->position;
            }
        }
        if (!$sizePosition) {
            error_log("'Canvus Print' product does not have a size option...");
            continue;
        }
        $sizeOption = 'option'.$sizePosition;
        $changes = 0;
        foreach ($product->variants as $variant) {
            $size = $variant->{$sizeOption};
            if (!$size) {
                error_log("Variant does not have a size option...");
                continue;
            }
            if (!in_array($size, [
                'Small',
                'Medium',
                'Large'
            ])) {
                error_log("Variant is not small, medium, or large. Skipping...");
                continue;
            }
            if ($size == 'Small') {
                $newSize = 'S';
            } elseif ($size == 'Medium') {
                $newSize = 'M';
            } else {
                $newSize = 'L';
            }
            $changes++;
            $update = callShopify($shop, '/admin/variants/'.$variant->id.'.json', 'PUT', array(
                'variant' => array(
                    $sizeOption => $newSize
                )
            ));
        }
        // if ($changes) {
        //     error_log("Product {$product->id} completed. Please double check now...");
        //     exit;
        // }
    }
    $products = null;
    $params['page']++;
} while ($count == $params['limit']);

error_log($argv[1].'.myshopify.com has been completed');
