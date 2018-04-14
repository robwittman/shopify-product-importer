<?php

namespace App\Pipeline\Product;

use App\Pipeline\ProductMetaContainer;

class SetTags
{
    public function __invoke(ProductMetaContainer $payload)
    {
        $product = $payload->getProduct();
        $template = $payload->getTemplate();
        $settings = $payload->getSettings();
        $queue = $payload->getQueue();
        $data = $payload->getPostData();
        $tags = implode(',', array_merge(
            str_getcsv($queue->tags),
            str_getcsv($template->tags),
            str_getcsv($settings->tags)
        ));
        $data['tags'] = $tags;
        $payload->setProduct($data);
        return $payload;
    }
}
