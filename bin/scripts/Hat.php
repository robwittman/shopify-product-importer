<?php

function createHats($queue) {
    $price = '29.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($queue->shop);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];
    $html = '<p></p>';
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.50';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' />
    <h5>Shipping &amp; Return Policy</h5>
    <p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
    <ul>
    <li>Hassle free return/exchange policy! </li>
    <li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
    </ul>
    <h5>Trucker Hat</h5>
    <p>You are going to <strong>LOVE </strong>our Trucker hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton front panel and visor </li>
    <li>100% nylon mesh back panel </li>
    <li>6-panel, structured, mid-profile </li>
    <li>Pigment-dyed front panels </li>
    <li>Traditional tan nylon mesh back panels </li>
    <li>Distressed torn visor, cotton twill sweatband </li>
    <li>Plastic tab back closure;Cool-Crown mesh lining</li>
    </ul>
    <h5>Cotton Twill Hat</h5>
    <p>You are going to <strong>LOVE</strong><span> </span>our Cotton Twill hats! This will be a perfect addition to your hat collection! </p>
    <ul>
    <li>100% cotton twill </li>
    <li>Garment washed, pigment dyed</li>
    <li>Six panel, unstructured, low profile </li>
    <li>Tuck-away leather strap, antique brass buckle </li>
    <li>Adams exclusive Cool Crown Mesh Lining </li>
    <li>Four rows of stitching on self-fabric sweatband</li>
    <li>Sewn eyelets</li>
    <li>One Size Fits All </li>
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
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $style = $specs[0];
        $color = $specs[1];
        $imageUrls[$style][$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'hat';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'Edge Promotions',
        'product_type' => 'hat',
        'options' => array(
            array(
                'name' => "Style"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
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
