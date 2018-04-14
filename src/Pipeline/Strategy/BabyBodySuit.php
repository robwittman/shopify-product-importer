<?php

namespace App\Pipeline\Strategy;

class BabyBodySuit implements StrategyInterface
{
    protected $sizes = array(
        'Newborn',
        '6 Months',
        '12 Months',
        '18 Months',
        '24 Months'
    );

    public function toVariants(array $images)
    {

    }
}
