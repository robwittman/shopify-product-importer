<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;
use App\Pipeline\Strategy\StrategyInterface;

class SetVariants
{
    protected $strategy;

    public function __construct(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function __invoke($payload)
    {
        $product = $payload->getProduct();
        foreach ($strategy->toVariants() as $variant) {
            $product['variants'][] = $variant;
        }
        $payload->setProduct($product);
        return $payload;
    }
}
