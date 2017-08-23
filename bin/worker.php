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
                case 'tumbler':
                    $res = createTumbler($q);
                    break;
                case 'uv_tumbler':
                    $res = createUvTumbler($q);
                    break;
                case 'hats':
                    $res = createHats($q);
                    break;
                case 'stemless':
                    $res = createStemless($q);
                    break;
                case 'single_product':
                    $res = processQueue($q);
                    break;
            }
            $q->finish($res);
        } catch(\Exception $e) {
            exit($e->getMessage());
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
        if (pathinfo($key, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }
        $res[] = $object;
    }
    return array_map(function($object) {
        return $object["Key"];
    }, $res);
}

function createHats($queue) {
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'piper-lou-collection.myshopify.com':
        case 'plcwholesale.myshopify.com':
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
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $post['tags'],
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
                'price' => '29.99',
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
            case 'piper-lou-collection.myshopify.com':
            case 'plcwholesale.myshopify.com':
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
                    "option2" => str_replace('_', ' ', $optionColor),
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
