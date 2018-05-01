<?php

namespace App\Pipeline\Shopify;

use App\Pipeline\Payload;
use Shopify\Object\Product;
use Shopify\Service\ProductService;
use Shopify\Api;

class PersistMappedImages
{
    /**
     * @var Api
     */
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Persist our image mapping to Shopify.
     * @note We use an update product to prevent some update issues
     * @param Payload $payload
     * @return Payload
     */
    public function __invoke(Payload $payload) : Payload
    {
        $product = $payload->getProduct();
        $service = new ProductService($this->api);
        $updateProduct = new Product();
        $updateProduct->id = $product->id;
        $updateProduct->images = $product->images;
        $service->update($updateProduct);
        return $payload;
    }
}