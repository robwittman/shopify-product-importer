<?php

function createFlasks($queue)
{
    $price = '19.99';
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($queue->shop);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $price = '12.00';
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<div>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</div>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p>You are going to <strong>LOVE<span> </span></strong>this awesome flask! Perfect addition for anybody who needs a quick drink on-the-go. Fill it with whatever you like, we won't judge....we'll encourage! </p>
<ul>
<li>Stainless Steel 6oz Flask</li>
<li>Heavy Duty but Light-Weight. Will not easily scratch or tarnish </li>
<li>Screw down cap ensures no liquid will escape the flask. Cap is attached to flask. </li>
<li>Reliable method of storing beverages for personal use. </li>
<li>Perfect gift for yourself or someone else.</li>
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
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'flask';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'ISIKEL',
        'options' => array(
            array(
                'name' => "Szie"
            ),
            array(
                'name' => "Color"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $color => $url) {
        $variantData = array(
            'title' => '6oz / '.$color,
            'price' => $price,
            'option1' => '6oz',
            'option2' => $color,
            'weight' => '1.1',
            'weight_unit' => 'lb',
            'requires_shipping' => true,
            'inventory_management' => null,
            'inventory_policy' => 'deny',
            'sku' => '6oz - Flask - '.$color
        );
        if ($color == 'Blue') {
            $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
        } else {
            $product_data['variants'][] = $variantData;
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));

    $imageUpdate = array();
    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = $variant->option2;
        $image = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
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
