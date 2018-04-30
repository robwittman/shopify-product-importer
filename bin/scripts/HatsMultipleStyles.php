<?php

use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createMultiHats(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $price = '15.00';
    if ($queue->sub_template_id === 'beach_hat') {
        $price = '14.95';
    }
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $chunks = explode('/', $productData);
        $color = $chunks[count($chunks) - 1];
        $imageUrls[$color] = $name;
    }
    $product_data = getProductSettings($shop, $queue, $template, $setting);
    $product_data['options'] = array(
        array(
            'name' => "Color"
        )
    );
    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($imageUrls as $color => $url) {
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
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $variantMap = array();
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[str_replace(' ', '_', $variant->option1)]}",
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
