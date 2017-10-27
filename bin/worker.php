<?php
define("DIR", dirname(dirname(__FILE__)));
require_once DIR.'/vendor/autoload.php';
require_once DIR.'/src/common.php';

use App\Model\Queue;
use App\Model\Sku;

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
    $queue = Queue::where('status', Queue::PENDING)
        ->orderBy('created_at', 'asc')
        ->first();
    if (!$queue) {
        sleep(10);
    } else {
        try {
            $queue->start();
            $data = json_decode($queue->data, true);
            switch ($queue->template) {
                case 'product-with-crew':
                    $res = apparelWithCrew($queue, $client);
                    break;
                case 'hats':
                    $res = createHats($queue);
                    break;
                case 'stemless':
                    $res = createStemless($queue);
                    break;
                case 'single_product':
                    $res = processQueue($queue, $client);
                    break;
                case 'drinkware':
                    $res = createDrinkware($queue);
                    break;
                case 'uv_drinkware':
                    $res = createUvDrinkware($queue);
                    break;
                case 'flasks':
                    $res = createFlasks($queue);
                    break;
                case 'baby_body_suit':
                    $res = createBabyBodySuit($queue, $client);
                    break;
                case 'raglans':
                    $res = createRaglans($queue, $client);
                    break;
                case 'front_back_pocket':
                    $res = createFrontBackPocket($queue, $client);
                    break;
                case 'uv_with_bottles':
                    $res = createUvWithBottles($queue);
                    break;
                case 'christmas':
                    $res = createChristmas($queue, $client);
                    break;
                case 'hats_masculine':
                    $res = createMasculineHats($queue);
                    break;
                default:
                    throw new \Exception("Invalid template {$data['post']['template']} provided");
            }
            error_log("Product {$res['product_id']} finished");
            $queue->finish($res);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            if ($message = json_decode($e->getMessage())) {
                $queue->fail($message->error->message);
            } else {
                $queue->fail($e->getMessage());
            }
        }
    }
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
    return $size;
}

function logResults(Google_Client $client, $sheet, $printType, array $results)
{
    if ($printType == 'front_print') {
        $sheetName = 'Front Print';
    } elseif ($printType == 'back_print') {
        $sheetName = 'Back Print';
    } elseif ($printType == 'double_sided') {
        $sheetName = 'Two Sided';
    }
    $service = new Google_Service_Sheets($client);
    $range = $sheetName.'!A:J';
    $values = compressValues($results, $printType);
    foreach ($values as $value) {
        $valueRange = new Google_Service_Sheets_ValueRange();
        $valueRange->setValues(array('values' => $value));
        // $valueRange->setValues(array(
        //     'values' => ["a", "b"]
        // ));
        $service->spreadsheets_values->append($sheet, $range, $valueRange, array('valueInputOption' => "RAW"));
    }
}

function generateSku($shop, $title)
{
    $shopChunks = explode('-', explode('.', $shop->myshopify_domain)[0]);
    $skuStart = strtoupper(implode('', array_map(function($chunk) {
        return $chunk[0];
    }, $shopChunks)));
    $words = preg_split("/\s+/", $title);
    $pt = '';
    foreach ($words as $word) {
        $pt .= $word[0];
    }
    $its = 0;
    $originalSku = strtolower(str_replace(array(' ', ','), '', $title));
    do {
        if ($its > 0) {
            $check = $originalSku.$its;
        } else {
            $check = $originalSku;
        }

        $its++;
    } while ($res = skuExists($check));

    return $check;
}

function skuExists($sku)
{
    try {
        $sku = Sku::where('sku', '=', $sku)->firstOrFail();
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $obj = new Sku();
        $obj->sku = $sku;
        $obj->save();
        return false;
    }
    return true;
}

function compressValues($results, $printType)
{
    $return = array();
    foreach ($results['variants'] as $result) {
        $temp = array();
        $temp['product_name'] = $results['product_name'];
        $temp['garment_name'] = '';
        $temp['product_fulfiller_code'] = $result['product_fulfiller_code'];
        $temp['garment_color'] = $result['garment_color'];
        $temp['product_sku'] = $result['product_sku'];
        $temp['shopify_product_admin_url'] = $results['shopify_product_admin_url'];
        switch ($printType) {
            case 'front_print';
                $temp['front_print_file_url'] = $results['front_print_file_url'];
                break;
            case 'back_print':
                $temp['back_print_file_url'] = $results['back_print_file_url'];
                break;
            case 'double_sided':
                $temp['front_print_file_url'] = $results['front_print_file_url'];
                $temp['back_print_file_url'] = $results['back_print_file_url'];
                break;
        }
        $temp['integration_status'] = '';
        $temp['date'] = date('m/d/Y');
        $return[] = array_values($temp);
    }
    return $return;
}
