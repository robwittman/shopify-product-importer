<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createMultiHats(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '15.00';
    $sizes = [];
    switch ($queue->sub_template_id) {
        case 'beach_hat':
            $price = '14.95';
        break;
        case 'new_era_flex_fit':
            $price = '13.00';
        break;
        case 'snap_back':
            $price = '11.00';
        break;
        case 'beanie':
            $price = '10.00';
            $sizes = ['S/M', 'M/L', 'L/XL'];
        break;
    }
    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $chunks = explode('/', $productData);
        $color = $chunks[count($chunks) - 1];
        if (strpos($productData, '-') !== false) {
            $color = explode('_-_', $productData)[1];
        }
        $imageUrls[$color] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Color"
        )
    );
    if (!empty($sizes)) {
        $product_data['options'][] = array('name' => "Size");
    }
    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($imageUrls as $color => $url) {
        if (empty($sizes)) {
            $color = str_replace('_', ' ', $color);
            $variantData = array(
                'title' => $color,
                'price' => $price,
                'option1' => $color,
                'weight' => '5.0',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
            $variantData['color'] = $color;
            $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
            unset($variantData['color']);
            if ($color == 'Black and White') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        } else {

            foreach ($sizes as $size) {
                $color = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => $color,
                    'price' => $price,
                    'option1' => $color,
                    'option2' => $size,
                    'weight' => '5.0',
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['size'] = $size;
                $variantData['color'] = $color;
                $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
                unset($variantData['color']);
                unset($variantData['size']);
                if ($color == 'Black and White') {
                    $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
                } else {
                    $product_data['variants'][] = $variantData;
                }
            }
        }
    }
    echo json_encode($product_data, JSON_PRETTY_PRINT);
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    if (empty($sizes)) {
        foreach ($res->product->variants as $variant) {
            $image = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[str_replace(' ', '_', $variant->option1)]}",
                'variant_ids' => [$variant->id]
            );
            $imageUpdate[] = $image;
        };
    } else {
        $variantMap = [];
        foreach ($res->product->variants as $variant) {
            error_log($variant->option1);
            if (!isset($variantMap[$variant->option1])) {
                $variantMap[$variant->option1] = [];
            }
            $variantMap[$variant->option1][] = $variant->id;
        };
        error_log(json_encode($variantMap));
        foreach ($variantMap as $color => $variants) {
            $imageUpdate[] = [
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[str_replace(' ', '_', $color)]}",
                'variant_ids' => $variants
            ];
        }
        error_log(json_encode($imageUpdate));
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);
}
