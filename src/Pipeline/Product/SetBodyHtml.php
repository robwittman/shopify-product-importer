<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetBodyHtml
{
    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        $template = $payload->getTemplate();
        $settings = $payload->getSettings();
        $queue = $payload->getQueue();
        $shop = $payload->getShop();
        $data = $payload->getPostData();
        $html = $queue->description ?: $setting->description ?: $shop->description ?: $template->description;
        $data['body_html'] = $html;
        return $payload->setProduct($data);
    }
}
