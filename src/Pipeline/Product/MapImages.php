<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;
use App\Pipeline\Strategy\StrategyInterface;
use Shopify\Service\ProductService;

class MapImages
{
    protected $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function __invoke($payload)
    {
        $product = $payload->getProduct();
        $variantMap = $payload->getVariantMap();

        $this->service->update($product['id'], $variantMap);
        // Persist our new images map
        return $payload;
    }
}
