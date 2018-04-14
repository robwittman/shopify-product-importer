<?php

namespace App\Pipeline;

use Liquid\Template as Liquid;

class AddSku
{
    protected $template;

    public function __construct($skuTemplate)
    {
        $liquid = new Liquid();
        $this->template = $liquid->parse($skuTemplate);
    }

    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        foreach ($product'variants'] as &$variant) {
            &$variant['sku'] = $this->template->render(array(
                'product' => $product,
                'variant' => $variant,
                'file' => $payload->getFile(),
                'data' => $payload->getPostData(),
                'shop' => $payload->getShop()
            ));
        }
        return $payload;
    }
}
