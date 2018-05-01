<?php

namespace App\Pipeline\Product;

use App\Pipeline\Payload;

class WholesaleTumbler
{
    public function __construct()
    {

    }

    public function __invoke(Payload $payload) : Payload
    {
        $product = $payload->getProduct();
        $images = $payload->getImages();
        $product->options = [
            ['name' => "Size"],
            ['name' => "Color"]
        ];
        foreach ($images as $image) {
            $chunks = explode('/', $image);
            $fileName = $chunks[count($chunks) - 1];

            $pieces = explode('-', basename($fileName, '.jpg'));
            $color = trim($pieces[1], '_');
            $size = trim($pieces[0], '_');
            if (strpos($size, '30') !== false) {
                $size = '30oz';
            } else {
                $size = '20oz';
            }
            $images[$size][$color] = $image;
        }

        return $payload->setProduct($product);
    }
}