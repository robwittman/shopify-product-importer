<?php

use App\Result\FrontPrint;

function createWholesaleApparel($queue)
{
    $html = '';
    $vendor = 'Edge Promotions';
    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/new_wholesale.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    // Ignore crew settings
    unset($matrix['Crew']);
    $image_data = array();
    $images = array();
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    $image_data = getImages($s3, $queue->file_name);
    $designId = null;

    $post = $data['post'];
    $details = $matrix[$post['wholesale_product_type']];
    $shop = \App\Model\Shop::find($queue->shop);
    foreach ($image_data as $name) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }

        if (is_null($designId)) {
            $designId = getDesignIdFromFilename($name);
        }

        $chunks = explode('/', $name);
        $fileName = $chunks[count($chunks) -1];
        $pieces = explode('-', basename($fileName, '.jpg'));
        $images[str_replace('_', ' ', trim($pieces[1], '_'))] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags = implode(',', $tags);
    $product_data = array(
        'title'         => $post['product_title'],
        'body_html'     => $html,
        'tags'          => $tags,
        'vendor'        => $vendor,
        'product_type'  => 'Apparel',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
        ),
        'variants'      => array(),
        'images'        => array()
    );

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
                'inventory_policy' => "deny",
                'sku' => "PL - {$designId} - {$details['skuModifier']} - {$size} - {$color}"
            );
            if ($post['wholesale_product_type'] == 'front_back_unisex_tee') {
                $varData['sku'] = 'FBP - '.$varData['sku'];
            }

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
