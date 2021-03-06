<?php

function createFrontBackPocket($queue)
{
    $vendor = 'Canvus Print';
    $prices = array(
        'Tee' => array(
            'small' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '16',
                'weight' => '0.0',
            )
        ),
        'Long Sleeve' => array(
            'small' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '18',
                'weight' => '0.0',
            )
        )
    );

    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);
    $post = $data['post'];
    $shop = \App\Model\Shop::find($queue->shop);
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];
    if (in_array($shop->myshopify_domain, ['piper-lou-collection.myshopify.com', 'plcwholesale.myshopify.com'])) {
        $vendor = 'BPP';
    }
    $html = '';
    if ($shop->description) {
        $html = $shop->description;
    }
    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }
    $tags = explode(',', trim($post['tags']));
    $tags = implode(',', $tags);
    $product_data = array(
        'title' => $post['product_title'],
        'body_html' => $html,
        'tags' => $tags,
        'vendor' => $vendor,
        'product_type' => 'Apparel',
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
    foreach ($prices as $style => $sizes) {
        foreach ($sizes as $size => $options) {
            foreach ($imageUrls as $color => $url) {
                $color = str_replace('_', ' ', $color);
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
                    'sku' => ""
                );
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    $variantMap = array();

    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        if (!isset($variantMap[$color])) {
            $variantMap[$color] = array();
        }
        $variantMap[$color][] = $variant->id;
    }
    foreach ($variantMap as $color => $ids) {
        $data = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$color],
            'variant_ids' => $ids
        );
        $imageUpdate[] = $data;
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
