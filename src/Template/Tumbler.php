<?php

namespace App\Template;

class Tumbler extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $this->logger->debug('Launching Tumbler');
        $queue = $this->queue;
        $image_data = array();
        $imageUrls = array();

        $data = $queue->data;

        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);

        $shopReq = [];

        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $size = $specs[0];
            $color = $specs[1];
            $imageUrls[$size][$color] = $name;
        }
        switch ($this->shop->myshopify_domain) {
            case 'piper-lou-collection.myshopify.com':
            case 'hopecaregive.myshopify.com':
            case 'game-slave.myshopify.com':
            default:
                $html = '<meta charset="utf-8" />'.
                        "<ul>".
                            "<li>2x heat &amp; cold retention (compared to plastic tumblers).</li>".
                            "<li>Double-walled vacuum insulation - Keeps Hot and Cold. </li>".
                            "<li>Fits most cup holders, Clear lid to protect from spills. </li>".
                            "<li>Sweat Free Design allows for a Strong Hold. </li>".
                            "<li>These tumblers will ship separately from our distributor in Texas. </li>".
                        '</ul>';
        }

        $product_data = array(
            'title' => $post['product_title'],
            'body_html' => $html,
            'tags' => $post['tags'],
            'vendor' => "ISIKEL",
            'product_type' => 'Tumbler',
            'options' => array(
                array(
                    'name' => "Size"
                ),
                array(
                    'name' => "Color"
                )
            ),
            'variants' => array(),
            'images' => array()
        );

        foreach ($imageUrls as $size => $colors) {
            $price = 24.99;
            if ($size == '30') {
                $price = 29.99;
            }
            foreach ($colors as $color => $image) {
                $optionColor = $color;
                if ($color == "Stainless") {
                    $optionColor = "Grey";
                }
                $skuColor = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => "{$size}oz /{$color}",
                    "price" => $price,
                    "option1" => "{$size}oz",
                    "option2" => str_replace('_', ' ', $optionColor),
                    "weight" => "1.1",
                    "weight_unit" => "lb",
                    "requires_shipping" => true,
                    "inventory_management" => null,
                    "inventory_policy" => "deny",
                    "sku" => "TX - T{$size} - {$skuColor} - Coated {$size}oz Tumbler"
                );
                if($color == 'Navy' && $size == '30') {
                    $product_data['variants'] = array_merge(array($variantData), $product_data['variants']);
                } else {
                    $product_data['variants'][] = $variantData;
                }
            }
        }

        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));

        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            $size = str_replace("oz", '', $variant->option1);
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                "src" => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$size][$color]}",
                'variant_ids' => [$variant->id]
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
