<?php

namespace App\Pipeline;

class GetUniqueSku
{
    public function __invoke($payload)
    {
        $product = $payload->getProduct();

        $payload->setProduct($product);
        return $payload;
    }
}
