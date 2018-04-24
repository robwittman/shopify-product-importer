<?php

use Phinx\Seed\AbstractSeed;

class TemplateSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $data = array(
            array(
                'handle' => 'single_product',
                'name' => 'Apparel',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'apparel'
            ),
            array(
                'handle' => 'wholesale_apparel',
                'name' => 'Wholesale Apparel',
                'description' => '',
                'vendor' => 'Edge Promotions',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'staple_wholesale_apparel',
                'name' => 'Staple Wholesale Apparel',
                'description' => '',
                'vendor' => '',
                'product_type' => '',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'wholesale_tumbler',
                'name' => 'Wholesale Tumbler',
                'description' => '',
                'vendor' => 'Iconic Imprint',
                'product_type' => 'Tumbler',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'grey_collection',
                'name' => 'Grey Collection',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'grey, apparel'
            ),
            array(
                'handle' => 'stemless',
                'name' => 'Stemless Wine Cup',
                'description' => '',
                'vendor' => 'Iconic Imprint',
                'product_type' => 'Stemless Wine Cup',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'hats',
                'name' => 'Hats',
                'description' => '',
                'vendor' => 'Edge Promotions',
                'product_type' => 'Headware',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'hat'
            ),
            array(
                'handle' => 'hats_masculine',
                'name' => 'Hats - Masculine',
                'description' => '',
                'vendor' => 'Edge Promotions',
                'product_type' => 'Headware',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'hat'
            ),
            array(
                'handle' => 'drinkware',
                'name' => 'Laser Etched Tumblers',
                'description' => '',
                'vendor' => 'ISIKEL',
                'product_type' => 'Tumbler',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'drinkware'
            ),
            array(
                'handle' => 'uv_tumbler',
                'name' => 'UV Tumbler',
                'description' => '',
                'vendor' => 'ISIKEL',
                'product_type' => 'UV Tumbler',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'baby_body_suit',
                'name' => 'Baby Body Suit',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'Body Suit, Baby'
            ),
            array(
                'handle' => 'raglans',
                'name' => 'Raglans',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => '3/4 sleeve raglan'
            ),
            array(
                'handle' => 'front_back_pocket',
                'name' => 'Front Back Pocket',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            ),
            array(
                'handle' => 'christmas',
                'name' => 'Christmas',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'Apparel',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'christmas'
            ),
            array(
                'handle' => 'donation_uv_tumbler',
                'name' => 'UV Tumbler (100% Donation)',
                'description' => '',
                'vendor' => 'Canvus Print',
                'product_type' => 'UV Tumbler',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => 'UV Tumbler, Awareness'
            ),
            array(
                'handle' => 'multistyle_hats',
                'name' => 'Multi Style Hats',
                'description' => '',
                'vendor' => 'Edge Promo',
                'product_type' => 'Hat',
                'enabled' => 1,
                'sku_template' => null,
                'tags' => ''
            )
        );
        $this->table('templates')->insert($data)->save();
    }
}
