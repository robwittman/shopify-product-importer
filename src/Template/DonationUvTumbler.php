<?php

namespace App\Template;

class DonationUvTumbler extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $prices = array(
            '30' => '39.99',
            '20' => '34.99'
        );


        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];
        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $prices = array(
                    '30' => '20.00',
                    '20' => '17.50'
                );
                break;
        }
        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $size = $specs[0];
            $color = $specs[1];
            $imageUrls[$size][$color] = $name;
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
        foreach ($imageUrls as $size => $colors) {
            foreach ($colors as $color => $url) {
                $color = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => $size. ' / '.$color,
                    'price' => $prices[$size],
                    'option1' => $size.'oz Tumbler',
                    'option2' => $color,
                    'weight' => '1.1',
                    'weight_unit' => 'lb',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['size'] = $size;
                $variantData['color'] = $color;
                $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
                unset($variantData['size']);
                unset($variantData['color']);
                if ($color == 'Black' && $size == '30') {
                    $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
                } else {
                    $product_data['variants'][] = $variantData;
                }
            }
        }
        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));
        $imageUpdate = array();
        foreach ($res->product->variants as $variant) {
            $size = $variant->option1;
            $color = str_replace(' ', '_', $variant->option2);
            switch ($size) {
                case '30oz Tumbler':
                    $size = '30';
                    break;
                case '20oz Tumbler':
                    $size = '20';
                    break;
            }
            $image = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
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
