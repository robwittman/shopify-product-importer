<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetDefaultVariant
{
    public function __construct()
    {

    }

    public function __invoke($payload)
    {
        $product = $payload->getProduct();
        $defaultColor = null;
        $defaultSize = null;
        $defaultStyle = null;
        foreach ($product['variants'] as $index => $variant) {
            // if ( variantShouldBeDefault ) {
            //     unset($product['variants'][$index]);
            //     $variants = array($variant) + $product['variants'];
            //     $product['variants'] = $variants;
            //     break;
            // }
        }
        return $payload;
    }
}
