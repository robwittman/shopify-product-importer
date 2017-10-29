<?php

use Phinx\Seed\AbstractSeed;

class CatalogSeeder extends AbstractSeed
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
        $data = [
            [
                'name' => 'Gildan Heavy Cotton (Unisex Crew)',
                'fulfiller_code' => '5000'
            ], [
                'name' => 'Gildan Ultra Cotton LS Shirt',
                'fulfiller_code' => '2400'
            ], [
                'name' => 'Gildan Heavy Blend Adult Hooded Sweatshirt',
                'fulfiller_code' => '18500'
            ], [
                'name' => 'Next Level Ideal Racerback Tank (Women\'s)',
                'fulfiller_code' => 'NL1533'
            ], [
                'name' => 'Rabbit Skins Onesie',
                'fulfiller_code' => '4400'
            ], [
                'name' => 'Next Level Unisex Premium',
                'fulfiller_code' => 'NL3600'
            ], [
                'name' => 'Gildan Unisex Crewneck Sweatshirt',
                'fulfiller_code' => '18000'
            ]
        ];
        $catalog = $this->table('catalog');
        $catalog->insert($data)->save();
    }
}
