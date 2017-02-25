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
        error_log("Processing {$q->id}");
        try {
            $q->start();
            $data = json_decode($q->data, true);
            var_dump($data);
            if (isset($data['post']['tumbler']) && $data['post']['tumbler'] == 'on') {
                $res = createTumbler($q);
            } else {
                $res = processQueue($q);
            }
            $q->finish($res);
        } catch(\Exception $e) {
            $q->fail($e->getMessage());
        }
    }

    sleep(10);
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
        $post = $data['post'];
        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => 'shopify-product-importer',
            "Prefix" => $data['file']
        ));
        foreach($objects as$object) {
            $image_data[] = $object["Key"];
        }

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
                $html = '<p>Designed, printed, and shipped in the USA!</p><p><a href="https://www.piperloucollection.com/pages/sizing-chart">View our sizing chart</a></p>';
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
        if (isset($post['single']) && $post['single'] == true) {
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
        } else {
            error_log("Creating multiple products");
            $created_products = array();
            foreach ($matrix as $type => $data) {
                // $positioned = false;
                $sizes = $data['sizes'];
                $colors = $data['colors'];
                $variants = array();
                $product = array(
                    'title'         => $post['product_title'].' '.$type,
                    'body_html'     => $data['body_html'],
                    'tags'          => $post['tags'],
                    'vendor'        => $post['vendor'],
                    'product_type'  => $post['product_type'],
                    'options'       => array(
                        array(
                            'name' => "Size",
                        ),
                        array(
                            'name' => "Color"
                        )
                    ),
                    'variants'      => array(),
                    'images'        => array()
                );
                // Add the image for each color
                foreach ($sizes as $size => $data) {
                    foreach ($colors as $color) {
                        $variant = array(
                            'title' => "{$size} \/ {$color}",
                            'price' => $data['price'],
                            'grams' => $data['grams'],
                            'option1' => $size,
                            'option2' => $color,
                            'weight' => $data['weight'],
                            'weight_unit' => $data['weight_unit'],
                            'requires_shipping' => true,
                            'inventory_management' => null,
                            'inventory_policy' => "deny",
                            'sku' => "SKU"
                        );
                        $variants[] = $variant;
                    }
                }

                switch ($post['default']) {
                    case 'black':
                        foreach ($variants as $key => $variant) {
                            if ($variant['option2'] == 'Black') {
                                $product['variants'][] = $variant;
                                unset($variants[$key]);
                            }
                        }
                        foreach($variants as $variant) {
                            $product['variants'][] = $variant;
                        }
                        break;
                    case 'navy':
                        foreach ($variants as $key => $variant) {
                            if ($variant['option2'] == 'Navy') {
                                $product['variants'][] = $variant;
                                unset($variants[$key]);
                            }
                        }
                        foreach($variants as $variant) {
                            $product['variants'][] = $variant;
                        }
                        break;
                    default:
                        $product['variants'] = $variants;
                }
                // Let's create our product
                $res = callShopify($shop, "/admin/products.json", "POST", array('product' => $product));

                if ($res) {
                    $positioned = false;
                    $update = array();
                    // Format garment and color for the image file
                    $garment = $type;

                    if ($garment == "Long Sleeve") {
                        $garment = 'LS';
                    } elseif ($garment == "Tee") {
                        $garment = "Tees";
                    } elseif ($garment == "Tank") {
                        $garment = "Tanks";
                    }

                    // Map of variant_ids for each color (matrix color!)
                    $variant_map = array();
                    foreach ($res->product->variants as $variant) {
                        if (!isset($variant_map[$variant->option2])) {
                            $variant_map[$variant->option2] = array($variant->id);
                        } else {
                            array_push($variant_map[$variant->option2], $variant->id);
                        }
                    }

                    foreach ($images[$garment] as $col => $image) {
                        $variant_ids = array();
                        $crop = false;
                        $position = 0;
                        switch ($col) {
                            case "Royal":
                                $variant_ids = $variant_map["Royal Blue"];
                                break;
                            case "Charcoal":
                                $variant_ids = $variant_map["Grey"];
                                break;
                            case "Navy":
                                $variant_ids = $variant_map["Navy"];
                                if (!$crop && $post['default'] == "navy") {
                                    $crop = true;
                                    if ($garment == 'Tanks') {
                                        $tmpFile = '/tmp/cropped.jpg';
                                        $crop = cropImage($image, $tmpFile, 425, 850);
                                        $cropData = array(
                                            'attachment' => base64_encode(file_get_contents($tmpFile)),
                                            'position' => 1
                                        );
                                        array_push($update, $cropData);
                                        // $position = 1;
                                    } else {
                                        $tmpFile = '/tmp/cropped.jpg';
                                        $crop = cropImage($image, $tmpFile);
                                        $cropData = array(
                                            'attachment' => base64_encode(file_get_contents($tmpFile)),
                                            'position' => 1
                                        );
                                        array_push($update, $cropData);
                                    }
                                    // Also create our cropped image
                                }
                                // We also want to set this image as the default
                                break;
                            case "Black":
                                $variant_ids = $variant_map["Black"];
                                if (!$crop && $post['default'] == "black") {
                                    $crop = true;
                                    if ($garment == 'Tanks') {
                                        // $position = 1;
                                        $tmpFile = '/tmp/cropped.jpg';
                                        $crop = cropImage($image, $tmpFile, 425, 750);
                                        $cropData = array(
                                            'attachment' => base64_encode(file_get_contents($tmpFile)),
                                            'position' => 1
                                        );
                                        array_push($update, $cropData);
                                    } else {
                                        $tmpFile = '/tmp/cropped.jpg';
                                        $crop = cropImage($image, $tmpFile);
                                        $cropData = array(
                                            'attachment' => base64_encode(file_get_contents($tmpFile)),
                                            'position' => 1
                                        );
                                        array_push($update, $cropData);
                                    }
                                    // Also create our cropped image
                                }
                                break;
                            case "Pink":
                                $variant_ids = $variant_map["Pink"];
                                break;
                            case "Purple":
                                $variant_ids = $variant_map["Purple"];
                                break;
                        }
                        $loc = "https://s3.amazonaws.com/shopify-product-importer/".str_replace(" ", "_", $image);

                        $data = array(
                            'attachment' => base64_encode(file_get_contents($loc)),
                            'variant_ids' => $variant_ids
                        );
                        // if ($position) {
                        //     $data['position'] = 1;
                        // }
                        array_push($update, $data);
                    }

                    $pass_data = array(
                        "product" => array(
                            "id" => $res->product->id,
                            "images" => $update
                        )
                    );

                    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", $pass_data);

                    if (!$res) {
                        $this->flash->addMessage('error', 'An error occured updateing product images');
                        return $response->withRedirect('/products');
                    }
                    $created_products[] = $res->product;
                } else {
                    foreach ($created_products as $created) {
                        // DELETE successful products, and return result
                    }

                }
            }
        }
    }
    return true;
}

function createTumbler($queue)
{
    error_log("Processing tumbler");
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
            if (strpos($object["Key"], "MACOSX") !== false) {
                continue;
            }
            $image_data[] = $object["Key"];
        }
        $shop = \App\Model\Shop::find($post['shop']);

        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != 'jpg') {
                continue;
            }

            $availColors = array('Navy','Black','Pink','Teal','Grey');
            foreach ($availColors as $color) {
                if (strpos($name, $color) !== false) {
                    $imageUrls[$color] = $name;
                }
            }
        }

        switch ($shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'hopecaregive.myshopify.com':
            case 'game-slave.myshopify.com':
            default:
                $html = '<meta charset="utf-8" />'.
                        "<ul>".
                            "<li>30 oz Stainless Steel Powder Coated Tumbler and Lid.</li>".
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
            'vendor' => "Centex Powder Coating",
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

        foreach ($imageUrls as $color => $image) {
            $variantData = array(
                'title' => "30 oz Stainless Steel Powder Coated Tumbler and Lid. / {$color}",
                "price" => "29.99",
                "grams" => 499,
                "option1" => "30 oz Stainless Steel Powder Coated Tumbler and Lid.",
                "option2" => $color,
                "compare_at_price" => "39.99",
                "weight" => "1.1",
                "weight_unit" => "lb",
                "requires_shipping" => true,
                "inventory_management" => null,
                "inventory_policy" => "deny",
                "sku" => "{$color} / {$post['product_title']}"
            );
            if ($color == "Navy") {
                array_unshift($product_data['variants'], $variantData);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }

        $res = callShopify($shop, '/admin/products.json', 'POST', array(
            'product' => $product_data
        ));

        $variantMap = array("Navy" => array(),"Black" => array(),"Pink" => array(),"Teal" => array(),"Grey" => array());
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            $variantMap[$variant->option2][] = $variant->id;
            $image = array(
                "src" => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$variant->option2]}",
                "variant_ids" => $variantMap[$variant->option2]
            );
            if ($variant->option2 == "Navy") {
                error_log("Setting navy product to 1st position");
                $data['position'] = 1;
            }
            $imageUpdate[] = $image;
        }



        $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));

        $queue->finish(array($res->product->id));
        error_log($res->product->id);
        return array($res->product->id);
    }
}
