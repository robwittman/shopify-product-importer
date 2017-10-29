<?php

namespace App\Model;

class Catalog extends Elegant
{
    protected $primaryKey = 'fulfiller_code';

    protected $table = 'catalog';

    protected $casts = array(
        'fulfiller_code' => 'string'
    );

    public function colors()
    {
        return $this->hasMany(Color::class, 'catalog_id', 'fulfiller_code');
    }
}
