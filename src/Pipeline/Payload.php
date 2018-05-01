<?php

namespace App\Pipeline;

use App\Model\Queue;
use App\Model\Shop;
use App\Model\SubTemplate;
use App\Model\Template;
use Shopify\Object\Product;

class Payload
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var Shop
     */
    protected $shop;

    /**
     * @var array
     */
    protected $images = [];

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var SubTemplate
     */
    protected $subTemplate;

    protected $setting;

    public function __construct()
    {
        $this->product = new Product();
    }

    public function setProduct(Product $product) : self
    {
        $this->product = $product;
        return $this;
    }

    public function getProduct() : Product
    {
        return $this->product;
    }

    public function setMetadata(Metadata $metadata) : self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getMetadata() : Metadata
    {
        return $this->metadata;
    }

    public function setShop(Shop $shop) : self
    {
        $this->shop = $shop;
        return $this;
    }

    public function getShop() : Shop
    {
        return $this->shop;
    }

    public function setImages(array $images) : self
    {
        $this->images = $images;
        return $this;
    }

    public function getImages() : array
    {
        return $this->images;
    }

    public function setQueue(Queue $queue) : self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getQueue() : Queue
    {
        return $this->queue;
    }

    public function setTemplate(Template $template) : self
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate() : Template
    {
        return $this->template;
    }

    public function setSubTemplate(SubTemplate $subTemplate) : self
    {
        $this->subTemplate = $subTemplate;
        return $this;
    }

    public function getSubTemplate() : SubTemplate
    {
        return $this->subTemplate;
    }

    public function setSetting(Setting $setting) : self
    {
        $this->setting = $setting;
        return $this;
    }

    public function getSetting() : Setting
    {
        return $this->setting;
    }
}