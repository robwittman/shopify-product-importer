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

$app->get('/login', function ($request, $response) {
    $this->view->render($response, 'auth.html');
});

$app->any('/logout', function ($request, $response) {
    session_destroy();
    $this->view->render($response, 'auth.html');
});

$app->post('/login', function ($request, $response) {
    $password = $_POST['password'];
    if ($password == getenv("PASSWORD")) {
        $_SESSION['logged_in'] = 1;
        $_SESSION['expiration'] = strtotime('+6 hours');
        $this->flash->addMessage('message', "Login successful");
        return $response->withRedirect('/products');
    } else {
        $this->view->render($response, 'auth.html', array(
            'error' => "Unauthorized"
        ));
    }
});
/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', function ($request, $response) {
    return $this->view->render($response, 'product.html');
})->add('checkLogin');

$app->get('/changelog', function ($request, $response) {
    $changelog = json_decode(file_get_contents('../src/changelog.json'), true);
    return $this->view->render($response, 'changelog.html', array(
        'changelog' => $changelog['changelog']
    ));
})->add('checkLogin');

$app->get('/matrix', function ($request, $response) {
    $matrix = file_get_contents('../src/matrix.json');
    if (!$matrix) {
        $this->flash->addMessage('error', "Failed loading product matrix!");
        return $this->view->render($response, 'product.html');
    }
    return $this->view->render($response, 'matrix.html', array(
        'matrix' => $matrix
    ));
})->add('checkLogin');

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
        if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
            $images[$garment]["Pink"] = $name;
        } else {
            $garment = $chunks[4];
            $color = explode("-", basename($name, ".jpg"))[1];
            $images[$garment][$color] = $name;
        }
    }
    writeLog(json_encode($images));
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
        $res = callShopify("/admin/products.json", "POST", array('product' => $product));
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
            writeLog($garment);

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
                        // We also want to set this image as the default
                        break;
                    case "Black":
                        $variant_ids = $variant_map["Black"];
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
                if ($position) {
                    $data['position'] = 1;
                }
                array_push($update, $data);
            }

            $pass_data = array(
                "product" => array(
                    "id" => $res->product->id,
                    "images" => $update
                )
            );
            writeLog(json_encode($pass_data));
            $res = callShopify("/admin/products/{$res->product->id}.json", "PUT", $pass_data);

            if (!$res) {
                writeLog(json_encode($res));
                return $this->view->render($response, 'product.html', array(
                    'error' => "An error occured updating product images"
                ));
            }
            $created_products[] = $res->product;
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
        'products' => $created_products
    ));
})->add('checkLogin');

function checkLogin($request, $response, $next)
{
    if (isset($_SESSION['logged_in']) && $_SESSION['expiration'] > time()) {
        return $next($request, $response);
    }
    session_destroy();
    return $response->withRedirect('/login');
}

function writeLog($message)
{
    return true;
    // error_log($message);
    // if (getenv("ENV") == "dev") {
    //     error_log($message);
    // }
}
$app->run();
