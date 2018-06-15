<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createMultiHats(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    switch ($shop->myshopify_domain) {
        case 'forged-blue.myshopify.com':
        case 'importer-testing.myshopify.com':
            $prices = [
              'beach_hat' => '0.00',
              'new_era_flex_fit' => '32.99',
              'snap_back' => '29.99',
              'beanie' => '24.99'
            ];
            break;
        default:
            $prices = [
                'beach_hat' => '14.95',
                'new_era_flex_fit' => '17.00',
                'snap_back' => '11.00',
                'beanie' => '14.00'
            ];
    }
    $sizes = [];
    $price = $prices[$queue->sub_template_id];
    if ($queue->sub_template_id == 'new_era_flex_fit') {
        $sizes = ['S/M', 'M/L', 'L/XL'];
    }
    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    $rearImages = [];

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $chunks = explode('/', $productData);
        $color = $chunks[count($chunks) - 1];
        if (strpos($name, 'Back') !== false) {
            $rearImages[] = $name;
            continue;
        }
        if (strpos($productData, '-') !== false) {
            $color = explode('_-_', $productData)[1];
        }
        $imageUrls[$color] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_type = 'Headware';
    if ($queue->sub_template_id == 'beach_hat') {
        $product_type = 'Beach Hat';
    } else if ($queue->sub_template_id == 'beanie') {
        $product_type = 'Beanie';
    } else if ($queue->sub_template_id == 'snap_back') {
        $product_type = 'Hat';
    } else if ($queue->sub_template_id == 'new_era_flex_fit') {
        $product_type = 'Hat';
    }
    $product_data['product_type'] = $product_type;
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
            if (!isset($variantMap[$variant->option1])) {
                $variantMap[$variant->option1] = [];
            }
            $variantMap[$variant->option1][] = $variant->id;
        };
        foreach ($variantMap as $color => $variants) {
            $imageUpdate[] = [
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[str_replace(' ', '_', $color)]}",
                'variant_ids' => $variants
            ];
        }
    }

    foreach ($rearImages as $image) {
        $imageUpdate[] = [
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$image}"
        ];
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);
}
