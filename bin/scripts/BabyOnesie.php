<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createBabyOnesie(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '11.00';
    $sizes = [
        'NB',
        '6M',
        '12M',
        '18M',
        '24M'
    ];
    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['product_type'] = 'Baby Onesie';
    $product_data['options'] = array(
        array(
            'name' => "Color"
        ),
        array(
            'name' => "Size"
        )
    );

    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($imageUrls as $color => $url) {
        foreach ($sizes as $size) {
            $variantData = array(
                'title' => $color,
                'price' => $price,
                'option1' => str_replace('_', ' ',  $color),
                'option2' => $size,
                'weight' => '10',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
            $variantData['color'] = str_replace('_', ' ', $color);
            $variantData['size'] = $size;
            $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
            unset($variantData['color']);
            unset($variantData['size']);
            $product_data['variants'][] = $variantData;
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = [];
    $imageUpdate = [];
    foreach ($res->product->variants as $variant) {
        if (!isset($variantMap[$variant->option1])) {
            $variantMap[$variant->option1] = [];
        }
        $variantMap[$variant->option1][] = $variant->id;
    }
    foreach ($variantMap as $color => $variants) {
        $color = str_replace(' ', '_', $color);
        $data = [
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => $variants
        ];
        $imageUpdate[] = $data;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", [
        'product' => [
            'id' => $res->product->id,
            'images' => $imageUpdate
        ]
    ]);
    return [$res->product->id];
}