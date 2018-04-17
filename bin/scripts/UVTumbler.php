<?php

function createUvTumbler($queue)
{
    $image_data = array();
    $imageUrls = array();
    global $s3;
    $queue->started_at = date('Y-m-d H:i:s');
    $data = $queue->data;

    if (isset($queue->file_name)) {
        $post = $data['post'];

        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => "shopify-product-importer",
            "Prefix" => $queue->file_name
        ));

        foreach ($objects as $object) {
            if (strpos($object["Key"], "MACOSX") !== false || strpos($object["Key"], "Icon^M" !== false)) {
                continue;
            }
            $image_data[] = $object["Key"];
        }
        $shop = \App\Model\Shop::find($queue->shop_id);

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

        $product_data = array(
            'title' => $post['product_title'],
            'body_html' => $html,
            'tags' => $post['tags'],
            'vendor' => "ISIKEL",
            'product_type' => 'UV Tumbler',
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
            $price = 37.99;
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
                    "sku" => "PL - T{$size} - {$skuColor}"
                );
                if($color == 'Black' && $size == '30') {
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
