<?php

ini_set('upload_max_filesize', '10M');
session_start();

require_once '../vendor/autoload.php';
require_once '../src/common.php';

use App\Model\Errors;
use App\Model\Messages;

$dbUrl = getenv("DATABASE_URL");
$dbConfig = parse_url($dbUrl);
// Load our App and container
$app = new Slim\App(array(
    'settings' => array(
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => false,
        'db' => array(
            'driver' => $dbConfig['scheme'] === 'postgres' ? 'pgsql' : 'mysql',
            'host' => $dbConfig['host'],
            'database' => ltrim($dbConfig['path'], '/'),
            'username' => $dbConfig['user'],
            'password' => $dbConfig['pass'],
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => ''
        )
    )
));

$app->add(new App\Middleware\Session());

require_once '../src/container.php';
require_once '../src/routes.php';

$app->run();
