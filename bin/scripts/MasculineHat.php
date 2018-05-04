<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createMasculineHats(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '29.99';
    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.50';
            break;
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = preg_replace('%([a-z])([A-Z])%', '\1-\2', $specs[1]);
        $imageUrls[trim($color, '_')] = $name;
    }

    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Color"
        ),
        array(
            'name' => "Style"
        )
    );
    $skuTemplate = getSkuTemplate($template, $setting, $queue);

    foreach ($imageUrls as $color => $image) {
        $variantData = array(
            'title' => "Trucker Hat / ".$color,
            'price' => $price,
            'option1' => "Trucker Hat",
            'option2' => str_replace('_', ' ', $color),
            'weight' => '5.0',
            'weight_unit' => 'oz',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny'
        );

        $variantData['color'] = $color;
        $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
        unset($variantData['color']);

        if ($color == 'Navy' && $style == 'Hat') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $color = str_replace(' ', '_', $variant->option2);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
            'variant_ids' => [$variant->id]
        );
        $imageUpdate[] = $image;
    };
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);
}
