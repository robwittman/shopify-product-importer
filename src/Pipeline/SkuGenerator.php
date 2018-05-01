<?php

namespace App\Pipeline;

use Liquid\Template;

class SkuGenerator
{
    /**
     * @var Template
     */
    protected $liquid;
    
    public function __construct(Template $liquid)
    {
        $this->liquid = $liquid;
    }

    public function __invoke(Payload $payload) : Payload
    {
        $template = $this->getSkuTemplate($payload);
        $this->liquid->parse($template);
        $product = $payload->getProduct();
        foreach ($product->variants as &$variant) {
            $sku = $this->liquid->render([
                'product' => $product,
                'shop' => $payload->getShop(),
                'variant' => $variant,
                'file' => str_replace('.zip', '', $payload->getQueue()->file_name),
                'queue' => $payload->getQueue()
            ]);
            $variant->sku = $sku;
        }
        return $payload->setProduct($product);
    }

    private function getSkuTemplate(Payload $payload) : string
    {
        return $payload->getQueue()->sku ?:
            $payload->getSetting()->sku_template ?:
                $payload->getTemplate()->sku_template;
    }
}