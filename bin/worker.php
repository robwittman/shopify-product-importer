<?php
require_once 'bootstrap.php';

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

foreach (glob(DIR."/bin/scripts/*.php") as $file) {
    include_once ($file);
}

while (true) {
    $queue = Queue::where('status', Queue::PENDING)
        ->orderBy('created_at', 'asc')
        ->first();
    if (!$queue) {
        sleep(5);
    } else {
        try {
            $queue->start();
            $data = $queue->data;
            $template = Template::where('handle', $data['post']['template'])->first();
            if (is_null($template)) {
                throw new \Exception("Unsupported template '{$data['post']['template']}'");
            }
            $setting = Setting::where(array(
                'template_id' => $template->id,
                'shop_id' => $queue->shop
            ))->first();
            $shop = Shop::find($queue->shop);
            switch ($queue->template) {
                case 'wholesale_apparel':
                    $res = createWholesaleApparel($queue, $shop, $template, $setting);
                    break;
                case 'wholesale_tumbler':
                    $res = createWholesaleTumbler($queue, $shop, $template, $setting);
                    break;
                case 'hats':
                    $res = createHats($queue, $shop, $template, $setting);
                    break;
                case 'stemless':
                    $res = createStemless($queue, $shop, $template, $setting);
                    break;
                case 'single_product':
                    $res = processQueue($queue, $shop, $template, $setting, $client);
                    break;
                case 'drinkware':
                    $res = createDrinkware($queue, $shop, $template, $setting);
                    break;
                case 'uv_drinkware':
                    $res = createUvDrinkware($queue, $shop, $template, $setting);
                    break;
                case 'donation_uv_tumbler':
                    $res = createDonationUVTumbler($queue, $shop, $template, $setting);
                    break;
                case 'flasks':
                    $res = createFlasks($queue, $shop, $template, $setting);
                    break;
                case 'baby_body_suit':
                    $res = createBabyBodySuit($queue, $shop, $template, $setting, $client);
                    break;
                case 'raglans':
                    $res = createRaglans($queue, $shop, $template, $setting, $client);
                    break;
                case 'front_back_pocket':
                    $res = createFrontBackPocket($queue, $shop, $template, $setting, $client);
                    break;
                case 'christmas':
                    $res = createChristmas($queue, $shop, $template, $setting, $client);
                    break;
                case 'hats_masculine':
                    $res = createMasculineHats($queue, $shop, $template, $setting);
                    break;
                case 'grey_collection':
                    $res = createGreyCollection($queue, $client);
                    break;
                default:
                    throw new \Exception("Invalid template {$data['post']['template']} provided");
            }
            $queue->finish($res);
            error_log("Queue {$queue->id} finished. ".json_encode($res));
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

function getSkuFromFileName($fileName)
{
    $parts = explode('-', str_replace('.zip', '', $fileName));
    if (count($parts) == 1) {
        return $parts[0];
    }
    return implode(array($parts[0], $parts[1]), '-');
}

function getDesignIdFromFilename($fileName)
{
    if (is_null($designId)) {
        $chunks = explode('/', $name);
        $fileName = $chunks[count($chunks) - 2];
        $pieces = explode('_-_', $fileName);
        return $pieces[1];
    }
}

function getProductSettings(Shop $shop, Queue $queue, Template $template, Setting $setting = null)
{
    $tags = implode(',', array_merge(
        str_getcsv($queue->tags),
        str_getcsv($template->tags),
        str_getcsv($setting->tags)
    ));
    return array(
        'title' => $queue->product_title,
        'body_html' => $queue->description ?: $setting->description ?: $shop->description ?: $template->description,
        'tags' => $tags,
        'product_type' => $queue->product_type ?: $setting->product_type ?: $template->product_Type,
        'vendor' => $queue->vendor ?: $setting->vendor ?: $template->vendor,
        'variants' => array(),
        'images' => array()
    );
}

function generateLiquidSku($skuTemplate, $product, Shop $shop, $variant, $post, $fileName)
{
    $template = new \Liquid\Template();
    $template->parse($skuTemplate);
    $sku = $template->render(array(
        'product' => $product,
        'shop' => $shop,
        'variant' => $variant,
        'file' => str_replace('.zip', '', $fileName),
        'data' => $post
    ));
    return $sku;
}

function getSkuTemplate(Template $template, Setting $setting = null, Queue $queue)
{
    return $queue->sku ?: $estting->sku_template ?: $template->sku_template;
}
