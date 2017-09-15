<?php

function createRaglans($queue)
{
    $prices = array(
        'Small' => array(
            'price' => '24.99',
            'weight' => '7.6',
        ),
        'Medium' => array(
            'price' => '24.99',
            'weight' => '8.8',
        ),
        'Large' => array(
            'price' => '24.99',
            'weight' => '10.0',
        ),
        'XL' => array(
            'price' => '24.99',
            'weight' => '10.3',
        ),
        '2XL' => array(
            'price' => '27.99',
            'weight' => '12.4',
        ),
        '3XL' => array(
            'price' => '27.99',
            'weight' => '13.2',
        ),
        '4XL' => array(
            'price' => '29.99',
            'weight' => '14.0',
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($post['shop']);
    $image_data = getImages($s3, $data['file']);
    $imageUrls = [];

    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $prices = array(
                'Small' => array(
                    'price' => '12.50',
                    'weight' => '7.6',
                ),
                'Medium' => array(
                    'price' => '12.50',
                    'weight' => '8.8',
                ),
                'Large' => array(
                    'price' => '12.50',
                    'weight' => '10.0',
                ),
                'XL' => array(
                    'price' => '12.50',
                    'weight' => '10.3',
                ),
                '2XL' => array(
                    'price' => '12.50',
                    'weight' => '12.4',
                ),
                '3XL' => array(
                    'price' => '12.50',
                    'weight' => '13.2',
                ),
                '4XL' => array(
                    'price' => '14.50',
                    'weight' => '14.0',
                )
            );
        case 'piper-lou-collection.myshopify.com':
        case 'importer-testing.myshopify.com':
            $html = "<meta charset='utf-8' /><meta charset='utf-8' /><meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p><span>You are going to <strong>LOVE</strong> this design! We offer apparel in Short Sleeve shirts, Long Sleeve Shirts, Tank tops, and Hoodies. If you want information on sizing, please view the sizing chart below. </span></p>
<p><span>Apparel is designed, printed, and shipped in the USA. 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 </span></p>
<p><a href='https://www.piperloucollection.com/pages/sizing-chart'>View our sizing chart</a></p>";
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
    $tags[] = '3/4 sleeve raglan';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => 'BPP',
        'options' => array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        ),
        'variants' => array(),
        'images' => array()
    );

    foreach ($imageUrls as $color => $url) {
        $color = str_replace('_', ' ', $color);
        foreach ($prices as $size => $options) {
            $variantData = array(
                'title' => $size . ' / ' . $color . ' / Raglan 3/4 Sleeve',
                'price' => $options['price'],
                'option1' => $size,
                'option2' => $color,
                'option3' => 'Raglan 3/4 Sleeve',
                'weight' => $options['weight'],
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny',
                'sku' => "3/4 Sleeve Raglan - {$color} - {$size}"
            );
            if ($color == 'Navy' && $size == '30') {
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
