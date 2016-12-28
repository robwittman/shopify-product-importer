<?php
ini_set('upload_max_filesize', '10M');
session_start();

require_once '../vendor/autoload.php';
require_once '../src/common.php';

use App\Model\Errors;
use App\Model\Messages;

// Load our App and container
$app = new Slim\App(array(
    'settings' => array(
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => false,
        'db' => array(
            'driver' => 'pgsql',
            'host' => 'postgres',
            'database' => 'shopify',
            'username' => 'postgres',
            'password' => 'password',
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
