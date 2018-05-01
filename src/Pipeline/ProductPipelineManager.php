<?php

namespace App\Pipeline;

use App\Model\Template;
use App\Pipeline\Product;

class ProductPipelineManager
{
    protected $templates = [
        'single_product' => Product\Apparel::class,
        'wholesale_apparel' => Product\Wholesale::class,
        'wholesale_tumbler' => Product\WholesaleTumbler::class,
        'hats' => Product\Hats::class,
        'stemless' => Product\Stemless::class,
        'drinkware' => Product\Drinkware::class,
        'uv_drinkware' => Product\UvDrinkware::class,
        'donation_uv_tumbler' => Product\DonationUvTumbler::class,
        'flasks' => Product\Flasks::class,
        'hats_masculine' => Product\MasculineHats::class,
        'multistyle_hats' => Product\HatsMultipleStyles::class
    ];

    public function getTemplateForProduct(Template $template) : string
    {
        if (array_key_exists($template->handle, $this->templates)) {
            $class = $this->templates[$template->handle];
            return new $class();
        }
        throw new \Exception("Invalid template {$template->handle} provided");
    }
}