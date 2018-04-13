<?php
define("DIR", dirname(dirname(__FILE__)));
require_once DIR.'/vendor/autoload.php';
require_once DIR.'/src/common.php';

$dbUrl = getenv("DATABASE_URL");
$dbConfig = parse_url($dbUrl);

$settings = array(
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
);

$credentials = new \Aws\Credentials\Credentials(getenv("AWS_ACCESS_KEY"),getenv("AWS_ACCESS_SECRET"));
$s3 = new \Aws\S3\S3Client([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => $credentials
]);

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
