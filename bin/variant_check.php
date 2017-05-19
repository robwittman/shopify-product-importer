<?php

require_once __DIR__. '/../vendor/autoload.php';
require_once __DIR__. '/../src/common.php';

$shop = array(
    'myshopify_domain' => 'plcwholesale.myshopify.com',
    'api_key' => 'f4f87b0ae7622ba4d1bb55621b483ace',
    'password' => 'c24c59be44d10798de2abf2696a4a20d',
    'shared_secret' => '80824fd52a01ada88154b2b3d9eba763'
);
$shop = (object) $shop;

$params = array(
    'limit' => 250,
    'page' => 1
);

do {
    $res = callShopify($shop, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        if (in_array('jewelry', explode(',', strtolower($product->tags)))) {
            foreach ($product->variants as $variant) {
                if ($variant->price != 15.00) {
                    var_dump(callShopify($shop, '/admin/variants/'.$variant->id.'.json', 'PUT', array(
                        'variant' => array(
                            'price' => 15.00
                        )
                    )));
                    sleep(1);
                }
            }
        }
    }
    $params['page']++;
} while(count($res->products) == $params['limit']);
// $params = array(
//     'limit' => 250,
//     'page' => 1,
// );
//
// $garments = array(
//     'Long Sleeve' => 12.00,
//     'Hoodie' => 20.00,
//     'Tank' => 10.00,
//     'Tee' => 10.00
// );
// $modifiers = array(
//     '2XL' => 2.00,
//     '3XL' => 4.00,
//     '4XL' => 4.00
// );
//
// do {
//     $res = callShopify($shop, '/admin/products.json', 'GET', $params);
//     foreach ($res->products as $product) {
//         if (in_array('grey', explode(',',strtolower($product->tags)))) {
//             foreach ($product->variants as $variant) {
//                 if (strpos($variant->sku, 'Tank') === false) {
//                     continue;
//                 }
//                 if ($variant->price > 14.00) {
//                     $price = $garments['Tank'];
//                     foreach ($modifiers as $size => $inc) {
//                         if (
//                             $variant->option1 == $size ||
//                             $variant->option2 == $size ||
//                             $variant->option3 == $size
//                         ) {
//                             $price += $inc;
//                         }
//                     }
//                     var_dump(callShopify($shop, '/admin/variants/'.$variant->id.'.json', 'PUT', array(
//                         'variant' => array(
//                             'price' => $price
//                         )
//                     )));
//                     usleep(500000);
//                 }
//             }
//         }
//     }
//     $params['page']++;
// } while (count($res->products) == $params['limit']);
// echo json_encode($res->products);
// echo count($res->products);
//
// $params = array(
//     'limit' => 250,
//     'page' => 1,
//     'vendor' => "LDC"
// );
//
// do {
//     $res = callShopify($shop, '/admin/products.json', 'GET', $params);
//     foreach ($res->products as $product) {
//         $needsUpdate = false;
//         $update = array('product' => array('variants' => array()));
//         foreach ($product->variants as $variant) {
//             if(!is_null($variant->compare_at_price)) {
//                 $update['product']['variants'][] = [
//                     'id' => $variant->id,
//                     'compare_at_price' => null
//                 ];
//             }
//         }
//
//         if (!empty($update['product']['variants'])) {
//             callShopify($shop, '/admin/products/'.$product->id.'.json', 'PUT', $update);
//             error_log($product->id.' updated');
//         }
//     }
//     $params['page']++;
// } while (count($res->products) == $params['limit']);
//
// $params = array(
//     'limit' => 250,
//     'page' => 1,
//     'vendor' => 'BPP'
// );
// $count = 0;
// $vendors = [];
// do {
//     $res = callShopify($shop, '/admin/products.json', 'GET', $params);
//     foreach ($res->products as $product) {
//         error_log($product->id);
//         foreach ($product->images as $image) {
//             if (empty($image->variant_ids)) {
//                 $count++;
//                 if (!isset($vendors[$product->vendor])) {
//                     $vendors[$product->vendor] = 0;
//                 }
//                 $vendors[$product->vendor]++;
//                 break ;
//             }
//         }
//     }
//     $params['page']++;
// } while(count($res->products) == $params['limit']);
//
// echo "COUNT :: {$count}\n";
// print_r($vendors);
//
// $params = array(
//     'limit' => 250,
//     'page' => 1,
//     'vendor' => 'BPP'
// );
// do {
//     $res = callShopify($shop, '/admin/products.json', 'GET', $params);
//     foreach ($res->products as $product) {
//         $needsUpdate = false;
//         foreach ($product->images as $image) {
//             if (empty($image->variant_ids)) {
//                 $needsUpdate = true;
//             }
//         }
//
//         if ($needsUpdate) {
//             $update = parseApparel($product);
//             if ($update) {
//                 $updateRes = callShopify($shop, '/admin/products/'.$product->id.'.json', 'PUT', array(
//                     'product' => $update
//                 ));
//                 error_log("Updated {$updateRes->product->id}");
//             } else {
//                 error_log("Update failed");
//             }
//         } else {
//             error_log("Already updated");
//         }
//     }
//     $params['page']++;
// } while(count($res->products) == $params['limit']);
//
// function parseApparel($product) {
//     $update = array(
//         'images' => []
//     );
//     $colorHandle = null;
//     $styleHandle = null;
//     $optionHandle = null;
//     foreach ($product->options as $option) {
//         if ($option->name == 'Color') {
//             $colorHandle = 'option'.$option->position;
//         }
//         if ($option->name == 'Style') {
//             $styleHandle = 'option'.$option->position;
//         }
//     }
//
//     foreach ($product->images as $image) {
//         foreach ($product->variants as $variant) {
//             $colorSearch = $variant->{$colorHandle};
//             if ($colorSearch == 'Grey') {
//                 $colorSearch = 'Charcoal';
//             } else if($colorSearch == 'Royal Blue') {
//                 $colorSearch = 'Royal';
//             }
//
//             if (is_null($styleHandle)) {
//                 error_log('$styleHandle failed');
//                 return false;
//             } else {
//                 $styleSearch = $variant->{$styleHandle};
//             }
//
//             if ($styleSearch == 'Tank') {
//                 $styleSearch = 'BellaFront';
//             } else if ($styleSearch == 'Long Sleeve') {
//                 $styleSearch = 'LS';
//             }
//
//             if (strpos(strtolower($image->src), strtolower($colorSearch)) && strpos(strtolower($image->src), strtolower($styleSearch))) {
//                 if (!in_array($variant->id, $image->variant_ids)) {
//                     $image->variant_ids[] = $variant->id;
//                 }
//             }
//         }
//
//         $update['images'][] = array(
//             'id' => $image->id,
//             'variant_ids' => $image->variant_ids
//         );
//     }
//
//     foreach ($update['images'] as $image) {
//         if (empty($image['variant_ids'])) {
//             return false;
//         }
//     }
//     if (empty($update['images'])) {
//         throw new \Exception("Image variant_ids empty!");
//     }
//     return $update;
// }

// $params = array(
//     'limit' => 250,
//     'page' => 1,
//     'vendor' => 'LDC'
// );
// do {
//     $res = callShopify($shop, '/admin/products.json', 'GET', $params);
//     foreach ($res->products as $product) {
//         $needsUpdate = false;
//         foreach ($product->images as $image) {
//             if (empty($image->variant_ids)) {
//                 error_log($image->id);
//                 $needsUpdate = true;
//             }
//         }
//         if ($needsUpdate) {
//             $update = parseTumbler($product);
//             callShopify($shop, '/admin/products/'.$product->id.'.json', 'PUT', array(
//                 'product' => $update
//             ));
//         } else {
//             error_log("Already updated");
//         }
//     }
//     $params['page']++;
// } while(count($res->products) == $params['limit']);
//
// function parseTumbler($tumbler)
// {
//     $optionHandle = 'option2';
//     $update = array(
//         'images' => array()
//     );
//
//     foreach ($tumbler->options as $option) {
//         if ($option->name == 'Color') {
//             $optionHandle = 'option'.$option->position;
//         }
//     }
//     foreach ($tumbler->images as $image) {
//         if (strpos($image->src, "Steel") !== false) {
//             foreach ($tumbler->variants as $variant) {
//                 if ($variant->{$optionHandle} == "Grey" && !in_array($variant->id, $image->variant_ids)) {
//                     $image->variant_ids[] = $variant->id;
//                 }
//             }
//         } else {
//             foreach ($tumbler->variants as $variant) {
//                 if (strpos($image->src, $variant->{$optionHandle}) !== false && !in_array($variant->id, $image->variant_ids)) {
//                     $image->variant_ids[] = $variant->id;
//                 }
//             }
//         }
//         $update['images'][] = array(
//             'id' => $image->id,
//             'variant_ids' => $image->variant_ids
//         );
//     }
//
//     var_dump($tumbler);
//     if (empty($update['images'])) {
//         throw new \Exception("Images empty!!!!");
//     }
//     return $update;
// }
