<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createBabyBodySuit(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '14.99';
    $sizes = array(
        'Newborn',
        '6 Months',
        '12 Months',
        '18 Months',
        '24 Months'
    );

    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];

    if ($shop->myshopify_domain === 'plcwholesale.myshopify.com') {
        $price = '8.50';
    }
    foreach ($image_data as $name) {
        $imageUrls[] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Size"
        ),
        array(
            'name' => "Color",

        )
    );
    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($sizes as $size) {
        $variantData = array(
            'title' => $size .' / White',
            'price' => $price,
            'option1' => $size,
            'option2' => 'White',
            'weight' => '0.6',
            'weight_unit' => 'lb',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny'
        );
        $variantData['size'] = $size;
        $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
        unset($variantData['size']);
        // 'sku' => 'Piper Lou - Baby Body Suit - White - '.$size
        $product_data['variants'][] = $variantData;
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantIds = array();
    foreach ($res->product->variants as $variant) {
        $variantIds[] = $variant->id;
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => array(
                array(
                    'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[0],
                    'variant_ids' => $variantIds
                )
            )
        )
    ));
    return array($res->product->id);
}
