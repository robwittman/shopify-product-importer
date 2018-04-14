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

    protected $shop;

    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
    }

    public function toVariants(array $images)
    {
        $price = '14.99';
        if ($shop->myshopify_domain === 'plcwholesale.myshopify.com') {
            $price = '8.50';
        }

        $image = $images[0];
        foreach ($this->sizes as $size) {
            yield array(
                'title' => $size.' / White',
                'price' => $price,
                'option1' => $size,
                'option2' => 'White',
                'weight' => '0.6',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
        }
    }

    public function mapImagesToVariants()
    {

    }
}
