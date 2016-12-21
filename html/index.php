<?php
ini_set('upload_max_filesize', '10M');
session_start();

require_once('../vendor/autoload.php');
require_once('../src/Errors.php');
require_once('../src/Messages.php');
require_once('../src/common.php');
// Load our App and container
$app = new Slim\App(array(
    'settings' => array(
        'determineRouteBeforeAppMiddleware' => true
    )
));
$container = $app->getContainer();
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('../views');
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    $view->getEnvironment()->addGlobal('flash', $c['flash']);
    $view->getEnvironment()->addGlobal('store', getenv("MYSHOPIFY_DOMAIN"));
    return $view;
};

$container['flash'] = function ($c) {
    return new Slim\Flash\Messages();
};

$app->get('/', function ($request, $response) {
    return $this->view->render($response, 'product.html');
});

/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', function ($request, $response) {
    return $this->view->render($response, 'product.html');
});

$app->get('/debug', function ($request, $response) {
    return $this->view->render($response, 'debug.html', array(
        'info' => phpinfo()
    ));
});

$app->get('/matrix', function ($request, $response) {
    $matrix = file_get_contents('../src/matrix.json');
    if (!$matrix) {
        $this->flash->addMessage('error', "Failed loading product matrix!");
        return $this->view->render($response, 'product.html');
    }
    return $this->view->render($response, 'matrix.html', array(
        'matrix' => $matrix
    ));
});

$app->post('/products', function ($request, $response) {
    $matrix = json_decode(file_get_contents('../src/matrix.json'), true);
    if (!$matrix) {
        return $this->view->render($response, 'product.html', array(
            'error' => "Failed loading product matrix!"
        ));
    }
    $images = array();
    $files = $request->getUploadedFiles();
    $hash = hash('sha256', uniqid());
    $path = "/tmp/{$hash}";
    if (empty($_FILES['zip_file'])) {
        return $this->view->render($response, 'product.html', array(
            'error' => "You have to upload a .zip file"
        ));
    }

    $file = $_FILES['zip_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $this->view->render($response, 'product.html', array(
            'error' => "There was an error uploading your .zip file. Code {$file['error']}"
        ));
    }
    $tmpName = $file['tmp_name'];

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
        $garment = $chunks[4];
        $color = explode("-", basename($name, ".jpg"))[1];
        $images[$garment][$color] = $name;
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


        // Format garment and color for the image file
        $garment = $type;
        $c = $color;
        if (isset($data['color_map'][$c])) {
            $c = $data['color_map'][$c];
        }
        if ($garment == "Long Sleeve") {
            $garment = 'LS';
        } elseif ($garment == "Tee") {
            $garment = "Tees";
        } elseif ($garment == "Tank") {
            $garment = "Tanks";
        }
        foreach ($images[$garment] as $image) {
            array_push($product['images'], array(
                'attachment' => base64_encode(file_get_contents($image)),
                'filename' => $image,
                "metafields" => array(
                        array(
                        'key' => 'color',
                        'value' => $color,
                        'value_type' => 'string',
                        'namespace' => 'mapping'
                    )
                )
            ));
        }
        // Let's create our product
        $res = callShopify("/admin/products.json", "POST", array('product' => $product));
        if ($res) {
            $created_products[] = $res;
            // Now we need to update to map images to variants
        } else {
            foreach ($created_products as $created) {
                // DELETE successful products, and return result
            }
            return $this->view->render($response, 'product.html', array(
                'error' => "An error occured creating your products"
            ));
        }
    }
    return $this->view->render($response, 'result.html', array(
        'products' => json_encode($created_products)
    ));
});


$app->run();
