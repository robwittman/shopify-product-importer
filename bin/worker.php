<?php
define("DIR", dirname(dirname(__FILE__)));
require_once DIR.'/vendor/autoload.php';
require_once DIR.'/src/common.php';

use App\Model\Queue;

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

foreach (glob(DIR."/bin/scripts/*.php") as $file) {
    include_once ($file);
}

while (true) {
    $queue = Queue::where('status', Queue::PENDING)->get();
    // $queue = Queue::get();
    foreach ($queue as $q) {
        try {
            // $q->start();
            $data = json_decode($q->data, true);
            switch ($data['post']['template']) {
                case 'hats':
                    $res = createHats($q);
                    break;
                case 'stemless':
                    $res = createStemless($q);
                    break;
                case 'single_product':
                    $res = processQueue($q, $client);
                    break;
                case 'drinkware':
                    $res = createDrinkware($q);
                    break;
                case 'uv_drinkware':
                    $res = createUvDrinkware($q);
                    break;
                case 'flasks':
                    $res = createFlasks($q);
                    break;
                case 'baby_body_suit':
                    $res = createBabyBodySuit($q, $client);
                    break;
                case 'raglans':
                    $res = createRaglans($q, $client);
                    break;
                case 'front_back_pocket':
                    $res = createFrontBackPocket($q, $client);
                    break;
                case 'uv_with_bottles':
                    $res = createUvWithBottles($q);
                    break;
                case 'christmas':
                    $res = createChristmas($q, $client);
                    break;
                case 'hats_masculine':
                    $res = createMasculineHats($q);
                    break;
                default:
                    throw new \Exception("Invalid template {$data['post']['template']} provided");
            }
            $q->finish($res);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            // exit($e->getMessage());
            $q->fail($e->getMessage());
        }
    }
    sleep(10);
}

function getImages($s3, $prefix) {
    $objects = $s3->getIterator('ListObjects', array(
        "Bucket" => "shopify-product-importer",
        "Prefix" => $prefix
    ));
    $res = array();
    foreach ($objects as $object) {
        $key = $object["Key"];
        if (strpos($key, "MACOSX") || strpos($key, "Icon^M")) {
            continue;
        }
        if (!in_array(pathinfo($key, PATHINFO_EXTENSION), array('jpg', 'png', 'jpeg'))) {
            continue;
        }
        $res[] = $object;
    }
    return array_map(function($object) {
        return $object["Key"];
    }, $res);
}

function getSku($size)
{
    switch ($size) {
        case 'Small':
            return 'S';
        case 'Medium':
            return 'M';
        case 'Large':
            return 'L';
    }
}

function logResults(Google_Client $client, $sheet, $type, array $results)
{
    $service = new Google_Services_Sheets($client);
    $range = $type.'!A:J';
    $valueRange = new Google_Service_Sheets_ValueRange();
    $valueRange->setValues(array('values' => compressValues($results)));
    $service->spreadsheets_values->append($sheet, $range, $valueRange, array('valueInputOption' => "RAW"));
}

function compressValues($results)
{
    $return = array();
    foreach ($results['variants'] as $variant) {
        $return['product_name'] = $results['product_name'];
        $return['shopify_product_admin_url'] = $results['shopify_product_admin_url'];
        $return['front_print_file_url'] = $result['front_print_file_url'];
        $return['back_print_file_url'] = $result['back_print_file_url'];
        $return['garment_color'] = $variant['garment_color'];
        $return['product_fulfiller_code'] = $variant['product_fulfiller_code'];
        $return['product_sku'] = $variant['product_sku'];
    }
    return $return;
}
