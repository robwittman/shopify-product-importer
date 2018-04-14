<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class CreateProduct
{
    public function __construct()
    {

    }

    public function __invoke($payload)
    {
        // Persist our product to Shopify
        return $payload;
    }
}
