<?php

namespace App\Pipeline;

class InitializeProduct
{
    /**
     * Initialize our product with correct settings
     * @param Payload $payload
     * @return Payload
     */
    public function __invoke(Payload $payload) : Payload
    {
        $product = $payload->getProduct();
        $product->title = $payload->getQueue()->title;
        $product->body_html = $this->getBodyHtml($payload);
        $product->tags = $this->condenseTags($payload);
        $product->product_type = $this->getProductType($payload);
        $product->vendor = $this->getVendor($payload);
        $product->variants = [];
        $product->images = [];
        return $payload->setProduct($product);
    }

    /**
     * Condense our tags to a single cs v
     * @param Payload $payload
     * @return string
     */
    private function condenseTags(Payload $payload) : string
    {
        return implode(',', array_merge(array_filter(
            str_getcsv($payload->getQueue()->tags),
            str_getcsv($payload->getTemplate()->tags),
            str_getcsv($payload->getSetting()->tags)
        )));
    }

    /**
     * Extract the body_html
     * @param Payload $payload
     * @return string
     */
    protected function getBodyHtml(Payload $payload) : string
    {
        return $payload->getQueue()->description ?:
            $payload->getSetting()->description ?:
                $payload->getShop()->description ?:
                    $payload->getTemplate()->description;
    }

    /**
     * Extract our product type
     * @param Payload $payload
     * @return string
     */
    protected function getProductType(Payload $payload) : string
    {
        return $payload->getQueue()->product_type ?:
            $payload->getSetting()->product_type ?:
                $payload->getTemplate()->product_type;
    }

    /**
     * Extract our vendor
     * @param Payload $payload
     * @return string
     */
    protected function getVendor(Payload $payload) : string
    {
        return $payload->getQueue()->vendor ?:
            $payload->getSetting()->vendor ?:
                $payload->getTemplate()->vendor;
    }
}