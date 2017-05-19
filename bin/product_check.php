<?php

require_once __DIR__. '/../vendor/autoload.php';
require_once __DIR__. '/../src/common.php';

$products = array();

$piperLou = (object) array(
    'myshopify_domain'  => 'piper-lou-collection.myshopify.com',
    'api_key'           => '427a3f8f0cb5b3b443bc7157aa6bcd1e',
    'password'          => 'befc75058d4cd38f7df70564226319e5'
);

$plcWholesale = (object) array(
    'myshopify_domain'  => 'plcwholesale.myshopify.com',
    'api_key'           => '9567edb38de3bd5f802945af5f1b6de1',
    'password'          => 'fc3a72c19b3d6fb5a78c5561394ab080'
);

$params = array(
    'limit' => 250,
    'page' => 1,
    'created_at_min' => date('c', strtotime('-2 weeks'))
);

do {
    $res = callShopify($piperLou, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        $check = callShopify($plcWholesale, '/admin/products.json', 'GET', array(
            'handle' => $product->handle
        ));
        usleep(500000);
        if (empty($check->products)) {
            $result = uploadProduct($plcWholesale, $product);
            error_log($result->product->id);
        }
    }
    $params['page']++;
} while (count($res->products) == $params['limit']);


function uploadProduct($newShop, $product)
{
    $data = array(
        'body_html' => $product->body_html,
        'handle' => $product->handle,
        'images' => array(),
        'product_type' => $product->product_type,
        'published_at' => $product->published_at,
        'published_scope' => $product->published_scope,
        'tags' => $product->tags,
        'template_suffix' => $product->template_suffix,
        'title' => $product->title,
        'variants' => array(),
        'vendor' => $product->vendor,
        'options' => $product->options
    );
    foreach ($product->images as $image) {
        $imageData = array(
            'position' => $image->position,
            'src' => $image->src
        );
        $data['images'][] = $imageData;
    }

    foreach ($product->variants as $variant) {
        $variantData = array(
            'barcode' => $variant->barcode,
            'compare_at_price' => $variant->compare_at_price,
            'fulfillment_service' => $variant->fulfillment_service,
            'grams' => $variant->grams,
            'inventory_management' => $variant->inventory_management,
            'inventory_policy' => $variant->inventory_policy,
            'option1' => $variant->option1,
            'option2' => $variant->option2,
            'option3' => $variant->option3,
            'price' => $variant->price,
            'requires_shipping' => $variant->requires_shipping,
            'sku' => $variant->sku,
            'taxable' => $variant->taxable,
            'title' => $variant->title,
            'weight' => $variant->weight,
            'weight_unit' => $variant->weight_unit
        );
        if ($product->vendor == 'LDC') {
            $variantData['price'] = 20.00;
        } else if ($product->vendor = 'BPP') {
            $variantData['price'] = getPrice($variantData);
        }
        $data['variants'][] = $variantData;
    }
    $res = callShopify($newShop, '/admin/products.json', 'POST', array(
        'product' => $data
    ));
    return $res;
}

function getPrice($data)
{
    $garments = array(
        'Long Sleeve' => 12.00,
        'Hoodie' => 20.00,
        'Tank' => 10.00,
        'Tee' => 10.00
    );
    $modifiers = array(
        '2XL' => 2.00,
        '3XL' => 4.00,
        '4XL' => 4.00
    );

    $options = array(
        $data['option1'],
        $data['option2'],
        $data['option3']
    );

    $price = $data['price'];
    foreach ($options as $option) {
        if (in_array($option, array_keys($garments))) {
            $price = $garments[$option];
        }
    }

    foreach ($options as $option) {
        if (in_array($option, array_keys($modifiers))) {
            $price += $modifiers[$option];
        }
    }
    return $price;
}
