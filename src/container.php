<?php

use Slim\Container;

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

$container['db'] = function () {
    global $capsule;
    return $capsule;
};

$container['flash'] = function () {
    return new Slim\Flash\Messages();
};

$container['AuthController'] = function (Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Auth($view, $flash);
};

$container['UserController'] = function (Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Users($view, $flash);
};

$container['ShopController'] = function (Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Shops($view, $flash);
};

$container['ProductController'] = function(Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    $filesystem = $c->get('Filesystem');
    $queue = $c->get('SqsQueue');
    return new \App\Controller\Products($view, $flash, $filesystem, $queue);
};

$container['TemplatesController'] = function(Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Templates($view, $flash);
};
$container['QueuesController'] = function(Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Queues($view, $flash);
};
$container['SubTemplatesController'] = function(Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\SubTemplates($view, $flash);
};
$container['SettingsController'] = function(Container $c) {
    $view = $c->get('view');
    $flash = $c->get('flash');
    return new \App\Controller\Settings($view, $flash);
};

$container['GoogleAuthController'] = function(Container $c) {
    $client = $c->get('GoogleDrive');
    $flash = $c->get('flash');
    return new \App\Controller\Google($client, $flash);
};

$container['GoogleDrive'] = function(Container $c) {
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

$container['Filesystem'] = function(Container $c) {
    $client = new \Aws\S3\S3Client([
        'credentials' => [
            'key'    => getenv("AWS_ACCESS_KEY"),
            'secret' => getenv("AWS_ACCESS_SECRET")
        ],
        'region' => getenv("AWS_REGION"),
        'version' => 'latest',
    ]);

    $adapter = new \League\Flysystem\AwsS3v3\AwsS3Adapter($client, getenv("AWS_S3_BUCKET"));
    $filesystem = new \League\Flysystem\Filesystem($adapter, [
        'visibility' => \League\Flysystem\AdapterInterface::VISIBILITY_PRIVATE
    ]);
    return $filesystem;
};

$container['LocalFilesystem'] = function(Container $c) {
    $adapter = new \League\Flysystem\Adapter\Local(DIR.'/uploads');
    $filesystem = new \League\Flysystem\Filesystem($adapter);
    return $filesystem;
};

$container['SqsQueue'] = function(Container $c) {
    $client = new \Aws\Sqs\SqsClient([
        'credentials' => [
            'key'    => getenv("AWS_ACCESS_KEY"),
            'secret' => getenv("AWS_ACCESS_SECRET")
        ],
        'region' => getenv("AWS_REGION"),
        'version' => '2012-11-05'
    ]);
    return $client;
};

$container['MountManager'] = function(Container $c) {
    $manager = new \League\Flysystem\MountManager([
        's3' => $c->get('Filesystem'),
        'local' => $c->get('LocalFilesystem'),
    ]);
    return $manager;
};
