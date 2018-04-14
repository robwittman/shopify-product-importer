<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;
use App\Pipeline\Strategy\StrategyInterface;

class MapImages
{
    protected $strategy;

    public function __construct(Strategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function __invoke($payload)
    {
        // Persist our new images map
        return $payload;
    }
}
