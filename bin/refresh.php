<?php

define("DIR", dirname(dirname(__FILE__)));
require_once DIR.'/vendor/autoload.php';
require_once DIR.'/src/common.php';

use App\Model\Shop;

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

$app = new Slim\App(['settings' => $settings]);
require_once DIR.'/src/container.php';
$container = $app->getContainer();

$client = $container->get('GoogleDrive');

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($settings['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getContainer()->singleton(
    \Illuminate\Contracts\Debug\ExceptionHandler::class,
    \App\CustomException::class
);

$shops = Shop::all();

foreach ($shops as $shop) {
    error_log($shop->myshopify_domain);
    try {
        if (!is_null($shop->google_access_token)) {
            $creds = $client->refreshToken($shop->google_refresh_token);
            $shop->google_access_token = $creds['access_token'];
            $shop->google_refresh_token = $creds['refresh_token'];
            $shop->save();
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
}
