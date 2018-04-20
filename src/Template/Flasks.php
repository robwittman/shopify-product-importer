<?php

namespace App\Template;

class Flasks extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $price = '19.99';

        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];

        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $price = '12.00';
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
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            )
        );

        $skuTemplate = $this->getSkuTemplate();
        foreach ($imageUrls as $color => $url) {
            $variantData = array(
                'title' => '6oz / '.$color,
                'price' => $price,
                'option1' => '6oz',
                'option2' => $color,
                'weight' => '1.1',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
            $variantData['color'] = $color;
            $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
            unset($variantData['color']);

            if ($color == 'Blue') {
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
            $size = $variant->option1;
            $color = $variant->option2;
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
