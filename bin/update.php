<?php

require_once './vendor/autoload.php';
require_once './src/common.php';

use App\Model\Shop;

$dbUrl = getenv("DATABASE_URL");
$dbConfig = parse_url($dbUrl);

$settings = array(
    'db' => array(
        'driver' => 'pgsql',
        'host' => $dbConfig['host'],
        'database' => ltrim($dbConfig['path'], '/'),
        'username' => $dbConfig['user'],
        'password' => $dbConfig['pass'],
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => ''
    )
);


$app = new Slim\App();
$container = $app->getContainer();
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($settings['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getContainer()->singleton(
    \Illuminate\Contracts\Debug\ExceptionHandler::class,
    \App\CustomException::class
);

$shops = Shop::all();
foreach ($shops as $shop) {
    if (in_array($shop->myshopify_domain, array('school-bus-drivers-unite.myshopify.com'))) {
        error_log("Skipping {$shop->myshopify_domain}");
        continue;
    }
	
    if($shop->myshopify_domain !== 'piper-lou-collection.myshopify.com') {
    	error_log("Skippping {$shop->myshopify_domain}");
	continue;
    }
    $params = array(
        'limit' => 100,
        'page' => 1
    );
    do {
        $res = callShopify($shop, '/admin/products.json', 'GET', $params);
        foreach ($res->products as $product) {
            if ($product->vendor == 'Centex Powder Coating') {
                $params = array(
                    'vendor' => "LDC"
                );
                $res = callShopify($shop, "/admin/products/{$product->id}.json", "PUT", array(
                    'product' => $params
                ));
                error_log(json_encode($res));
                foreach ($product->variants as $variant) {
                    $color = $variant->option2;
                    $newSku = "LDC - T30 - {$color} - {$product->title}";
                    $update = array(
                        'sku' => $newSku
                    );
                    $res = callShopify($shop, "/admin/variants/{$variant->id}.json", "PUT", array(
                        'variant' => $update
                    ));
                }
                error_log("Product {$product->id} finished");
            }
        }
        $params['page']++;
        sleep(0.5);
    } while (count($res->products) == $params['limit']);
}
