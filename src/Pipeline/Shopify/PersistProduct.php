<?php

namespace App\Pipeline\Shopify;

use App\Pipeline\Payload;
use Shopify\Service\ProductService;
use Shopify\Api;

class PersistProduct
{
    /**
     * @var Api
     */
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function __invoke(Payload $payload) : Payload
    {
        $service = new ProductService($this->api);
        $product = $payload->getProduct();
        $service->createProduct($product);
        return $payload->setProduct($product);
    }
}