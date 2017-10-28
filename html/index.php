<?php

ini_set('upload_max_filesize', '10M');
session_start();

require_once '../vendor/autoload.php';

$container = new \Flexsounds\Component\SymfonyContainerSlimBridge\ContainerBuilder();
$loader = new \Symfony\Component\DependencyInjection\Loader\PhpFileLoader($container, new \Symfony\Component\Config\FileLocator(dirname(__FILE__, 2)));
$loader->load('container.php');
if (file_exists('../container.env.php')) {
    $loader->load('container.env.php');
}

$app = new \Slim\App($container);

$config = $container->getParameter('database.config');
$connection = array(
    'driver' => 'pgsql',
    'host' => $config['host'],
    'database' => ltrim($config['path'], '/'),
    'username' => $config['user'],
    'password' => $config['pass'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
);
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($connection);
$capsule->setAsGlobal();
$capsule->bootEloquent();

require_once '../routes.php';

$app->run();
