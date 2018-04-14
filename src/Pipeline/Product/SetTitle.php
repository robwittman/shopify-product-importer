<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetTitle
{
    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        $product['title'] = $payload->getQueue()->title;
        $payload->setProduct($product);
        return $payload;
    }
}
