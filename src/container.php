<?php

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\AdapterInterface;

$container = $app->getContainer();
$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('../views');
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    $view->getEnvironment()->addGlobal('flash', $c['flash']);
    $view->getEnvironment()->addGlobal('store', getenv("MYSHOPIFY_DOMAIN"));
    return $view;
};
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$capsule->getContainer()->singleton(
    \Illuminate\Contracts\Debug\ExceptionHandler::class,
    \App\CustomException::class
);

$container['db'] = function ($c) {
    return $capsule;
};

$container['flash'] = function ($c) {
    return new Slim\Flash\Messages();
};

$container['AuthController'] = function ($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Auth($view, $flash);
};

$container['UserController'] = function ($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Users($view, $flash);
};

$container['ShopController'] = function ($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Shops($view, $flash);
};

$container['ProductController'] = function($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    $filesystem = $c->get('Filesystem');
    $queue = $c->get('Queue');
    // $rabbit = $c->get('rabbit');
    return new \App\Controller\Products($view, $flash, $filesystem, $queue);
};

$container['TemplatesController'] = function($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Templates($view, $flash);
};
$container['QueuesController'] = function($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Queues($view, $flash);
};
$container['SubTemplatesController'] = function($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\SubTemplates($view, $flash);
};
$container['SettingsController'] = function($c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Settings($view, $flash);
};

$container['GoogleAuthController'] = function($c) {
    $client = $c->get('GoogleDrive');
    $flash = $c->get('flash');
    return new \App\Controller\Google($client, $flash);
};

$container['GoogleDrive'] = function($c) {
    $client = new Google_Client(array(
        'client_id' => getenv("GOOGLE_OAUTH_CLIENT_ID"),
        'client_secret' => getenv("GOOGLE_OAUTH_CLIENT_SECRET"),
        'redirect_uri' => getenv("GOOGLE_OAUTH_REDIRECT_URI")
    ));
    $client->setApplicationName("Product Importer");
    $client->setScopes(implode(' ', array(
        Google_Service_Sheets::SPREADSHEETS
    )));
    $client->setAccessType('offline');
    return $client;
};

$container['S3'] = function($c) {
    $credentials = new \Aws\Credentials\Credentials(getenv("AWS_ACCESS_KEY"),getenv("AWS_ACCESS_SECRET"));
    return new \Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'credentials' => $credentials
    ]);
};

$container['Filesystem'] = function($c) {
    $client = $c->get('S3');
    $adapter = new AwsS3Adapter($client, 'shopify-product-importer');
    return new Filesystem($adapter, array(
        'visibility' => AdapterInterface::VISIBILITY_PRIVATE
    ));
};

$container['Queue'] = function($c) {
    $credentials = new \Aws\Credentials\Credentials(getenv("AWS_ACCESS_KEY"),getenv("AWS_ACCESS_SECRET"));
    return new Aws\Sqs\SqsClient(array(
        'version' => 'latest',
        'region'  => 'us-east-1',
        'credentials' => $credentials
    ));
};
