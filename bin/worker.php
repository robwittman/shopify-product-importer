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

    foreach ($queue as $q) {
        error_log("Processing {$q->id}");
        try {
            $q->start();
            $res = processQueue($q);
            $q->finish($res);
        } catch(\Exception $e) {
            $q->fail($e->getMessage());
        }
    }
    sleep(10);
}

function processQueue($queue) {
    $matrix = json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    global $s3;
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

        if (isset($post['single']) && $post['single'] == true) {
            error_log("Creating single product");
            $product_data = array(
                'title' => $post['product_title'],
                'body_html' => $data['body_html'],
                'tags' => $post['tags'],
                'vendor' => $post['vendor'],
                'product_type' => $post['product_type'],
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
                    'Black' => array('4XL'),
                    'Navy' => array('4XL'),
                    'Royal Blue' => array('4XL'),
                    'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                    'Charcoal' => array('4XL'),
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
                    } elseif ($color == "Grey") {
                        $color = "Charcoal";
                    }

                    $variantSettings = $matrix[$garment];
                    foreach($variantSettings['sizes'] as $size => $sizeSettings) {
                        if (isset($ignore[$garment]) &&
                        isset($ignore[$garment][$color])) {
                            if(is_array($ignore[$garment][$color])) {
                              if(in_array($size, $ignore[$garment][$color])) {
                                  error_log("Ingnoring");
                                   continue;
                               }
                            } else {
                                error_log("Ignoring {$garment}/{$color}/{$size}");
                                continue;
                            }
                        }

                        $product_data['variants'][] = array(
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
                            'inventory_policy' => "deny"
                        );
                    }
                }
            }
            var_dump($product_data);
            error_log(count($product_data['variants'])." variants pending creation");
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
                }
                $variantMap[$variant->option2][$variant->option3][] = $variant->id;
            }

            var_dump($images);
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
                    if($garment == "Tee" && $color == "Navy") {
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
