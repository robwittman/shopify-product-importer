<?php

use App\Result\FrontPrint;

function createWholesaleApparel($queue, Google_Client $client) {

    $vendor = 'Edge Promotion';
    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
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
    var_dump($image_data);
    $post = $data['post'];
    $type = null;
    switch ($data['product']) {

    }

    $shop = \App\Model\Shop::find($queue->shop);
    foreach ($image_data as $name) {
        if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
            continue;
        }

        $chunks = explode('/', $name);
        if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
            $images[$garment]["Pink"] = $name;
        } else {
            $garment = $chunks[2];
            if(!in_array($garment, array(
                'Hoodie','LS','Tanks','Tees'
            ))) {
                continue;
            }
            $color = explode("-", basename($name, ".jpg"))[1];
            $images[$garment][$color] = $name;
        }
    }
    if ($shop->google_access_token) {
        $client->setAccessToken($shop->google_access_token);
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'apparel';
    $tags = implode(',', $tags);
    $product_data = array(
        'title'         => $post['product_title'],
        'body_html'     => $html,
        'tags'          => $tags,
        'vendor'        => $vendor,
        'product_type'  => $post['product_type'],
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

    foreach ($img as $color => $src) {
        $variantSettings = $matrix[$garment];
        foreach($variantSettings['sizes'] as $size => $sizeSettings) {
            $varData = array(
                'title' => "{$garment} \/ {$size} \/ {$color}",
                'price' => $sizeSettings['price'],
                'grams' => $sizeSettings['grams'],
                'option1' => getSku($size),
                'option2' => $color,
                'option3' => $garment,
                'weight' => $sizeSettings['weight'],
                'weight_unit' => $sizeSettings['weight_unit'],
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => "deny",
                'sku' => $variantSku
            );

            if($garment == $post['default_product'] && $color == $post['default_color'] && $size == 'Small') {
                array_unshift($product_data['variants'], $varData);
            } else {
                $product_data['variants'][] = $varData;
            }
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
    $results['shopify_product_admin_url'] = "https://{$shop->myshopify_domain}/admin/products/{$res->product->id}";
    $variantMap = array();
    $imageUpdate = array();

    foreach ($res->product->variants as $variant) {
        if(!isset($variantMap[$variant->option2])) {
            $variantMap[$variant->option2] = array();
        }
        if (!isset($variantMap[$variant->option2][$variant->option3])) {
            $variantMap[$variant->option2][$variant->option3] = array();
        }

        if($variant->option2 == "Royal Blue") {
            $variant->option2 = "Royal";
        } elseif ($variant->option2 == "Grey") {
            $variant->option2 = "Charcoal";
        }
        $variantMap[$variant->option2][$variant->option3][] = $variant->id;
    }

    foreach($variantMap as $color => $garments) {
        foreach($garments as $garment => $ids) {
            if($garment == "Tee") {
                $search = "Tees";
            } elseif($garment == "Long Sleeve") {
                $search = "LS";
            } elseif($garment == "Tank") {
                $search = "Tanks";
            } else {
                $search = $garment;
            }

            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$search][$color],
                'variant_ids' => $ids
            );
            if($garment == $post['default_product'] && $color == $post['default_color']) {
                $data['position'] = 1;
            }
            $imageUpdate[] = $data;
        }
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        'product' => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));
    if (isset($queue->log_to_google) && $queue->log_to_google && $shop->google_access_token) {
        logResults($client, $shop->google_sheet_slug, $post['print_type'], $results);
    } else {
        error_log("No google sync...");
    }
    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
