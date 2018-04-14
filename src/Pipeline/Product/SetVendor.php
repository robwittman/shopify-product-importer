<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetVendor
{
    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        $template = $payload->getTemplate();
        $settings = $payload->getSettings();
        $queue = $payload->getQueue();
        $data = $payload->getPostData();
        $data['vendor'] = $queue->vendor ?:
            $setting->vendor ?:
            $template->vendor;
        $html = $queue->description ?: $setting->description ?: $shop->description ?: $template->description;
        $payload->setProduct($data);
        return $payload;
    }
}
