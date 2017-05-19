<?php

require_once __DIR__. '/../vendor/autoload.php';
require_once __DIR__. '/../src/common.php';

$products = array();
$created_at_min = date('c', strtotime('-2 weeks'));
$piperLou = (object) array(
    'myshopify_domain'  => 'piper-lou-collection.myshopify.com',
    'api_key'           => '427a3f8f0cb5b3b443bc7157aa6bcd1e',
    'password'          => 'befc75058d4cd38f7df70564226319e5'
);

$plcWholesale = (object) array(
    'myshopify_domain'  => 'plcwholesale.myshopify.com',
    'api_key'           => '9567edb38de3bd5f802945af5f1b6de1',
    'password'          => 'fc3a72c19b3d6fb5a78c5561394ab080'
);

/**
 * Find any products that don't exist, and create the base data on wholesale
 */
$params = array(
    'limit' => 250,
    'page' => 1,
    // 'created_at_min' => $created_at_min,
    'handle' => 'no-place-like-home-arkansas'
);
//
do {
    $res = callShopify($piperLou, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        if ($product->id != 10694350542) {
            continue;
        } else {
            error_log("Updating 10694350542");
        }
        $check = callShopify($plcWholesale, '/admin/products.json', 'GET', array(
            'handle' => $product->handle
        ));
        usleep(500000);
        if (empty($check->products)) {
            $result = uploadProduct($plcWholesale, $product);
            error_log($result->product->id);
        }
    }
    $params['page']++;
} while (count($res->products) == $params['limit']);
//
// $params = array(
//     'limit' => 250,
//     'page' => 1
// );
//
// do {
//     $res = callShopify($piperLou, '/admin/smart_collections.json', 'GET', $params);
//     foreach ($res->smart_collections as $collection) {
//         $check = callShopify($plcWholesale, '/admin/smart_collections.json', 'GET', array(
//             'handle' => $collection->handle
//         ));
//         usleep(500000);
//         if (empty($check->smart_collections)) {
//             $createRes = callShopify($plcWholesale, '/admin/smart_collections.json', 'POST', array(
//                 'smart_collection' => $collection
//             ));
//         }
//     }
// } while (count($res->smart_collections) == $params['limit']);
/**
 * Remap apparel variants to corresponding images
 */
$params = array(
    'limit' => 250,
    'page' => 1,
    'vendor' => 'BPP',
    'created_at_min' => $created_at_min
);
do {
    $res = callShopify($plcWholesale, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        $needsUpdate = false;
        foreach ($product->images as $image) {
            if (empty($image->variant_ids)) {
                $needsUpdate = true;
            }
        }

        if ($needsUpdate) {
            $update = parseApparel($product);
            if ($update) {
                $updateRes = callShopify($plcWholesale, '/admin/products/'.$product->id.'.json', 'PUT', array(
                    'product' => $update
                ));
            }
        }
    }
    $params['page']++;
} while(count($res->products) == $params['limit']);

/**
 * Remap tumbler variants to their respective images
 */
$params = array(
    'limit' => 250,
    'page' => 1,
    'vendor' => 'LDC',
    'created_at_min' => $created_at_min
);
do {
    $res = callShopify($plcWholesale, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        $needsUpdate = false;
        foreach ($product->images as $image) {
            if (empty($image->variant_ids)) {
                error_log($image->id);
                $needsUpdate = true;
            }
        }
        if ($needsUpdate) {
            $update = parseTumbler($product);
            callShopify($plcWholesale, '/admin/products/'.$product->id.'.json', 'PUT', array(
                'product' => $update
            ));
        }
    }
    $params['page']++;
} while(count($res->products) == $params['limit']);

/**
 * Remap UV tumblers
 */
$params = array(
    'limit' => 250,
    'page' => 1,
    'vendor' => 'Tx Tumbler'
    // 'created_at_min' => $created_at_min
);

do {
    $res = callShopify($plcWholesale, '/admin/products.json', 'GET', $params);
    error_log(count($res->products));
    foreach ($res->products as $product) {
        $needsUpdate = false;
        foreach ($product->images as $image) {
            if (empty($image->variant_ids)) {
                $needsUpdate = true;
                break;
            }
        }

        if ($needsUpdate) {
            $update = parseUvTumbler($product);
            callShopify($plcWholesale, '/admin/products/'.$product->id.'.json', 'PUT', array(
                'product' => $update
            ));
        }
    }
    $params['page']++;
} while (count($res->products) == $params['limit']);

/**
 * Take an old product and upload to new shop
 * @param  object $newShop
 * @param  object $product
 * @return object
 */
function uploadProduct($newShop, $product)
{
    $data = array(
        'body_html' => $product->body_html,
        'handle' => $product->handle,
        'images' => array(),
        'product_type' => $product->product_type,
        'published_at' => $product->published_at,
        'published_scope' => $product->published_scope,
        'tags' => $product->tags,
        'template_suffix' => $product->template_suffix,
        'title' => $product->title,
        'variants' => array(),
        'vendor' => $product->vendor,
        'options' => $product->options
    );
    foreach ($product->images as $image) {
        $imageData = array(
            'position' => $image->position,
            'src' => $image->src
        );
        $data['images'][] = $imageData;
    }

    foreach ($product->variants as $variant) {
        $variantData = array(
            'barcode' => $variant->barcode,
            'fulfillment_service' => $variant->fulfillment_service,
            'grams' => $variant->grams,
            'inventory_management' => $variant->inventory_management,
            'inventory_policy' => $variant->inventory_policy,
            'option1' => $variant->option1,
            'option2' => $variant->option2,
            'option3' => $variant->option3,
            'price' => $variant->price,
            'requires_shipping' => $variant->requires_shipping,
            'sku' => $variant->sku,
            'taxable' => $variant->taxable,
            'title' => $variant->title,
            'weight' => $variant->weight,
            'weight_unit' => $variant->weight_unit
        );
        if ($product->vendor == 'LDC' || $product->vendor == 'Tx Tumbler') {
            $variantData['price'] = 20.00;
        } else if ($product->vendor = 'BPP') {
            $variantData['price'] = getPrice($variantData);
        }
        $data['variants'][] = $variantData;
    }
    $res = callShopify($newShop, '/admin/products.json', 'POST', array(
        'product' => $data
    ));
    return $res;
}

/**
 * Parse apparal to get new images array
 * @param  object $product
 * @return array
 */
function parseApparel($product) {
    $update = array(
        'images' => []
    );
    $colorHandle = null;
    $styleHandle = null;
    $optionHandle = null;
    foreach ($product->options as $option) {
        if ($option->name == 'Color') {
            $colorHandle = 'option'.$option->position;
        }
        if ($option->name == 'Style') {
            $styleHandle = 'option'.$option->position;
        }
    }

    foreach ($product->images as $image) {
        foreach ($product->variants as $variant) {
            $colorSearch = $variant->{$colorHandle};
            if ($colorSearch == 'Grey') {
                $colorSearch = 'Charcoal';
            } else if($colorSearch == 'Royal Blue') {
                $colorSearch = 'Royal';
            }

            if (is_null($styleHandle)) {
                error_log('$styleHandle failed');
                return false;
            } else {
                $styleSearch = $variant->{$styleHandle};
            }

            if ($styleSearch == 'Tank') {
                $styleSearch = 'BellaFront';
            } else if ($styleSearch == 'Long Sleeve') {
                $styleSearch = 'LS';
            }

            if (strpos(strtolower($image->src), strtolower($colorSearch)) && strpos(strtolower($image->src), strtolower($styleSearch))) {
                if (!in_array($variant->id, $image->variant_ids)) {
                    $image->variant_ids[] = $variant->id;
                }
            }
        }

        $update['images'][] = array(
            'id' => $image->id,
            'variant_ids' => $image->variant_ids
        );
    }

    foreach ($update['images'] as $image) {
        if (empty($image['variant_ids'])) {
            return false;
        }
    }
    if (empty($update['images'])) {
        throw new \Exception("Image variant_ids empty!");
    }
    return $update;
}

/**
 * Parse a tumbler product, and get image / variant map
 * @param  object $tumbler
 * @return array
 */
function parseTumbler($tumbler)
{
    $optionHandle = 'option2';
    $update = array(
        'images' => array()
    );

    foreach ($tumbler->options as $option) {
        if ($option->name == 'Color') {
            $optionHandle = 'option'.$option->position;
        }
    }
    foreach ($tumbler->images as $image) {
        if (strpos($image->src, "Steel") !== false) {
            foreach ($tumbler->variants as $variant) {
                if ($variant->{$optionHandle} == "Grey" && !in_array($variant->id, $image->variant_ids)) {
                    $image->variant_ids[] = $variant->id;
                }
            }
        } else {
            foreach ($tumbler->variants as $variant) {
                if (strpos($image->src, $variant->{$optionHandle}) !== false && !in_array($variant->id, $image->variant_ids)) {
                    $image->variant_ids[] = $variant->id;
                }
            }
        }
        $update['images'][] = array(
            'id' => $image->id,
            'variant_ids' => $image->variant_ids
        );
    }

    if (empty($update['images'])) {
        throw new \Exception("Images empty!!!!");
    }
    return $update;
}

function parseUvTumbler($data)
{
    $update = array(
        'images' => array()
    );
    $colorHandle = null;
    $sizeHandle = null;
    foreach ($data->options as $option) {
        if ($option->name == 'Color') {
            $colorHandle = 'option'.$option->position;
        } else if ($option->name == 'Size') {
            $sizeHandle = 'option'.$option->position;
        }
    }
    if (is_null($colorHandle) || is_null($sizeHandle)) {
        throw new \Exception("Failed mapping images for Tx Tumbler. No size or color option!");
    }

    foreach ($data->images as $image) {
        $fileName = getFileNameFromUrl($image->src);
        $size = null;
        $color = null;
        foreach ($data->variants as $variant) {
            $variantColor = str_replace(' ', '_', $variant->{$colorHandle});
            if ($variant->{$colorHandle} == "Grey") {
                $variant->{$colorHandle} = "Stainless";
            }
            // Since Teal will match Teal and Metallic Teal, we skip metallic hits
            // when searching for just Teal
            if ($variantColor == 'Teal') {
                if (strpos($fileName, 'Metallic') !== false) {
                    continue;
                }
            }
            $variantSize = str_replace('oz', '', $variant->{$sizeHandle});
            if (strpos($fileName, $variantColor) !== false && substr($fileName, 0, 2) == $variantSize) {
                $image->variant_ids[] = $variant->id;
            }
        }
        $update['images'][] = array(
            'id' => $image->id,
            'variant_ids' => $image->variant_ids
        );
    }
    return $update;
}

function getFileNameFromUrl($src)
{
    $baseName = basename($src);
    return pathinfo($baseName, PATHINFO_FILENAME);
}

/**
 * Parse a product and get the new wholesale price
 * @param  array $data
 * @return float
 */
function getPrice($data)
{
    $garments = array(
        'Long Sleeve' => 12.00,
        'Hoodie' => 20.00,
        'Tank' => 10.00,
        'Tee' => 10.00
    );
    $modifiers = array(
        '2XL' => 2.00,
        '3XL' => 4.00,
        '4XL' => 4.00
    );

    $options = array(
        $data['option1'],
        $data['option2'],
        $data['option3']
    );

    $price = $data['price'];
    foreach ($options as $option) {
        if (in_array($option, array_keys($garments))) {
            $price = $garments[$option];
        }
    }

    foreach ($options as $option) {
        if (in_array($option, array_keys($modifiers))) {
            $price += $modifiers[$option];
        }
    }
    return $price;
}
