<?php

require_once '../src/common.php';


$auth = new StdClass();

$dbUrl = 'pgsql:dbname=d87rvfgh36nanm;host=ec2-54-243-38-139.compute-1.amazonaws.com;port=5432';

$pdo = new PDO($dbUrl, 'hrnwqcuynpdhzz', 'd0efbf30bd92da97f84a83b8195fbae44b8d3dae030ba3509508979f1e949491');
$stmt = $pdo->prepare("SELECT * FROM shops WHERE myshopify_domain = ? LIMIT 1");
if ($stmt->execute(array($argv[1].'.myshopify.com')) === false) {
    die("Query failed");
}

$shop = $stmt->fetch();
if (!$shop) {
    exit("Shop with domain {$argv[1]}.myshopify.com does not exist\n");
}
$params = array(
    'limit' => 250,
    'page' => 1,
    'vendor' => 'BPP'
);

do {
    $res = callShopify((object) $shop, '/admin/products.json', 'GET', $params);
    $products = $res->products;
    $res = null;
    $count = count($products);
    foreach ($products as $product) {
        if ($product->vendor == 'BPP') {
            error_log("Current Vendor:: ".$product->vendor);
            $update = callShopify((object) $shop, '/admin/products/'.$product->id.'.json', 'PUT', array(
                'product' => array(
                    'vendor' => 'Canvus Print'
                )
            ));
            error_log("New Product vendor: ".$update->product->vendor);
            $update = null;
        }
    }
    $products = null;
    $params['page']++;
} while($count == $params['limit']);
