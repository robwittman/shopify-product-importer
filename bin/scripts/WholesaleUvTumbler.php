<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createWholesaleUvTumbler(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $details = [
        '20oz' => '16.00',
        '30oz' => '17.50'
    ];
    global $s3;
    $images = array();
    $data = $queue->data;

    $image_data = getImages($s3, $queue->file_name);
    $post = $data['post'];
    foreach ($image_data as $name) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }

        $chunks = explode('/', $name);
        $fileName = $chunks[count($chunks) -1];

        $pieces = explode('-', basename($fileName, '.jpg'));
        $color = trim($pieces[1],'_');
        $size = trim($pieces[0],'_');
        if (strpos($size, '30') !== false) {
            $size = '30oz';
        } else {
            $size = '20oz';
        }
        $images[$size][$color] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Size"
        ),
        array(
            'name' => "Color"
        )
    );

    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($images as $size => $colors) {
        foreach ($colors as $color => $url) {
            $price = $details[$size];
            $varData = array(
                'title' => "{$color} / {$size}",
                'price' => $price,
                'option1' => $size,
                'option2' => str_replace('_', ' ', $color),
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
            );

            $varData['size'] = $size;
            $varData['color'] = str_replace('_', ' ', $color);
            $varData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $varData, $post, $data['file_name'], $queue);
            unset($varData['size']);
            unset($varData['color']);
            $product_data['variants'][] = $varData;
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
    $variantMap = array();
    $imageUpdate = array();

    foreach ($res->product->variants as $variant) {
        if(!isset($variantMap[$variant->option1])) {
            $variantMap[$variant->option1] = array();
        }
        if (!isset($variantMap[$variant->option1][$variant->option2])) {
            $variantMap[$variant->option1][$variant->option2] = array();
        }
        $variantMap[$variant->option1][$variant->option2][] = $variant->id;
    }
    foreach ($variantMap as $size => $colors) {
        foreach ($colors as $color => $ids) {
            $color = str_replace(' ', '_', $color);
            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$size][$color],
                'variant_ids' => $ids
            );
            $imageUpdate[] = $data;
        }
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        'product' => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);
}
