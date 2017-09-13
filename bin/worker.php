<?php
define("DIR", dirname(dirname(__FILE__)));
require_once DIR.'/vendor/autoload.php';
require_once DIR.'/src/common.php';

use App\Model\Queue;

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

$credentials = new \Aws\Credentials\Credentials(getenv("AWS_ACCESS_KEY"),getenv("AWS_ACCESS_SECRET"));
$s3 = new \Aws\S3\S3Client([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => $credentials
]);

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

while (true) {
    $queue = Queue::where('status', Queue::PENDING)->get();
    // $queue = Queue::get();
    foreach ($queue as $q) {
        try {
            // $q->start();
            $data = json_decode($q->data, true);
            switch ($data['post']['template']) {
                case 'hats':
                    $res = createHats($q);
                    break;
                case 'stemless':
                    $res = createStemless($q);
                    break;
                case 'single_product':
                    $res = processQueue($q);
                    break;
                case 'drinkware':
                    $res = createDrinkware($q);
                    break;
                case 'uv_drinkware':
                    $res = createUvDrinkware($q);
                    break;
                case 'flasks':
                    $res = createFlasks($q);
                    break;
                case 'baby_body_suit':
                    $res = createBabyBodySuit($q);
                    break;
                case 'raglans':
                    $res = createRaglans($q);
                    break;
                case 'front_back_pocket':
                    $res = createFrontBackPocket($q);
                    break;
                case 'uv_with_bottles':
                    $res = createUvWithBottles($q);
                    break;
                case 'christmas':
                    $res = createChristmas($q);
                    break;
                case 'hats_masculine':
                    $res = createMasculineHats($q);
                    break;
                default:
                    throw new \Exception("Invalid template {$data['post']['template']} provided");
            }
            $q->finish($res);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            // exit($e->getMessage());
            $q->fail($e->getMessage());
        }
    }

    sleep(10);
}

function getImages($s3, $prefix) {
    $objects = $s3->getIterator('ListObjects', array(
        "Bucket" => "shopify-product-importer",
        "Prefix" => $prefix
    ));
    $res = array();
    foreach ($objects as $object) {
        $key = $object["Key"];
        if (strpos($key, "MACOSX") || strpos($key, "Icon^M")) {
            continue;
        }
        if (!in_array(pathinfo($key, PATHINFO_EXTENSION), array('jpg', 'png', 'jpeg'))) {
            continue;
        }
        $res[] = $object;
    }
    return array_map(function($object) {
        return $object["Key"];
    }, $res);
}

function createMasculineHats($queue)
{
    $price = '29.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.50';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' />
    <h5>Shipping &amp; Return Policy</h5>
    <p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
    <ul>
    <li>Hassle free return/exchange policy! </li>
    <li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
    </ul>
    <h5>Trucker Hat</h5>
    <p>You are going to <strong>LOVE </strong>our Trucker hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton front panel and visor </li>
    <li>100% nylon mesh back panel </li>
    <li>6-panel, structured, mid-profile </li>
    <li>Pigment-dyed front panels </li>
    <li>Traditional tan nylon mesh back panels </li>
    <li>Distressed torn visor, cotton twill sweatband </li>
    <li>Plastic tab back closure;Cool-Crown mesh lining</li>
    </ul>
    <h5>Cotton Twill Hat</h5>
    <p>You are going to <strong>LOVE</strong><span> </span>our Cotton Twill hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton twill </li>
    <li>Garment washed, pigment dyed</li>
    <li>Six panel, unstructured, low profile </li>
    <li>Tuck-away leather strap, antique brass buckle </li>
    <li>Adams exclusive Cool Crown Mesh Lining </li>
    <li>Four rows of stitching on self-fabric sweatband</li>
    <li>Sewn eyelets</li>
    <li>One Size Fits All </li>
    </ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = preg_replace('%([a-z])([A-Z])%', '\1-\2', $specs[1]);
        $imageUrls[trim($color, '_')] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'hat';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Edge Promotions',
        'product_type' => 'hat',
        'options' => array(
            array(
                'name' => "Style"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );
    $store_name = '';
    switch ($shop->myshopify_domain) {
        case 'piper-lou-collection.myshopify.com':
        case 'plcwholesale.myshopify.com':
            $store_name = 'Piper Lou - ';
            break;
    }
    foreach ($imageUrls as $color => $image) {
        $variantData = array(
            'title' => "Trucker Hat / ".$color,
            'price' => $price,
            'option1' => "Trucker Hat",
            'option2' => str_replace('_', ' ', $color),
            'weight' => '5.0',
            'weight_unit' => 'oz',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny',
            'sku' => "{$store_name}Hat"
        );
        if ($color == 'Navy' && $style == 'Hat') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $color = str_replace(' ', '_', $variant->option2);
        error_log($color);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => [$variant->id]
        );
        error_log($image['src']);
        $imageUpdate[] = $image;
    };
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createFrontBackPocket($queue)
{
    $prices = array(
        'Tee' => array(
            'small' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '16',
                'weight' => '0.0',
            )
        ),
        'Long Sleeve' => array(
            'small' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '18',
                'weight' => '0.0',
            )
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    error_log($data['file']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    $html = '';
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'PLCWholesale',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        ),
        'variants' => array(),
        'images' => array()
    );
    foreach ($prices as $style => $sizes) {
        foreach ($sizes as $size => $options) {
            foreach ($imageUrls as $color => $url) {
                $color = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / ' . $style,
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'option3' => $style,
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny',
                    'sku' => ""
                );
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    $variantMap = array();

    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        if (!isset($variantMap[$color])) {
            $variantMap[$color] = array();
        }
        $variantMap[$color][] = $variant->id;
    }
    foreach ($variantMap as $color => $ids) {
        $data = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$color],
            'variant_ids' => $ids
        );
        $imageUpdate[] = $data;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);

}

function createChristmas($queue)
{
    $variants = array(
        'Hoodie' => array(
            'Small' => array('price' => '32.99', 'weight' => '16.1'),
            'Medium' => array('price' => '32.99', 'weight' => '17.5'),
            'Large' => array('price' => '32.99', 'weight' => '18.8'),
            'XL' => array('price' => '32.99', 'weight' => '21.2'),
            '2XL' => array('price' => '34.99', 'weight' => '22.9'),
            '3XL' => array('price' => '36.99', 'weight' => '24.1'),
            '4XL' => array('price' => '36.99', 'weight' => '24.5')
        ),
        'Long Sleeve' => array(
            'Small' => array('price' => '24.99', 'weight' => '7.6'),
            'Medium' => array('price' => '24.99', 'weight' => '8.8'),
            'Large' => array('price' => '24.99', 'weight' => '10.0'),
            'XL' => array('price' => '24.99', 'weight' => '10.3'),
            '2XL' => array('price' => '26.99', 'weight' => '12.4'),
            '3XL' => array('price' => '26.99', 'weight' => '12.6'),
            '4XL' => array('price' => '26.99', 'weight' => '13.6')
        ),
        'Tee' => array(
            'Small' => array('price' => '22.99', 'weight' => '5.6'),
            'Medium' => array('price' => '22.99', 'weight' => '6.3'),
            'Large' => array('price' => '22.99', 'weight' => '7.2'),
            'XL' => array('price' => '22.99', 'weight' => '8.0'),
            '2XL' => array('price' => '24.99', 'weight' => '8.7'),
            '3XL' => array('price' => '26.99', 'weight' => '9.8'),
            '4XL' => array('price' => '29.99', 'weight' => '10.2')
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $variants = array(
                'Hoodie' => array(
                    'Small' => array('price' => '20.00', 'weight' => '16.1'),
                    'Medium' => array('price' => '20.00', 'weight' => '17.5'),
                    'Large' => array('price' => '20.00', 'weight' => '18.8'),
                    'XL' => array('price' => '20.00', 'weight' => '21.2'),
                    '2XL' => array('price' => '22.00', 'weight' => '22.9'),
                    '3XL' => array('price' => '24.00', 'weight' => '24.1'),
                    '4XL' => array('price' => '26.00', 'weight' => '24.5')
                ),
                'Long Sleeve' => array(
                    'Small' => array('price' => '12.50', 'weight' => '7.6'),
                    'Medium' => array('price' => '12.50', 'weight' => '8.8'),
                    'Large' => array('price' => '12.50', 'weight' => '10.0'),
                    'XL' => array('price' => '12.50', 'weight' => '10.3'),
                    '2XL' => array('price' => '14.50', 'weight' => '12.4'),
                    '3XL' => array('price' => '16.50', 'weight' => '12.6'),
                    '4XL' => array('price' => '18.50', 'weight' => '13.6')
                ),
                'Tee' => array(
                    'Small' => array('price' => '11', 'weight' => '5.6'),
                    'Medium' => array('price' => '11', 'weight' => '6.3'),
                    'Large' => array('price' => '11', 'weight' => '7.2'),
                    'XL' => array('price' => '11', 'weight' => '8.0'),
                    '2XL' => array('price' => '13', 'weight' => '8.7'),
                    '3XL' => array('price' => '13', 'weight' => '9.8'),
                    '4XL' => array('price' => '13', 'weight' => '10.2')
                )
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
                    <h5>Shipping &amp; Returns</h5>
                    <p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
                    <ul>
                    <li>Hassle free return/exchange policy! </li>
                    <li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
                    </ul>
                    <h5>Product Description</h5>
                    <p><span>You are going to <strong>LOVE</strong> this design! We offer apparel in Short Sleeve shirts, Long Sleeve Shirts, Tank tops, and Hoodies. If you want information on sizing, please view the sizing chart below. </span></p>
                    <p><span>Apparel is designed, printed, and shipped in the USA. 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 </span></p>
                    <p><a href='https://www.piperloucollection.com/pages/sizing-chart'>View our sizing chart</a></p>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $style = $specs[0];
        $color = $specs[1];
        $imageUrls[$style][$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'christmas';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'BPP',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($variants as $style => $sizes) {
        foreach ($sizes as $size => $options) {
            foreach (['Green', 'Red'] as $color) {
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / ' . $style,
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'option3' => $style,
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny',
                    'sku' => $style . ' - ' . $color . ' - ' . $size
                );
                $product_data['variants'][] = $variantData;
            }
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    $variantMap = array(
        'Red' => array(
            'Hoodie' => array(),
            'Long Sleeve' => array(),
            'Tee' => array()
        ),
        'Green' => array(
            'Hoodie' => array(),
            'Long Sleeve' => array(),
            'Tee' => array()
        )
    );
    foreach ($res->product->variants as $variant) {
        $style = $variant->option3;
        $color = $variant->option2;
        $variantMap[$color][$style][] = $variant->id;
    }
    foreach ($variantMap as $color => $styles) {
        foreach ($styles as $style => $ids) {
            $imageStyle = ($style == 'Long Sleeve') ? 'LS' : $style;
            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$imageStyle][$color],
                'variant_ids' => $ids
            );
            $imageUpdate[] = $data;
        }
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createUvDrinkware($queue)
{
    $prices = array(
        '30' => '39.99',
        '20' => '34.99'
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                '30' => '20.00',
                '20' => '17.50'
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
                    <ul>
                    <li>2x heat &amp; cold retention (compared to plastic tumblers).</li>
                    <li>Double-walled vacuum insulation - Keeps Hot and Cold. </li>
                    <li>Fits most cup holders, Clear lid to protect from spills. </li>
                    <li>Sweat Free Design allows for a Strong Hold. </li>
                    <li>These tumblers will ship separately from our distributor in Texas. </li>
                    </ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $size = $specs[0];
        $color = $specs[1];
        $imageUrls[$size][$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Tx Tumbler',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $size => $colors) {
        foreach ($colors as $color => $url) {
            switch ($size) {
                case '30':
                    $option1 = '30oz Tumbler';
                    $sku = "TX (UV PRINTED) - T30 - {$color} - Coated 30oz Tumbler";
                    break;
                case '20':
                    $option1 = '20oz Tumbler';
                    $sku = "TX (UV PRINTED) - T20 - {$color} - Coated 20oz Tumbler";
                    break;
            }
            $variantData = array(
                'title' => $option1. ' / '.$color,
                'price' => $prices[$size],
                'option1' => $option1,
                'option2' => str_replace('_', ' ', $color),
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => $sku
            );
            if ($color == 'Navy' && $size == '30') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        switch ($size) {
            case '30oz Tumbler':
                $size = '30';
                break;
            case '20oz Tumbler':
                $size = '20';
                break;
        }
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createFlasks($queue)
{
    $price = '19.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.00';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<div>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</div>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this awesome flask! Perfect addition for anybody who needs a quick drink on-the-go. Fill it with whatever you like, we won't judge....we'll encourage! </p>
<ul>
<li>Stainless Steel 6oz Flask</li>
<li>Heavy Duty but Light-Weight. Will not easily scratch or tarnish </li>
<li>Screw down cap ensures no liquid will escape the flask. Cap is attached to flask. </li>
<li>Reliable method of storing beverages for personal use. </li>
<li>Perfect gift for yourself or someone else.</li>
</ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'flask';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Tx Tumbler',
        'options' => array(
            array(
                'name' => "Szie"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $color => $url) {
        $variantData = array(
            'title' => '6oz / '.$color,
            'price' => $price,
            'option1' => '6oz',
            'option2' => $color,
            'weight' => '1.1',
            'weight_unit' => 'lb',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny',
            'sku' => '6oz - Flask - '.$color
        );
        if ($color == 'Blue') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));

    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = $variant->option2;
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createBabyBodySuit($queue)
{
    $price = '14.99';
    $sizes = array(
        'Newborn',
        '6 Months',
        '12 Months',
        '18 Months',
        '24 Months'
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '8.50';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<div>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</div>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this baby body suit! Perfect addition for to your baby's wardrobe. These are guaranteed to get hilarious and cute reactions from anybody that sees them! </p>
<ul>
<li>5.0 oz. 100% combed ring spun cotton</li>
<li>Sewn with 100% cotton thread</li>
<li>Flatlock seams</li>
<li>Double-needle rib binding on neck, shoulders,sleeves and leg openings</li>
<li>Reinforced three-snap closure</li>
</ul>
<p> </p>
<p> </p>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $imageUrls[] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'Body Suit';
    $tags[] = 'baby';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Piper Lou Collection',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color",

            )
        ),
        'variants' => array(),
        'images' => array()
    );
    foreach ($sizes as $size) {
        $imageUrl = $imageUrls[0];
        $variantData = array(
            'title' => $size .' / White',
            'price' => $price,
            'option1' => $size,
            'option2' => 'White',
            'weight' => '0.6',
            'weight_unit' => 'lb',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny',
            'sku' => 'Piper Lou - Baby Body Suit - White - '.$size
        );
        $product_data['variants'][] = $variantData;
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantIds = array();
    foreach ($res->product->variants as $variant) {
        $variantIds[] = $variant->id;
    }
    error_log($imageUrls[0]);
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => array(
                array(
                    'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[0],
                    'variant_ids' => $variantIds
                )
            )
        )
    ));
    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createRaglans($queue)
{
    $prices = array(
        'Small' => array(
            'price' => '24.99',
            'weight' => '7.6',
        ),
        'Medium' => array(
            'price' => '24.99',
            'weight' => '8.8',
        ),
        'Large' => array(
            'price' => '24.99',
            'weight' => '10.0',
        ),
        'XL' => array(
            'price' => '24.99',
            'weight' => '10.3',
        ),
        '2XL' => array(
            'price' => '27.99',
            'weight' => '12.4',
        ),
        '3XL' => array(
            'price' => '27.99',
            'weight' => '13.2',
        ),
        '4XL' => array(
            'price' => '29.99',
            'weight' => '14.0',
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                'Small' => array(
                    'price' => '12.50',
                    'weight' => '7.6',
                ),
                'Medium' => array(
                    'price' => '12.50',
                    'weight' => '8.8',
                ),
                'Large' => array(
                    'price' => '12.50',
                    'weight' => '10.0',
                ),
                'XL' => array(
                    'price' => '12.50',
                    'weight' => '10.3',
                ),
                '2XL' => array(
                    'price' => '12.50',
                    'weight' => '12.4',
                ),
                '3XL' => array(
                    'price' => '12.50',
                    'weight' => '13.2',
                ),
                '4XL' => array(
                    'price' => '14.50',
                    'weight' => '14.0',
                )
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' /><meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p><span>You are going to <strong>LOVE</strong> this design! We offer apparel in Short Sleeve shirts, Long Sleeve Shirts, Tank tops, and Hoodies. If you want information on sizing, please view the sizing chart below. </span></p>
<p><span>Apparel is designed, printed, and shipped in the USA. 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 </span></p>
<p><a href='https://www.piperloucollection.com/pages/sizing-chart'>View our sizing chart</a></p>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags[] = '3/4 sleeve raglan';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'BPP',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $color => $url) {
        $color = str_replace('_', ' ', $color);
        foreach ($prices as $size => $options) {
            $variantData = array(
                'title' => $size . ' / ' . $color . ' / Raglan 3/4 Sleeve',
                'price' => $options['price'],
                'option1' => $size,
                'option2' => $color,
                'option3' => 'Raglan 3/4 Sleeve',
                'weight' => $options['weight'],
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => "3/4 Sleeve Raglan - {$color} - {$size}"
            );
            if ($color == 'Navy' && $size == '30') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);

}

function createDrinkware($queue)
{
    $prices = array(
        '30' => '29.99',
        '20' => '24.99',
        'Bottle' => '29.99',
        'SmallBottle' => '26.99'
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                '30' => '17',
                '20' => '16',
                'Bottle' => '17.50',
                'SmallBottle' => '13.50'
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this awesome drink ware! Perfect addition for anybody who needs a cold/warm drink on the go. </p>
<ul>
<li>Tumblers available in 30oz and 20oz, comes with lid</li>
<li>Water Bottles available in 40oz and 16oz, comes with twist caps</li>
<li>Vacuum sealed lid insulates cold drinks for 24 hours and hot drinks for 12 hours. Double wall feature eliminates condensation and retains temperature</li>
<li>Stainless steel with a powder coat finish provides maximum durability against damages</li>
<li>Narrow mouth opening is perfect to drink from without spilling and narrow bottom fits standard cupholders. </li>
<li>Hand wash only, do not put in dishwasher</li>
</ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $size = $specs[0];
        $color = $specs[1];
        $imageUrls[$size][$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags[] = 'drinkware';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Tx Tumbler',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $size => $colors) {
        foreach ($colors as $color => $url) {
            $sku = $color;
            if ($color == 'Cyan') {
                $sku = 'Seafoam';
            }
            switch ($size) {
                case '30':
                    $option1 = '30oz Tumbler';
                    $sku = "TX - T30 - {$sku} - Coated 30oz Tumbler";
                    break;
                case '20':
                    $option1 = '20oz Tumbler';
                    $sku = "TX - T20 - {$sku} - Coated 20oz Tumbler";
                    break;
                case 'Bottle':
                    $option1 = '40oz Water Bottle';
                    $sku = "TX - T40 - {$sku} - Coated 40oz Water Bottle";
                    break;
                case 'SmallBottle':
                    $option1 = '16oz Water Bottle';
                    $sku = "TX - T16 - {$sku} - Coated 16oz Water Bottle";
                    break;
            }
            $variantData = array(
                'title' => $option1. ' / '.$color,
                'price' => $prices[$size],
                'option1' => $option1,
                'option2' => $color,
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => $sku
            );
            if ($color == 'Navy' && $size == '30') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = $variant->option2;
        switch ($size) {
            case '30oz Tumbler':
                $size = '30';
                break;
            case '20oz Tumbler':
                $size = '20';
                break;
            case '40oz Water Bottle':
                $size = 'Bottle';
                break;
            case '16oz Water Bottle':
                $size = 'SmallBottle';
                break;
        }
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createStemless($queue) {
    $price = '24.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.50';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<div>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</div>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this stemless wine glass! Perfect addition for to your wine drinking collection! Comes in tons of cute colors and is a must have. </p>
<ul>
<li>9 oz. drink capacity</li>
<li>Double-walled, vacuum insulated</li>
<li>Keeps beverages cold for 24 hours, hot for 12 hours</li>
<li>Comes with lid </li>
<li>Stainless steel exterior</li>
<li>Hand wash Only</li>
</ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags[] = 'wine cup';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Tx Tumbler',
        'options' => array(
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );
    $store_name = '';
    switch ($shop->myshopify_domain) {
        case 'piper-lou-collection.myshopify.com':
        case 'plcwholesale.myshopify.com':
            $store_name = 'Piper Lou - ';
            break;
    }
    foreach ($imageUrls as $color => $url) {
        $sku = $color;
        if ($color == 'Grey') {
            $sku = 'Stainless Steel';
        }
        $variantData = array(
            'title' => $color,
            'price' => $price,
            'option1' => $color,
            'weight' => '0.1',
            'weight_unit' => 'oz',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny',
            'sku' => 'Stemless Wine Cup - '.$sku
        );
        if ($color == 'Black') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $color = $variant->option1;
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function createHats($queue) {
    $price = '29.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.50';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' />
    <h5>Shipping &amp; Return Policy</h5>
    <p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
    <ul>
    <li>Hassle free return/exchange policy! </li>
    <li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
    </ul>
    <h5>Trucker Hat</h5>
    <p>You are going to <strong>LOVE </strong>our Trucker hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton front panel and visor </li>
    <li>100% nylon mesh back panel </li>
    <li>6-panel, structured, mid-profile </li>
    <li>Pigment-dyed front panels </li>
    <li>Traditional tan nylon mesh back panels </li>
    <li>Distressed torn visor, cotton twill sweatband </li>
    <li>Plastic tab back closure;Cool-Crown mesh lining</li>
    </ul>
    <h5>Cotton Twill Hat</h5>
    <p>You are going to <strong>LOVE</strong><span> </span>our Cotton Twill hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton twill </li>
    <li>Garment washed, pigment dyed</li>
    <li>Six panel, unstructured, low profile </li>
    <li>Tuck-away leather strap, antique brass buckle </li>
    <li>Adams exclusive Cool Crown Mesh Lining </li>
    <li>Four rows of stitching on self-fabric sweatband</li>
    <li>Sewn eyelets</li>
    <li>One Size Fits All </li>
    </ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $style = $specs[0];
        $color = $specs[1];
        $imageUrls[$style][$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'hat';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Edge Promotions',
        'product_type' => 'hat',
        'options' => array(
            array(
                'name' => "Style"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );
    $store_name = '';
    switch ($shop->myshopify_domain) {
        case 'piper-lou-collection.myshopify.com':
        case 'plcwholesale.myshopify.com':
            $store_name = 'Piper Lou - ';
            break;
    }
    foreach ($imageUrls as $style => $colors) {
        foreach ($colors as $color => $image) {
            $variantData = array(
                'title' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat").' / '.$color,
                'price' => $price,
                'option1' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat"),
                'option2' => str_replace('_', ' ', $color),
                'weight' => '5.0',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => "{$store_name}Hat"
            );
            if ($color == 'Navy' && $style == 'Hat') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $style = $variant->option1 == 'Trucker Hat' ? "Hat" : "TwillHat";
        $color = str_replace(' ', '_', $variant->option2);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$style][$color]}",
            'variant_ids' => [$variant->id]
        );
        $imageUpdate[] = $image;
    };
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}

function processQueue($queue) {
    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    $image_data = array();
    $images = array();
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    if (isset($data['file'])) {
        $image_data = getImages($s3, $data['file']);
        $post = $data['post'];
        $shop = \App\Model\Shop::find($post['shop']);

        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
                continue;
            }
            $chunks = explode('/', $name);
            if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
                $images[$garment]["Pink"] = $name;
            } else {
                $garment = $chunks[2];
                if(!in_array($garment, array(
                    'Hoodie','LS','Tanks','Tees'
                ))) {
                    continue;
                }
                $color = explode("-", basename($name, ".jpg"))[1];
                $images[$garment][$color] = $name;
            }
        }

        switch($shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $matrix = json_decode(file_get_contents(DIR.'/src/wholesale.json'), true);
                if (!$matrix) {
                    return "Unable to open matrix file";
                }
            case 'piper-lou-collection.myshopify.com':
            case 'importer-testing.myshopify.com':
                $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p><span>You are going to <strong>LOVE</strong> this design! We offer apparel in Short Sleeve shirts, Long Sleeve Shirts, Tank tops, and Hoodies. If you want information on sizing, please view the sizing chart below. </span></p>
<p><span>Apparel is designed, printed, and shipped in the USA. 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 </span></p>
<p><a href='https://www.piperloucollection.com/pages/sizing-chart'>View our sizing chart</a></p>";
                break;
            case 'hopecaregive.myshopify.com':
                $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
                break;
            case 'game-slave.myshopify.com':
                $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
                break;
            default:
                $html = '<p></p>';
        }


        $product_data = array(
            'title'         => $post['product_title'],
            'body_html'     => $html,
            'tags'          => $post['tags'],
            'vendor'        => $post['vendor'],
            'product_type'  => $post['product_type'],
            'options' => array(
                array(
                    'name' => "Size"
                ),
                array(
                    'name' => "Color"
                ),
                array(
                    'name' => "Style"
                )
            ),
            'variants'      => array(),
            'images'        => array()
        );

        $ignore = array(
            'Hoodie' => array(
                // 'Navy' => array('4XL'),
                // 'Royal' => array('4XL'),
                'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                // 'Charcoal' => array('4XL'),
                // 'Black' => array('4XL'),
            ),
            'Tee' => array(
                // 'Black' => array('4XL'),
                // 'Navy' => array('4XL'),
                // 'Royal' => array('4XL'),
                'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                // 'Charcoal' => array('4XL'),
            ),
            'Tank'=> array(
                'Pink' => array(
                    'Small',
                    'Medium',
                    'Large',
                    'XL',
                    '2XL'
                )
            ),
            'Long Sleeve' => array(
                'Black'         => array('4XL'),
                'Navy'          => array('4XL'),
                'Royal Blue'    => array('4XL'),
                'Purple'        => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                'Grey'          => array('4XL'),
            )
        );

        foreach($images as $garment => $img) {
            if($garment == 'Tanks') {
                $garment = 'Tank';
            } else if($garment == 'Tees') {
                $garment = 'Tee';
            } else if($garment == "LS") {
                $garment = 'Long Sleeve';
            }
            foreach ($img as $color => $src) {
                if($color == "Royal") {
                    $color = "Royal Blue";
                } else if($color == "Charcoal") {
                    $color = "Grey";
                } else if($color == "Grey") {
                    $color = "Charcoal";
                }

                $variantSettings = $matrix[$garment];
                foreach($variantSettings['sizes'] as $size => $sizeSettings) {
                    if (isset($ignore[$garment]) &&
                    isset($ignore[$garment][$color])) {
                        if(is_array($ignore[$garment][$color])) {
                          if(in_array($size, $ignore[$garment][$color])) {
                             continue;
                           }
                        } else {
                            continue;
                        }
                    }
                    $sku = "$garment - $color - $size";
                    $varData = array(
                        'title' => "{$garment} \/ {$size} \/ {$color}",
                        'price' => $sizeSettings['price'],
                        'grams' => $sizeSettings['grams'],
                        'option1' => $size,
                        'option2' => $color,
                        'option3' => $garment,
                        'weight' => $sizeSettings['weight'],
                        'weight_unit' => $sizeSettings['weight_unit'],
                        'requires_shipping' => true,
                        'inventory_management' => null,
                        'inventory_policy' => "deny",
                        'sku' => $sku
                    );

                    if($garment == $post['default_product'] && $color == $post['default_color'] && $size == 'Small') {
                        error_log("Moving $color / $garment to front of array");
                        $product_data['variants'] = array_merge(array($varData), $product_data['variants']);
                    } else {
                        $product_data['variants'][] = $varData;
                    }
                }
            }
        }

        $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            if(!isset($variantMap[$variant->option2])) {
                $variantMap[$variant->option2] = array();
            }
            if (!isset($variantMap[$variant->option2][$variant->option3])) {
                $variantMap[$variant->option2][$variant->option3] = array();
            }

            if($variant->option2 == "Royal Blue") {
                $variant->option2 = "Royal";
            } elseif ($variant->option2 == "Grey") {
                $variant->option2 = "Charcoal";
            }
            $variantMap[$variant->option2][$variant->option3][] = $variant->id;
        }

        foreach($variantMap as $color => $garments) {
            foreach($garments as $garment => $ids) {
                if($garment == "Tee") {
                    $search = "Tees";
                } elseif($garment == "Long Sleeve") {
                    $search = "LS";
                } elseif($garment == "Tank") {
                    $search = "Tanks";
                } else {
                    $search = $garment;
                }

                $data = array(
                    'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$search][$color],
                    'variant_ids' => $ids
                );
                if($garment == $post['default_product'] && $color == $post['default_color']) {
                    $data['position'] = 1;
                }
                $imageUpdate[] = $data;
            }
        }

        $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
            'product' => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));

        $queue->finish(array($res->prouct->id));
        return array($res->product->id);
    }
    return true;
}

function createTumbler($queue)
{
    $image_data = array();
    $imageUrls = array();
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    if (isset($data['file'])) {
        $post = $data['post'];
        $image_data = getImages($s3, $data['file']);
        $shop = \App\Model\Shop::find($post['shop']);

        $shopReq = [];

        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != 'jpg') {
                continue;
            }
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $size = $specs[0];
            $color = $specs[1];
            $imageUrls[$size][$color] = $name;
        }

        switch ($shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'hopecaregive.myshopify.com':
            case 'game-slave.myshopify.com':
            default:
                $html = '<meta charset="utf-8" />'.
                        "<ul>".
                            "<li>2x heat &amp; cold retention (compared to plastic tumblers).</li>".
                            "<li>Double-walled vacuum insulation - Keeps Hot and Cold. </li>".
                            "<li>Fits most cup holders, Clear lid to protect from spills. </li>".
                            "<li>Sweat Free Design allows for a Strong Hold. </li>".
                            "<li>These tumblers will ship separately from our distributor in Texas. </li>".
                        '</ul>';
        }

        $product_data = array(
            'title' => $post['product_title'],
            'body_html' => $html,
            'tags' => $post['tags'],
            'vendor' => "Tx Tumbler",
            'product_type' => $post['product_type'],
            'options' => array(
                array(
                    'name' => "Size"
                ),
                array(
                    'name' => "Color"
                )
            ),
            'variants' => array(),
            'images' => array()
        );

        foreach ($imageUrls as $size => $colors) {
            $price = 24.99;
            if ($size == '30') {
                $price = 29.99;
            }
            foreach ($colors as $color => $image) {
                $optionColor = $color;
                if ($color == "Stainless") {
                    $optionColor = "Grey";
                }
                $skuColor = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => "{$size}oz /{$color}",
                    "price" => $price,
                    "option1" => "{$size}oz",
                    "option2" => str_replace('_', ' ', $optionColor),
                    "weight" => "1.1",
                    "weight_unit" => "lb",
                    "requires_shipping" => true,
                    "inventory_management" => null,
                    "inventory_policy" => "deny",
                    "sku" => "TX - T{$size} - {$skuColor} - {$post['product_title']} {$size}oz"
                );
                if($color == 'Navy' && $size == '30') {
                    error_log("Moving $color / $size to front of array");
                    $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
                } else {
                    $product_data['variants'][] = $variantData;
                }
            }
        }

        $res = callShopify($shop, '/admin/products.json', 'POST', array(
            'product' => $product_data
        ));

        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            $size = str_replace("oz", '', $variant->option1);
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                "src" => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
                'variant_ids' => [$variant->id]
            );
            $imageUpdate[] = $image;
        }
        $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));


        $queue->finish(array($res->product->id));
        return array($res->product->id);
    }
}

function createUvTumbler($queue)
{
    $image_data = array();
    $imageUrls = array();
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    if (isset($data['file'])) {
        $post = $data['post'];

        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => "shopify-product-importer",
            "Prefix" => $data['file']
        ));

        foreach ($objects as $object) {
            if (strpos($object["Key"], "MACOSX") !== false || strpos($object["Key"], "Icon^M" !== false)) {
                continue;
            }
            $image_data[] = $object["Key"];
        }
        $shop = \App\Model\Shop::find($post['shop']);

        $shopReq = [];

        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != 'jpg') {
                continue;
            }
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $size = $specs[0];
            $color = $specs[1];
            $imageUrls[$size][$color] = $name;
        }

        switch ($shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'hopecaregive.myshopify.com':
            case 'game-slave.myshopify.com':
            default:
                $html = '<meta charset="utf-8" />'.
                        "<ul>".
                            "<li>2x heat &amp; cold retention (compared to plastic tumblers).</li>".
                            "<li>Double-walled vacuum insulation - Keeps Hot and Cold. </li>".
                            "<li>Fits most cup holders, Clear lid to protect from spills. </li>".
                            "<li>Sweat Free Design allows for a Strong Hold. </li>".
                            "<li>These tumblers will ship separately from our distributor in Texas. </li>".
                        '</ul>';
        }

        $product_data = array(
            'title' => $post['product_title'],
            'body_html' => $html,
            'tags' => $post['tags'],
            'vendor' => "Tx Tumbler",
            'product_type' => $post['product_type'],
            'options' => array(
                array(
                    'name' => "Size"
                ),
                array(
                    'name' => "Color"
                )
            ),
            'variants' => array(),
            'images' => array()
        );

        foreach ($imageUrls as $size => $colors) {
            $price = 34.99;
            if ($size == '30') {
                $price = 39.99;
            }
            foreach ($colors as $color => $image) {
                $skuColor = str_replace('_', ' ', $color);
                $optionColor = $color;
                if ($color == "Stainless") {
                    $optionColor = "Grey";
                }
                $variantData = array(
                    'title' => "{$size}oz/{$color}",
                    "price" => $price,
                    "option1" => "{$size}oz",
                    "option2" => $skuColor,
                    "weight" => "1.1",
                    "weight_unit" => "lb",
                    "requires_shipping" => true,
                    "inventory_management" => null,
                    "inventory_policy" => "deny",
                    "sku" => "TX (UV PRINTED) - T{$size} - {$skuColor} - {$post['product_title']} {$size}oz"
                );
                if($color == 'Navy' && $size == '30') {
                    error_log("Moving $color / $size to front of array");
                    $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
                } else {
                    $product_data['variants'][] = $variantData;
                }
            }
        }

        $res = callShopify($shop, '/admin/products.json', 'POST', array(
            'product' => $product_data
        ));

        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            $size = str_replace("oz", '', $variant->option1);
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                "src" => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
                'variant_ids' => [$variant->id]
            );
            $imageUpdate[] = $image;
        }
        $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));

        $queue->finish(array($res->product->id));
        return array($res->product->id);
    }
}

function createUvWithBottles($queue)
{
    $prices = array(
        '30' => '34.99',
        '20' => '34.99',
        'Bottle' => '34.99',
        'SmallBottle' => '34.99'
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                '30' => '17',
                '20' => '16',
                'Bottle' => '17.50',
                'SmallBottle' => '13.50'
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this awesome drink ware! Perfect addition for anybody who needs a cold/warm drink on the go. </p>
<ul>
<li>Tumblers available in 30oz and 20oz, comes with lid</li>
<li>Water Bottles available in 40oz and 16oz, comes with twist caps</li>
<li>Vacuum sealed lid insulates cold drinks for 24 hours and hot drinks for 12 hours. Double wall feature eliminates condensation and retains temperature</li>
<li>Stainless steel with a powder coat finish provides maximum durability against damages</li>
<li>Narrow mouth opening is perfect to drink from without spilling and narrow bottom fits standard cupholders. </li>
<li>Hand wash only, do not put in dishwasher</li>
</ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $size = $specs[0];
        $color = $specs[1];
        $imageUrls[$size][$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags[] = 'drinkware';
    $tags[] = 'uv';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Tx Tumbler',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $size => $colors) {
        foreach ($colors as $color => $url) {
            $sku = $color;
            if ($color == 'Cyan') {
                $sku = 'Seafoam';
            }
            switch ($size) {
                case '30':
                    $option1 = '30oz Tumbler';
                    $sku = "TX - T30 - {$sku} - {$post['product_title']} 30oz";
                    break;
                case '20':
                    $option1 = '20oz Tumbler';
                    $sku = "TX - T20 - {$sku} - {$post['product_title']} 20oz";
                    break;
                case 'Bottle':
                    $option1 = '40oz Water Bottle';
                    $sku = "TX - TBigBottle - {$sku} - {$post['product_title']} BigBottle";
                    break;
                case 'SmallBottle':
                    $option1 = '16oz Water Bottle';
                    $sku = "TX - TSmlBottle - {$sku} - {$post['product_title']} SmlBottle";
                    break;
            }
            $variantData = array(
                'title' => $option1. ' / '.$color,
                'price' => $prices[$size],
                'option1' => $option1,
                'option2' => $color,
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => $sku
            );
            if ($color == 'Navy' && $size == '30') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = $variant->option2;
        switch ($size) {
            case '30oz Tumbler':
                $size = '30';
                break;
            case '20oz Tumbler':
                $size = '20';
                break;
            case '40oz Water Bottle':
                $size = 'Bottle';
                break;
            case '16oz Water Bottle':
                $size = 'SmallBottle';
                break;
        }
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
