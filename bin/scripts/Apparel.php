<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function processQueue(Queue $queue, Shop $shop, Template $template, Setting $setting = null, Google_Client $client)
{
    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    // Ignore crew settings
    unset($matrix['Crew']);

    $images = array();
    $queue->started_at = date('Y-m-d H:i:s');
    $data = $queue->data;

    $image_data = getImages($s3, $queue->file_name);
    $post = $data['post'];
    $results = array(
        'product_name' => $post['product_title'],
        'shopify_product_admin_url' => null,
        'front_print_file_url' => $post['front_print_url'],
        'back_print_file_url' => $post['back_print_url'],
        'variants' => array()
    );
    foreach ($image_data as $name) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }

        $chunks = explode('/', $name);
        $garment = $chunks[2];
        if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
            $images[$garment]["Pink"] = $name;
        } else {
            if(!in_array($garment, array(
                'Hoodie','LS','Tanks','Tees'
            ))) {
                continue;
            }
            $color = explode("-", basename($name, ".jpg"))[1];
            $images[$garment][$color] = $name;
        }
    }
    if ($shop->google_access_token) {
        $client->setAccessToken($shop->google_access_token);
    }

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
        case 'importer-testing.myshopify.com':
            $matrix = json_decode(file_get_contents(DIR.'/src/wholesale.json'), true);
            if (!$matrix) {
                return "Unable to open matrix file";
            }
            unset($matrix['Crew']);
            break;
        case 'forged-blue.myshopify.com':
            $matrix = json_decode(file_get_contents(DIR.'/src/shield-republic.json'), true);
            if (!$matrix) {
                return "Unable to open matrix file";
            }
            unset($matrix['Crew']);
            break;
    }

    $sku = generateSku($shop, $post['product_title']);
    $results['product_name'] = $post['product_title'];
    $results['front_print_file_url'] = $post['front_print_url'];
    $results['back_print_file_url'] = $post['back_print_url'];

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $skuTemplate = getSkuTemplate($template, $setting, $queue);

    $product_data['options'] = array(
        array(
            'name' => "Size"
        ),
        array(
            'name' => "Color"
        ),
        array(
            'name' => "Style"
        )
    );

    $ignore = array(
        'Hoodie' => array(
            // 'Navy' => array('4XL'),
            // 'Royal' => array('4XL'),
            'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
            // 'Charcoal' => array('4XL'),
            // 'Black' => array('4XL'),
        ),
        'Tee' => array(
            // 'Black' => array('4XL'),
            // 'Navy' => array('4XL'),
            // 'Royal' => array('4XL'),
            'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
            // 'Charcoal' => array('4XL'),
        ),
        'Tank'=> array(
            'Pink' => array(
                'Small',
                'Medium',
                'Large',
                'XL',
                '2XL'
            )
        ),
        'Long Sleeve' => array(
            'Black'         => array('4XL'),
            'Navy'          => array('4XL'),
            'Royal Blue'    => array('4XL'),
            'Purple'        => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
            'Grey'          => array('4XL'),
        )
    );

    foreach($images as $garment => $img) {
        if($garment == 'Tanks') {
            $fulfillerCode = 'NL1533';
            $garment = 'Tank';
        } else if($garment == 'Tees') {
            $garment = 'Tee';
            $fulfillerCode = 'NL3600';

        } else if($garment == "LS") {
            $garment = 'Long Sleeve';
            $fulfillerCode = '2400';
        } else if($garment == 'Hoodie') {
            $fulfillerCode = '18500';
        }
        foreach ($img as $color => $src) {
            $garmentColor = $color;
            if($color == "Royal") {
                $color = "Royal Blue";
            } else if($color == "Charcoal") {
                if ($fulfillerCode == 'NL1533') {
                    $garmentColor = 'Dark Grey';
                } else {
                    $garmentColor = "Heavy Metal";
                }
                $color = "Grey";
            } else if($color == "Grey") {
                $color = "Charcoal";
            }
            $variantSku = getVariantSku($sku, $garment, $color);
            $results['variants'][] = array(
                'garment_name' => $garment,
                'product_fulfiller_code' => $fulfillerCode,
                'garment_color' => $garmentColor,
                'product_sku' => $variantSku,
            );
            $variantSettings = $matrix[$garment];
            foreach($variantSettings['sizes'] as $size => $sizeSettings) {
                if (isset($ignore[$garment]) &&
                isset($ignore[$garment][$color])) {
                    if(is_array($ignore[$garment][$color])) {
                      if(in_array($size, $ignore[$garment][$color])) {
                         continue;
                       }
                    } else {
                        continue;
                    }
                }
                $varData = array(
                    'title' => "{$garment} \/ {$size} \/ {$color}",
                    'price' => $sizeSettings['price'],
                    'grams' => $sizeSettings['grams'],
                    'option1' => getSku($size),
                    'option2' => $color,
                    'option3' => (($shop->myshopify_domain == 'forged-blue.myshopify.com' && $garment == 'Tank') ? 'Women\'s Tank' : $garment),
                    'weight' => $sizeSettings['weight'],
                    'weight_unit' => $sizeSettings['weight_unit'],
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => "deny"
                );
                $varData['size'] = $size;
                $varData['style'] = $garment;
                $varData['color'] = $color;
                $varData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $varData, $post, $data['file_name'], $queue);
                unset($varData['size']);
                unset($varData['color']);
                unset($varData['style']);
                if($garment == $post['default_product'] && $color == $post['default_color'] && $size == 'Small') {
                    array_unshift($product_data['variants'], $varData);
                } else {
                    $product_data['variants'][] = $varData;
                }
            }
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
    $results['shopify_product_admin_url'] = "https://{$shop->myshopify_domain}/admin/products/{$res->product->id}";
    $variantMap = array();
    $imageUpdate = array();

    foreach ($res->product->variants as $variant) {
        if(!isset($variantMap[$variant->option2])) {
            $variantMap[$variant->option2] = array();
        }
        if (!isset($variantMap[$variant->option2][$variant->option3])) {
            $variantMap[$variant->option2][$variant->option3] = array();
        }

        if($variant->option2 == "Royal Blue") {
            $variant->option2 = "Royal";
        } elseif ($variant->option2 == "Grey") {
            $variant->option2 = "Charcoal";
        }
        $variantMap[$variant->option2][$variant->option3][] = $variant->id;
    }

    foreach($variantMap as $color => $garments) {
        foreach($garments as $garment => $ids) {
            if($garment == "Tee") {
                $search = "Tees";
            } elseif($garment == "Long Sleeve") {
                $search = "LS";
            } elseif($garment == "Tank" || $garment == 'Women\'s Tank') {
                $search = "Tanks";
            } else {
                $search = $garment;
            }

            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$search][$color],
                'variant_ids' => $ids
            );
            if($garment == $post['default_product'] && $color == $post['default_color']) {
                $data['position'] = 1;
            }
            $imageUpdate[] = $data;
        }
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        'product' => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));
    if (isset($queue->log_to_google) && $queue->log_to_google && $shop->google_access_token) {
        logResults($client, $shop->google_sheet_slug, $post['print_type'], $results, $shop->id);
    } else {
        error_log("No google sync...");
    }
    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
