<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createHats(Queue $queue, Shop $shop, Template $template, Setting $setting)
{
    $price = '29.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '14.95';
            break;
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $style = $specs[0];
        $color = $specs[1];
        $imageUrls[$style][$color] = $name;
    }

    $product_data = getProductSettings($shop, $post, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Color"
        ),
        array(
            'name' => "Style"
        )
    );
    $store_name = '';
    switch ($shop->myshopify_domain) {
        case 'piper-lou-collection.myshopify.com':
        case 'plcwholesale.myshopify.com':
            $store_name = 'Piper Lou - ';
            break;
    }
    foreach ($imageUrls as $style => $colors) {
        foreach ($colors as $color => $image) {
            $variantData = array(
                'title' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat").' / '.$color,
                'price' => $price,
                'option1' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat"),
                'option2' => str_replace('_', ' ', $color),
                'weight' => '5.0',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => "{$store_name}Hat"
            );
            if ($color == 'Navy' && $style == 'Hat') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $style = $variant->option1 == 'Trucker Hat' ? "Hat" : "TwillHat";
        $color = str_replace(' ', '_', $variant->option2);
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$style][$color]}",
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

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
