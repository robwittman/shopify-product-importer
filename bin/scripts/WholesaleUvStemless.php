<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createWholesaleUvStemless(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '15.00';
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
    $product_data['product_type'] = 'Stemless Wine Cup';
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
            'option1' => str_replace('_', ' ', $color),
            'weight' => '10',
            'weight_unit' => 'oz',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny'
        );
        $variantData['color'] = str_replace('_', ' ', $color);

        $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
        unset($variantData['color']);
        if ($color == 'Black') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $color = str_replace(' ', '_', $variant->option1);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => array($variant->id)
        );
        $imageUpdate[] = $image;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);
}
