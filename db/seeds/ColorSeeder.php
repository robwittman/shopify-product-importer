<?php

use Phinx\Seed\AbstractSeed;

class ColorSeeder extends AbstractSeed
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
        $colors = [
            'Black',
            'Navy',
            'Royal Blue',
            'Pink',
            'Athletic Navy',
            'Berry',
            'Celadon',
            'Deep Coral',
            'Jade',
            'Kelly',
            'Maroon',
            'Heliconia',
            'Moss',
            'Orange',
            'Purple',
            'Red',
            'Royal',
            'Sky Blue',
            'Soft Pink',
            'Turquoise',
            'Aqua',
            'Butter',
            'Heather',
            'Fuchsia',
            'Hot Pink',
            'Lavender',
            'Raspberry',
            'White',
            'Charcoal Heather',
            'Forest',
            'Neon Pink',
            'Teal',
            'Cyan',
            'Dark Denim',
            'Khaki',
            'Seafoam',
            'Black and White',
            'Camo and Stone',
            'Heather and Black',
            'Heather and Red',
            'Heather and White',
            'Khaki and White',
            'Navy and White',
            'Orange and White',
            'Pink and White',
            'Red and White'
        ];
        $data = array_map(function($color) {
            return ['name' => $color];
        }, $colors);

        $table = $this->table('colors');
        $table->insert($data)->save();
    }
}
