<?php

namespace App\Pipeline;

/**
 * The object that traverses the pipeline
 */
class ProductMetaContainer
{
    protected $shop;

    protected $file;

    protected $post;

    protected $queue;

    protected $settings;

    protected $images;

    protected $product = array(
        'variants' => array(),
        'images' => array()
    );

    public function setShop(Shop $shop)
    {
        $this->shop = $shop;
        return $this;
    }

    public function setTemplate(Template $template)
    {
        $this->template = $template;
        return $this;
    }

    public function setPostData($post)
    {
        $this->post = $post;
        return $this;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function setSettings(Setting $setting)
    {
        $this->setting = $setting;
        return $this;
    }

    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    public function setImages(array $images)
    {
        $this->images = $images;
        return $this;
    }

    public function getShop()
    {
        return $this->shop;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function getPostData()
    {
        return $this->post;
    }

    public function getSettings()
    {
        return $this->setting;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getImages()
    {
        return $this->images;
    }
}
