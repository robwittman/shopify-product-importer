<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;
use Shopify\Service\ProductService;

class CreateProduct
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function __invoke($payload)
    {
        // Persist our product to Shopify
        $product = $payload->getProduct();
        $res = $this->service->create($payload->getProduct());
        $product['id'] = $res->product->id;
        $payload->setProduct($product);
        return $payload;
    }
}
