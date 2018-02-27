<?php

function createDrinkware($queue)
{
    $prices = array(
        '30' => '29.99',
        '20' => '24.99'
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($queue->shop);
    $image_data = array_reverse(getImages($s3, $queue->file_name));
    $designId = null;
    error_log(json_encode($image_data, JSON_PRETTY_PRINT));
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                '30' => '15',
                '20' => '14'
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this awesome drink ware! Perfect addition for anybody who needs a cold/warm drink on the go. </p>
<ul>
<li>Tumblers available in 30oz and 20oz, comes with lid</li>
<li>Vacuum sealed lid insulates cold drinks for 24 hours and hot drinks for 12 hours. Double wall feature eliminates condensation and retains temperature</li>
<li>Stainless steel with a powder coat finish provides maximum durability against damages</li>
<li>Narrow mouth opening is perfect to drink from without spilling and narrow bottom fits standard cupholders. </li>
<li>Hand wash only, do not put in dishwasher</li>
</ul>";
            break;
        case 'hopecaregive.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        case 'game-slave.myshopify.com':
            $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
            break;
        default:
            $html = '<p></p>';
    }
    if ($shop->description) {
        $html = $shop->description;
    }
    $hasNavy = false;
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $size = $specs[0];
        $color = $specs[1];
        if ($color == 'Navy') {
            $hasNavy = true;
        }
        $imageUrls[$size][$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags[] = 'drinkware';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'ISIKEL',
        'product_type' => "Tumbler",
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $size => $colors) {
        foreach ($colors as $color => $url) {
            $sku = $color;
            if ($color == 'Cyan') {
                $sku = 'Seafoam';
            }
            $sku = str_replace('_', ' ', $sku);
            switch ($size) {
                case '30':
                    $option1 = '30oz Tumbler';
                    $sku = getSkuFromFileName($data['file_name']).' - T30 - '.$sku;
                    break;
                case '20':
                    $option1 = '20oz Tumbler';
                    $sku = getSkuFromFileName($data['file_name']).' - T20 - '.$sku;
                    break;
            }
            $variantData = array(
                'title' => $option1. ' / '.str_replace('_', ' ', $color),
                'price' => $prices[$size],
                'option1' => $option1,
                'option2' => str_replace('_', ' ', $color),
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => $sku
            );
            if ($color == ($hasNavy ? 'Navy' : 'Black') && $size == '30') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        switch ($size) {
            case '30oz Tumbler':
                $size = '30';
                break;
            case '20oz Tumbler':
                $size = '20';
                break;
        }
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
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

    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
