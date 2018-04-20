<?php

namespace App\Template;

class Raglans extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $prices = array(
            'Small' => array(
                'price' => '24.99',
                'weight' => '7.6',
            ),
            'Medium' => array(
                'price' => '24.99',
                'weight' => '8.8',
            ),
            'Large' => array(
                'price' => '24.99',
                'weight' => '10.0',
            ),
            'XL' => array(
                'price' => '24.99',
                'weight' => '10.3',
            ),
            '2XL' => array(
                'price' => '27.99',
                'weight' => '12.4',
            ),
            '3XL' => array(
                'price' => '27.99',
                'weight' => '13.2',
            ),
            '4XL' => array(
                'price' => '29.99',
                'weight' => '14.0',
            )
        );


        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];

        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $prices = array(
                    'Small' => array(
                        'price' => '12.50',
                        'weight' => '7.6',
                    ),
                    'Medium' => array(
                        'price' => '12.50',
                        'weight' => '8.8',
                    ),
                    'Large' => array(
                        'price' => '12.50',
                        'weight' => '10.0',
                    ),
                    'XL' => array(
                        'price' => '12.50',
                        'weight' => '10.3',
                    ),
                    '2XL' => array(
                        'price' => '12.50',
                        'weight' => '12.4',
                    ),
                    '3XL' => array(
                        'price' => '12.50',
                        'weight' => '13.2',
                    ),
                    '4XL' => array(
                        'price' => '14.50',
                        'weight' => '14.0',
                    )
                );
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
                'name' => "Size"
            ),
            array(
                'name' => "Color"
            ),
            array(
                'name' => "Style"
            )
        );
        $skuTemplate = $this->getSkuTemplate();
        foreach ($imageUrls as $color => $url) {
            $color = str_replace('_', ' ', $color);
            foreach ($prices as $size => $options) {
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / Raglan 3/4 Sleeve',
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'option3' => 'Raglan 3/4 Sleeve',
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['size'] = $size;
                $variantData['color'] = $color;
                $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
                unset($variantData['size']);
                unset($variantData['color']);
                if ($color == 'Navy' && $size == '30') {
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
