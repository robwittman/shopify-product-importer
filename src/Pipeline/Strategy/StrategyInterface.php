<?php

namespace App\Pipeline\Strategy;

interface StrategyInterface
{
    public function toVariants(array $images);

    public function mapImagesToVariants();
}
