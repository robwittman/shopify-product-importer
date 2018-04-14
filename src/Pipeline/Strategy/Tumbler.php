<?php

namespace App\Pipeline\Strategy;

use App\Pipeline\ProductMetaContainer;

class Tumbler
{
    protected $matrix;

    public function __construct($matrix)
    {
        $this->matrix = $matrix;
    }

    public function toVariants(array $images)
    {

    }

    public function mapImagesToVariants()
    {
        
    }
}
