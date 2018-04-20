<?php

namespace App\Template;

class Hats extends AbstractTemplate implements TemplateInterface
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
                $price = '14.95';
                break;
        }
        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('_-_', $productData);
            $style = $specs[0];
            $color = $specs[1];
            $imageUrls[$style][$color] = $name;
        }
        $this->logger->debug(json_encode($imageUrls));
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
        foreach ($imageUrls as $style => $colors) {
            foreach ($colors as $color => $image) {
                $variantData = array(
                    'title' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat").' / '.$color,
                    'price' => $price,
                    'option1' => ($style == "Hat" ? "Trucker Hat" : "Cotton Twill Hat"),
                    'option2' => str_replace('_', ' ', $color),
                    'weight' => '5.0',
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['color'] = $color;
                $variantData['style'] = $style;
                $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
                unset($variantData['color']);
                unset($variantData['style']);
                if ($color == 'Navy' && $style == 'Hat') {
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
            $style = $variant->option1 == 'Trucker Hat' ? "Hat" : "TwillHat";
            $color = str_replace(' ', '_', $variant->option2);
            $image = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/{$imageUrls[$style][$color]}",
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
