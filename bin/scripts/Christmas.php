<?php

function createChristmas($queue, Google_Client $client)
{
    $results = array();
    $vendor = 'Canvus Print';
    $variants = array(
        'Hoodie' => array(
            'Small' => array('price' => '32.99', 'weight' => '16.1'),
            'Medium' => array('price' => '32.99', 'weight' => '17.5'),
            'Large' => array('price' => '32.99', 'weight' => '18.8'),
            'XL' => array('price' => '32.99', 'weight' => '21.2'),
            '2XL' => array('price' => '34.99', 'weight' => '22.9'),
            '3XL' => array('price' => '36.99', 'weight' => '24.1'),
            '4XL' => array('price' => '36.99', 'weight' => '24.5')
        ),
        'Long Sleeve' => array(
            'Small' => array('price' => '24.99', 'weight' => '7.6'),
            'Medium' => array('price' => '24.99', 'weight' => '8.8'),
            'Large' => array('price' => '24.99', 'weight' => '10.0'),
            'XL' => array('price' => '24.99', 'weight' => '10.3'),
            '2XL' => array('price' => '26.99', 'weight' => '12.4'),
            '3XL' => array('price' => '26.99', 'weight' => '12.6'),
            '4XL' => array('price' => '26.99', 'weight' => '13.6')
        ),
        'Tee' => array(
            'Small' => array('price' => '22.99', 'weight' => '5.6'),
            'Medium' => array('price' => '22.99', 'weight' => '6.3'),
            'Large' => array('price' => '22.99', 'weight' => '7.2'),
            'XL' => array('price' => '22.99', 'weight' => '8.0'),
            '2XL' => array('price' => '24.99', 'weight' => '8.7'),
            '3XL' => array('price' => '26.99', 'weight' => '9.8'),
            '4XL' => array('price' => '29.99', 'weight' => '10.2')
        ),
        'Crew' => array(
            'Small' => array('price' => '29.99', 'weight' => '5.6'),
            'Medium' => array('price' => '29.99', 'weight' => '6.3'),
            'Large' => array('price' => '29.99', 'weight' => '7.2'),
            'XL' => array('price' => '29.99', 'weight' => '8.0'),
            '2XL' => array('price' => '31.99', 'weight' => '8.7'),
            '3XL' => array('price' => '33.99', 'weight' => '9.8'),
            '4XL' => array('price' => '35.99', 'weight' => '10.2')
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($queue->shop);
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    switch($shop->myshopify_domain) {
        case 'plcwholesale.myshopify.com':
            $variants = array(
                'Hoodie' => array(
                    'Small' => array('price' => '20.00', 'weight' => '16.1'),
                    'Medium' => array('price' => '20.00', 'weight' => '17.5'),
                    'Large' => array('price' => '20.00', 'weight' => '18.8'),
                    'XL' => array('price' => '20.00', 'weight' => '21.2'),
                    '2XL' => array('price' => '22.00', 'weight' => '22.9'),
                    '3XL' => array('price' => '24.00', 'weight' => '24.1'),
                    '4XL' => array('price' => '26.00', 'weight' => '24.5')
                ),
                'Long Sleeve' => array(
                    'Small' => array('price' => '12.50', 'weight' => '7.6'),
                    'Medium' => array('price' => '12.50', 'weight' => '8.8'),
                    'Large' => array('price' => '12.50', 'weight' => '10.0'),
                    'XL' => array('price' => '12.50', 'weight' => '10.3'),
                    '2XL' => array('price' => '14.50', 'weight' => '12.4'),
                    '3XL' => array('price' => '16.50', 'weight' => '12.6'),
                    '4XL' => array('price' => '18.50', 'weight' => '13.6')
                ),
                'Tee' => array(
                    'Small' => array('price' => '11', 'weight' => '5.6'),
                    'Medium' => array('price' => '11', 'weight' => '6.3'),
                    'Large' => array('price' => '11', 'weight' => '7.2'),
                    'XL' => array('price' => '11', 'weight' => '8.0'),
                    '2XL' => array('price' => '13', 'weight' => '8.7'),
                    '3XL' => array('price' => '13', 'weight' => '9.8'),
                    '4XL' => array('price' => '13', 'weight' => '10.2')
                ),
                'Crew' => array(
                    'Small' => array('price' => '29.99', 'weight' => '5.6'),
                    'Medium' => array('price' => '29.99', 'weight' => '6.3'),
                    'Large' => array('price' => '29.99', 'weight' => '7.2'),
                    'XL' => array('price' => '29.99', 'weight' => '8.0'),
                    '2XL' => array('price' => '31.99', 'weight' => '8.7'),
                    '3XL' => array('price' => '33.99', 'weight' => '9.8'),
                    '4XL' => array('price' => '35.99', 'weight' => '10.2')
                )
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
    if ($shop->description) {
        $html = $shop->description;
    }
    $sku = generateSku($shop, $post['product_title']);
    $results = array(
        'product_name' => $post['product_title'],
        'shopify_product_admin_url' => null,
        'front_print_file_url' => $post['front_print_url'],
        'back_print_file_url' => $post['back_print_url'],
        'variants' => array()
    );

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('-', $productData);
        $style = $specs[0];
        $color = $specs[1];
        $imageUrls[$style][$color] = $name;
    }

    $tags = explode(',', trim($post['tags']));
    $tags[] = 'christmas';
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => $vendor,
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

    foreach ($variants as $style => $sizes) {
        switch ($style) {
            case 'Hoodie':
                $fulfillerCode = '18500';
                break;
            case 'LS':
                $style = 'Long Sleeve';
                $fulfillerCode = '2400';
                break;
            case 'Tees':
                $style = 'Tee';
                $fulfillerCode = 'NL3600';
                break;
            case 'Crew':
                $style = 'Crewneck';
                $fulfillerCode = '';
                break;
        }
        foreach ($sizes as $size => $options) {
            foreach (['Green', 'Red'] as $color) {
                $variantSku = getVariantSku($sku, ($style == 'Crewneck' ? 'Crew' : $style), $color);
                $results['variants'][] = array(
                    'garment_name' => $style,
                    'product_fulfiller_code' => $fulfillerCode,
                    'garment_color' => $color,
                    'product_sku' => $variantSku,
                );
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / ' . $style,
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'option3' => $style,
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny',
                    'sku' => $variantSku
                );
                $product_data['variants'][] = $variantData;
            }
        }
    }

    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $results['shopify_product_admin_url'] = "https://{$shop->myshopify_domain}/admin/products/{$res->product->id}";
    $imageUpdate = array();
    $variantMap = array(
        'Red' => array(
            'Hoodie' => array(),
            'Long Sleeve' => array(),
            'Tee' => array(),
            'Crewneck' => array(),
        ),
        'Green' => array(
            'Hoodie' => array(),
            'Long Sleeve' => array(),
            'Tee' => array(),
            'Crewneck' => array()
        )
    );
    foreach ($res->product->variants as $variant) {
        $style = $variant->option3;
        $color = $variant->option2;
        $variantMap[$color][$style][] = $variant->id;
    }
    foreach ($variantMap as $color => $styles) {
        foreach ($styles as $style => $ids) {
            $imageStyle = $style;
            if ($style == 'Long Sleeve') {
                $imageStyle = 'LS';
            } else if ($style == 'Crewneck') {
                $imageStyle = 'Crew';
            }
            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$imageStyle][$color],
                'variant_ids' => $ids
            );
            $imageUpdate[] = $data;
        }
    }

    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));
    if (isset($queue->log_to_google) && $queue->log_to_google && $shop->google_access_token) {
        if ($shop->google_access_token) {
            $client->setAccessToken($shop->google_access_token);
        }
        logResults($client, $shop->google_sheet_slug, $post['print_type'], $results);
    } else {
        error_log("No google sync...");
    }
    $queue->finish(array($res->product->id));
    return array($res->product->id);
}
