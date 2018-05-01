<?php

use App\Result\FrontPrint;
use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createWholesaleApparel(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/new_wholesale.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    // Ignore crew settings
    unset($matrix['Crew']);
    $images = array();
    $queue->started_at = date('Y-m-d H:i:s');
    $data = $queue->data;

    $image_data = getImages($s3, $queue->file_name);
    $designId = null;

    $post = $data['post'];
    $details = $matrix[$queue->sub_template_id];
    foreach ($image_data as $name) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }


        $chunks = explode('/', $name);
        $fileName = $chunks[count($chunks) -1];
        $pieces = explode('-', basename($fileName, '.jpg'));
        $images[str_replace('_', ' ', trim($pieces[1], '_'))] = $name;
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
    foreach ($images as $color => $src) {
        foreach($details['sizes'] as $size) {
            $varData = array(
                'title' => "{$size} \/ {$color}",
                'price' => ($size == '2XL') ? $details['price'] + $details['modifier'] : $details['price'],
                'option1' => $size,
                'option2' => $color,
                'weight' => $details['weight'],
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => "deny"
            );

            $variantData['size'] = $size;
            $variantData['color'] = $color;
            $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
            unset($variantData['size']);
            unset($variantData['color']);
            if($color == $post['default_color'] && $size == 'S') {
                array_unshift($product_data['variants'], $varData);
            } else {
                $product_data['variants'][] = $varData;
            }
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
    $variantMap = array();
    $imageUpdate = array();

    foreach ($res->product->variants as $variant) {
        if(!isset($variantMap[$variant->option2])) {
            $variantMap[$variant->option2] = array();
        }
        $variantMap[$variant->option2][] = $variant->id;
    }
    foreach($variantMap as $color => $ids) {
        $data = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$color],
            'variant_ids' => $ids
        );
        if($color == $post['default_color']) {
            $data['position'] = 1;
        }
        $imageUpdate[] = $data;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        'product' => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));
    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
