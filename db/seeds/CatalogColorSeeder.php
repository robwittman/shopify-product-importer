<?php

use Phinx\Seed\AbstractSeed;

class CatalogColorSeeder extends AbstractSeed
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
            '5000' => [
                'Black',
                'Navy',
                'Royal',
                'Dark Heather',
                'Sport Grey',
                'Cardinal Red',
                'Carolina Blue',
                'Charcoal',
                'Dark Chocolate',
                'Forest Green',
                'Garnet',
                'Gold',
                'Heliconia',
                'Irish Green',
                'Light Pink',
                'Maroon',
                'Orange',
                'Purple',
                'Red',
                'Tennessee Orange',
                'Tropical Blue'
            ],
            '2400' => [
                'Navy',
                'White',
                'Black',
                'Cardinal Red',
                'Charcoal',
                'Dark Chocoloate',
                'Irish Green',
                'Maroon',
                'Orange',
                'Purple',
                'Red',
                'Royal',
                'Sport Grey',
                'Forest Green'
            ],
            '18500' => [
                'Gold',
                'Black',
                'Cardinal Red',
                'Carolina Blue',
                'Charcoal',
                'Dark Chocolate',
                'Dark Heather',
                'Forest Green',
                'Heliconia',
                'Irish Green',
                'Light Pink',
                'Maroon',
                'Navy',
                'Orange',
                'Purple',
                'Red',
                'Royal',
                'Sport Grey',
                'White'
            ],
            'NL1533' => [
                'Black',
                'Dark Grey',
                'Heather Grey',
                'Kelly Green',
                'Navy',
                'Purple',
                'Red',
                'Royal',
                'White'
            ],
            '4400' => [
                'Pink',
                'Black',
                'Light Blue',
                'Heather Grey',
                'White'
            ],
            'NL3600' => [
                'Navy',
                'Royal',
                'Light Grey',
                'Heather Grey',
                'Black',
                'White',
                'Purple',
                'Red',
                'Heavy Metal',
                'Forest Green'
            ],
            '18000' => [
                'Black',
                'Red',
                'White',
                'Forest Green',
                'Maroon',
                'Navy',
                'Royal Blue',
                'Dark Heather',
                'Sport Grey',
                'Light Pink'
            ]
        ];

        foreach ($data as $code => $colors) {
            foreach ($colors as $color) {
                ($this->table('catalog_colors'))->insert([
                    'catalog_id' => $code,
                    'name' => $color
                ])->save();
            }
        }
    }
}
