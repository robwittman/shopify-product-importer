<?php

use App\Model\Shop;
use App\Model\User;

$app->get('/', function ($request, $response) {
    return $response->withRedirect('/products');
});

#
#   Uncomment to tu admin user in database
#
// $app->get('/init', function ($request, $response) {
//     $user = new \App\Model\User();
//     $user->email = 'admin@admin.com';
//     $user->password = 'password';
//     $user->role = 'admin';
//     $user->save();
//     var_dump($user);
// });
/*========================================
    User Routes
 =======================================*/
$app->group('/users', function () use ($app) {
    $app->get('', "UserController:index");
    $app->get('/create', "UserController:create");
    $app->post('', "UserController:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "UserController:show");
        $app->map(array("GET", "POST"), '/access', "UserController:access");
        $app->post('', "UserController:update");
        $app->map(array("GET", "POST"), '/delete', "UserController:delete");
    });
})->add(new App\Middleware\Authorization());

/*=========================================
    Auth Routes
=========================================*/
$app->group('/auth', function () use ($app) {
    $app->map(array('GET', 'POST'), '/login', 'AuthController:login');
    $app->any('/logout', 'AuthController:logout');
});

/*=========================================
    Shop Routes
=========================================*/
$app->group('/shops', function () use ($app) {
    $app->get('', "ShopController:index");
    $app->get('/create', "ShopController:create");
    $app->post('', "ShopController:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "ShopController:show");
        $app->post('', "ShopController:update");
        $app->map(array("GET", "POST"), '/delete', "ShopController:delete");
    });
})->add(new App\Middleware\Authorization());

// TODO: Move this to separate file
/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', function ($request, $response) {
    $user = User::find($request->getAttribute('user')->id);
    $shops = $user->shops;
    return $this->view->render($response, 'product.html', array(
        'shops' => $shops
    ));
})->add(new \App\Middleware\Authorization());


$app->post('/products', function ($request, $response) {
    $matrix = json_decode(file_get_contents('../src/matrix.json'), true);
    if (!$matrix) {
        $this->flash->addMessage('error', 'Failed loading product matrix');
        return $response->withRedirect('/products');
    }
    $images = array();
    $files = $request->getUploadedFiles();
    $hash = hash('sha256', uniqid());
    $path = "/tmp/{$hash}";
    if (empty($_FILES['zip_file'])) {
        $this->flash->addMessage('error', 'You have to upload a .zip file!');
        return $response->withRedirect('/products');
    }

    $file = $_FILES['zip_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $this->flash->addMessage('error', "There was an error uploading your .zip file. Code {$file['error']}");
        return $response->withRedirect('/products');
    }
    $tmpName = $file['tmp_name'];

    $shopId = $_POST['shop'];
    $shop = Shop::find($shopId);
    if (empty($shop)) {
        $this->flash->addMessage('error', "We couldnt find that shop");
        return $response->withRedirect('/products');
    }
    $zip = new ZipArchive();
    $zip->open($tmpName);
    $zip->extractTo($path);
    $zip->close();

    // We now have files in "/tmp/{$hash}, so let's get a flat list of each image"
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($objects as $name => $object) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }
        $chunks = explode('/', $name);
        if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
            $images[$garment]["Pink"] = $name;
        } else {
            $garment = $chunks[4];
            $color = explode("-", basename($name, ".jpg"))[1];
            $images[$garment][$color] = $name;
        }
    }
    $created_products = array();
    foreach ($matrix as $type => $data) {
        $sizes = $data['sizes'];
        $colors = $data['colors'];
        $product = array(
            'title'         => $_POST['product_title'].' '.$type,
            'body_html'     => $data['body_html'],
            'tags'          => $_POST['tags'],
            'vendor'        => $_POST['vendor'],
            'product_type'  => $_POST['product_type'],
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
                $product['variants'][] = array(
                    'title' => "{$size} \/ {$color}",
                    'price' => $data['price'],
                    'grams' => $data['grams'],
                    'option1' => $size,
                    'option2' => $color,
                    'weight' => $data['weight'],
                    'weight_unit' => $data['weight_unit'],
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => "deny"
                );
            }
        }


        // Let's create our product
        $res = callShopify($shop, "/admin/products.json", "POST", array('product' => $product));

        if ($res) {
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
                        $position = 1;
                        if ($position && !$crop && $_POST['default'] == "navy") {
                            $crop = true;
                            if ($garment == 'Tank') {
                            } else {
                                $tmpFile = '/tmp/cropped.jpg';
                                $crop = cropImage($image, $tmpFile);
                                $cropData = array(
                                    'attachment' => base64_encode(file_get_contents($tmpFile)),
                                    'position' => 1
                                );
                            }
                            // Also create our cropped image
                            array_push($update, $cropData);
                        }
                        // We also want to set this image as the default
                        break;
                    case "Black":
                        $variant_ids = $variant_map["Black"];
                        $position = 1;
                        if ($position && !$crop && $_POST['default'] == "black") {
                            $crop = true;
                            if ($garment == 'Tank') {
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
                $data = array(
                    'attachment' => base64_encode(file_get_contents($image)),
                    'variant_ids' => $variant_ids
                );
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
            $this->flash->addMessage('error', "An error occured creating your products");
            return $response->withRedirect('/products');
        }
    }
    return $this->view->render($response, 'result.html', array(
        'products' => $created_products
    ));
})->add(new \App\Middleware\Authorization());

/*=========================================
Miscellaneous Routes
=========================================*/
$app->get('/changelog', function ($request, $response) {
    $changelog = json_decode(file_get_contents('../src/changelog.json'), true);
    return $this->view->render($response, 'changelog.html', array(
        'changelog' => $changelog['changelog']
    ));
})->add(new \App\Middleware\Authorization());

$app->get('/matrix', function ($request, $response) {
    $matrix = file_get_contents('../src/matrix.json');
    if (!$matrix) {
        $this->flash->addMessage('error', "Failed loading product matrix!");
        return $this->view->render($response, 'product.html');
    }
    return $this->view->render($response, 'matrix.html', array(
        'matrix' => $matrix
    ));
})->add(new \App\Middleware\Authorization());
