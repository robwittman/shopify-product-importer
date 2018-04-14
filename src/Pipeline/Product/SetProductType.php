<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetProductType
{
    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        $template = $payload->getTemplate();
        $settings = $payload->getSettings();
        $queue = $payload->getQueue();
        $data = $payload->getPostData();
        $data['product_type'] = $queue->product_type ?:
            $setting->product_type ?:
            $template->product_type;
        $payload->setProduct($data);
        return $payload;
    }
}
