<?php

function createUvTumbler($queue)
{
    $image_data = array();
    $imageUrls = array();
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    if (isset($data['file'])) {
        $post = $data['post'];

        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => "shopify-product-importer",
            "Prefix" => $data['file']
        ));

        foreach ($objects as $object) {
            if (strpos($object["Key"], "MACOSX") !== false || strpos($object["Key"], "Icon^M" !== false)) {
                continue;
            }
            $image_data[] = $object["Key"];
        }
        $shop = \App\Model\Shop::find($queue->shop);

        $shopReq = [];

        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != 'jpg') {
                continue;
            }
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $size = $specs[0];
            $color = $specs[1];
            $imageUrls[$size][$color] = $name;
        }

        switch ($shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'hopecaregive.myshopify.com':
            case 'game-slave.myshopify.com':
            default:
                $html = '<meta charset="utf-8" />'.
                        "<ul>".
                            "<li>2x heat &amp; cold retention (compared to plastic tumblers).</li>".
                            "<li>Double-walled vacuum insulation - Keeps Hot and Cold. </li>".
                            "<li>Fits most cup holders, Clear lid to protect from spills. </li>".
                            "<li>Sweat Free Design allows for a Strong Hold. </li>".
                            "<li>These tumblers will ship separately from our distributor in Texas. </li>".
                        '</ul>';
        }

        $product_data = array(
            'title' => $post['product_title'],
            'body_html' => $html,
            'tags' => $post['tags'],
            'vendor' => "ISIKEL",
            'product_type' => $post['product_type'],
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
            $price = 34.99;
            if ($size == '30') {
                $price = 39.99;
            }
            foreach ($colors as $color => $image) {
                $skuColor = str_replace('_', ' ', $color);
                $optionColor = $color;
                if ($color == "Stainless") {
                    $optionColor = "Grey";
                }
                $variantData = array(
                    'title' => "{$size}oz/{$color}",
                    "price" => $price,
                    "option1" => "{$size}oz",
                    "option2" => $skuColor,
                    "weight" => "1.1",
                    "weight_unit" => "lb",
                    "requires_shipping" => true,
                    "inventory_management" => null,
                    "inventory_policy" => "deny",
                    "sku" => "TX (UV PRINTED) - T{$size} - {$skuColor} - Coated {$size}oz Tumbler"
                );
                if($color == 'Navy' && $size == '30') {
                    error_log("Moving $color / $size to front of array");
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
            $size = str_replace("oz", '', $variant->option1);
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                "src" => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
                'variant_ids' => [$variant->id]
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
}
