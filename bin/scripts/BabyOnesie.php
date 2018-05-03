<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createBabyOnesie(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
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
        )
    );

    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($imageUrls as $color => $url) {
        $variantData = array(
            'title' => $color,
            'price' => $price,
            'option1' => str_replace('_', ' ',  $color),
            'weight' => '10',
            'weight_unit' => 'oz',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny'
        );
    }
    $variantData['color'] = str_replace('_', ' ', $color);
    $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
    unset($variantData['color']);

    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = [];
    foreach ($res->product->variants as $variant) {
        $color = str_replace(' ', '_', $variant->option1);
        $image = [
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => [$variant->id]
        ];
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", [
        'product' => [
            'id' => $res->product->id,
            'images' => $imageUpdate
        ]
    ]);
    $queue->finish([$res->product->id]);
    return [$res->product->id];
}