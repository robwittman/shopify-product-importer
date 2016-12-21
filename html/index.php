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
    $view->getEnvironment()->addGlobal('store', $_SESSION['myshopify_domain']);
    return $view;
};

$container['flash'] = function ($c) {
    return new Slim\Flash\Messages();
};

$app->get('/', function ($request, $response) {
    return $this->view->render($response, 'product.html');
});

/*========================================
    Authentication
========================================*/
$app->get('/auth', function ($request, $response) {
    return $this->view->render($response, 'auth.html', array(
        'session' => $_SESSION
    ));
});

$app->post('/auth', function ($request, $response) {
    $params = $request->getParsedBody();
    $_SESSION['shopify_api_key'] =  $params['shopify_api_key'];
    $_SESSION['shopify_password'] = $params['shopify_password'];
    $_SESSION['shopify_shared_secret'] = $params['shopify_shared_secret'];
    $_SESSION['myshopify_domain'] = $params['myshopify_domain'];
    $this->flash->addMessage('message', Messages::AUTHENTICATION_SET);
    return $response->withRedirect('/products');
});

$app->any('/clear', function ($request, $response) {
    session_destroy();
    $this->flash->addMessage('message', "Session successfully cleared");
    return $this->view->render($response, 'auth.html');
});

/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', function ($request, $response) {
    if (!$_SESSION['shopify_api_key'] ||
       !$_SESSION['shopify_password'] ||
       !$_SESSION['shopify_shared_secret'] ||
       !$_SESSION['myshopify_domain']) {
           $this->flash->addMessage('error', Errors::NO_AUTHENTICATION_PROVIDED);
           return $response->withRedirect('/auth');
    }
    return $this->view->render($response, 'product.html');
});

$app->post('/products', function ($request, $response) {
    $results = array();
    $files = $request->getUploadedFiles();
    $hash = hash('sha256', uniqid());
    $path = "/tmp/{$hash}";
    if (empty($_FILES['zip_file'])) {
        $this->flash->addMessage('error', "You have to upload a .zip file");
        return $this->view->render($response, 'product.html');
    }
    $images = array();

    $file = $_FILES['zip_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
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
            $results[$garment][$color] = $name;
        }

        // We have a multidimensional array of products with images. So now, we
        // have to create the product, add its images, and then update the product with the map of
        // images -> variants, so that Shopify is correct.
        $product = array(
            'title'         => $_POST['product_title'],
            'handle'        => $_POST['product_handle'],
            'body_html'     => $_POST['body_html'],
            'tags'          => $_POST['tags'],
            'vendor'        => $_POST['vendor'],
            'product_type'  => $_POST['product_type'],
            'variants'      => array(),
            'images'        => array()
        );
        $url = generateUrl($_SESSION);
        $res = callShopify($url."/admin/products.json", "POST", array('product' => $product));
        return $response->withJson(array(
            'res' => $res
        ));
    } else {
        $this->flash->addMessage('error', "There was an error uploading your .zip file");
        return $this->view->render($response, 'product.html');
    }

    // var_dump($files['zip_file']);
});


$app->run();
