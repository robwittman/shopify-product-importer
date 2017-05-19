<?php

require_once __DIR__. '/../vendor/autoload.php';
require_once __DIR__. '/../src/common.php';

$plcWholesale = (object) array(
    'myshopify_domain'  => 'plcwholesale.myshopify.com',
    'api_key'           => '9567edb38de3bd5f802945af5f1b6de1',
    'password'          => 'fc3a72c19b3d6fb5a78c5561394ab080'
);

$params = array(
    'limit' => 250,
    'page' => 1
);

$prices = array(
    'vendors' => array(
        'BPP' => array(
            'Tank' => 12.00,
            'Tee' => 11.00,
            'Long Sleeve' => 12.50,
            'Hoodie' => 20.00
        ),
        'Kodiak' => array(
            '20oz' => 25.00,
            '30oz' => 30.00
        ),
        'Tx Tumbler' => array(
            '20oz' => 19.00,
            '30oz' => 20.00
        ),
        'LDC' => array(
            '20oz' => 19.00,
            '30oz' => 20.00
        )
    )
);

## Check Crews
$modifiers = array(
    'Tee' => array(
        '2XL' => 2,
        '3XL' => 2,
        '4XL' => 2,
        '5XL' => 2
    ),
    'Long Sleeve' => array(
        '2XL' => 2,
        '3XL' => 2,
        '4XL' => 2,
        '5XL' => 2
    ),
    'Hoodie' => array(
        '2XL' => 2,
        '3XL' => 4,
        '4XL' => 4
    ),
    'UV' => array(
        '20oz' => 1,
        '30oz' => 2
    )
);

$vendors = array(
    'Acme',
    'BPP',
    'Kodiak',
    'LDC',
    'Piper Lou Collection',
    'PLCWholesale',
    'S&S',
    'Tx Tumbler'
);

$ids = [];
$failedProducts = 0;
do {
    $res = callShopify($plcWholesale, '/admin/products.json', 'GET', $params);
    foreach ($res->products as $product) {
        error_log($product->id);
        $failed = false;
        if ($product->vendor == 'Kodiak') {
            continue;
        }
        if ($product->product_type == 'Jewelry') {
            continue;
        }
        foreach ($product->variants as $variant) {
            // if ($variant->price == 19.99 && $product->vendor == 'BPP') {
            //     var_dump($product);
            //     exit;
            // }
            $newPrice = getPrice($product, $variant);

            if (!$newPrice) {
                $failed = true;
                continue;
            }
            if ($newPrice != $variant->price) {
                error_log("Updating {$variant->sku} to {$newPrice}. Was {$variant->price}");
                callShopify($plcWholesale, '/admin/variants/'.$variant->id.'.json','PUT',array(
                    'variant' => array(
                        'price' => $newPrice
                    )
                ));
                usleep(500000);
            } else {
                error_log("{$variant->sku} already set to correct price: {$newPrice}");
            }
        }
        if ($failed) {
            $failedProducts++;
            $ids[] = $product->id;
        }
    }
    $params['page']++;

} while(count($res->products) == $params['limit']);

foreach ($ids as $idx => $id) {
    error_log("$idx => $id");
}
error_log("Products with > 1 failed variant: {$failedProducts}");
function getPrice($product, $variant) {
    global $prices;
    global $modifiers;

    $sizeHandle = null;
    $styleHandle = null;
    foreach ($product->options as $option) {
        if ($option->name == "Size") {
            $sizeHandle = "option{$option->position}";
        } elseif ($option->name == "Style") {
            $styleHandle = "option{$option->position}";
        }
    }

    if ($product->vendor == "LDC" || $product->vendor == "Tx Tumbler") {
        // We have a tumbler, so let's act as such
        if (!isset($variant->{$sizeHandle})) {
            return false;
        }
        $size = $variant->{$sizeHandle};
        if (!isset($prices['vendors']['LDC'][$size])) {
            if (strpos($variant->title, "30 oz") !== false) {
                $size = '30oz';
            } else if (strpos($variant->title, "20 oz") !== false) {
                $size = '20oz';
            } else {
                return false;
            }
        }
        $startingPrice = $prices['vendors']['LDC'][$size];
        if (strpos($product->title, "UV") !== false) {
            $startingPrice += $modifiers['UV'][$size];
        }
        return $startingPrice;
    } else if ($product->vendor == 'BPP') {
        if (is_null($sizeHandle) || !isset($variant->{$sizeHandle})) {
            return false;
        }
        $garment = null;
        if (isset($variant->{$styleHandle})) {
            $garment = $variant->{$styleHandle};
        } else {
            foreach ($prices['vendors']['BPP'] as $style => $cost) {
                if (strpos($variant->sku, $style) !== false) {
                    $garment = $style;
                    break;
                }
            }
        }
        $size = $variant->{$sizeHandle};
        if (is_null($garment) || is_null($size)) {
            return false;
        }
        if (!isset($prices['vendors']['BPP'][$garment])) {
            return false;
        }
        $startingPrice = $prices['vendors']['BPP'][$garment];
        if (isset($modifiers[$garment][$size])) {
            $startingPrice += $modifiers[$garment][$size];
        }
        return $startingPrice;
    }
}
