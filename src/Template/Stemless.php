<?php

namespace App\Template;

class Stemless extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $price = '26.99';

        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];
        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $price = '12.50';
                break;
        }

        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $color = $specs[1];
            $imageUrls[$color] = $name;
        }
        $product_data = $this->getProductSettings();
        $product_data['options'] = array(
            array(
                'name' => "Color"
            )
        );
        $store_name = '';
        switch ($this->shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'plcwholesale.myshopify.com':
                $store_name = 'Piper Lou - ';
                break;
        }
        if ($queue->sub_template_id == 'etched') {
            $slug = 'W12M';
        } else {
            $slug = 'W12G';
        }
        $skuTemplate = $this->getSkuTemplate();
        foreach ($imageUrls as $color => $url) {
            $sku = $color;
            if ($color == 'Grey') {
                $sku = 'Stainless Steel';
            }
            $variantData = array(
                'title' => $color,
                'price' => $price,
                'option1' => str_replace('_', ' ', $color),
                'weight' => '10',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
            $variantData['size'] = $size;
            $variantData['color'] = str_replace('_', ' ', $color);
            $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
            unset($variantData['size']);
            unset($variantData['color']);
            if ($color == 'Navy') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));
        $imageUpdate = array();
        foreach ($res->product->variants as $variant) {
            $color = str_replace(' ', '_', $variant->option1);
            $image = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
                'variant_ids' => array($variant->id)
            );
            $imageUpdate[] = $image;
        }
        $res = $this->callShopify("/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));

        return $res->product->id;
    }
}
