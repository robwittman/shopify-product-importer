<?php

namespace App\Template;

class MasculineHats extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $price = '29.99';

        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];
        $html = '<p></p>';
        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $price = '12.50';
                break;
        }
        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $color = preg_replace('%([a-z])([A-Z])%', '\1-\2', $specs[1]);
            $imageUrls[trim($color, '_')] = $name;
        }

        $product_data = $this->getProductSettings();
        $product_data['options'] = array(
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        );
        $skuTemplate = $this->getSkuTemplate();
        switch ($this->shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'plcwholesale.myshopify.com':
                $store_name = 'Piper Lou - ';
                break;
        }
        foreach ($imageUrls as $color => $image) {
            $variantData = array(
                'title' => "Trucker Hat / ".$color,
                'price' => $price,
                'option1' => "Trucker Hat",
                'option2' => str_replace('_', ' ', $color),
                'weight' => '5.0',
                'weight_unit' => 'oz',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );

            $variantData['color'] = $color;
            $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
            unset($variantData['color']);

            if ($color == 'Navy' && $style == 'Hat') {
                $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
            } else {
                $product_data['variants'][] = $variantData;
            }
        }
        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));
        $variantMap = array();
        $imageUpdate = array();
        foreach ($res->product->variants as $variant) {
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$color]}",
                'variant_ids' => [$variant->id]
            );
            $imageUpdate[] = $image;
        };
        $res = $this->callShopify("/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));

        return $res->product->id;
    }
}
