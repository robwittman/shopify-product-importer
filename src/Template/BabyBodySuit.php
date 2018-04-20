<?php

namespace App\Template;

class BabyBodySuit extends AbstractTemplate implements TemplateInterface
{
    protected $sizes = array(
        'Newborn',
        '6 Months',
        '12 Months',
        '18 Months',
        '24 Months'
    );

    public function execute()
    {
        $queue = $this->queue;
        $price = '14.99';

        $data = $queue->data;
        $post = $data['post'];
        $image_data = $this->getImages($queue->file_name);
        $imageUrls = [];

        if ($this->shop->myshopify_domain === 'plcwholesale.myshopify.com') {
            $price = '8.50';
        }
        foreach ($image_data as $name) {
            $imageUrls[] = $name;
        }

        $product_data = $this->getProductSettings();
        $product_data['options'] = array(
            array(
                'name' => "Size"
            ),
            array(
                'name' => "Color",

            )
        );
        $skuTemplate = $this->getSkuTemplate();
        foreach ($this->sizes as $size) {
            $imageUrl = $imageUrls[0];
            $variantData = array(
                'title' => $size .' / White',
                'price' => $price,
                'option1' => $size,
                'option2' => 'White',
                'weight' => '0.6',
                'weight_unit' => 'lb',
                'requires_shipping' => true,
                'inventory_management' => null,
                'inventory_policy' => 'deny'
            );
            $variantData['size'] = $size;
            $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $productData, $variantData);
            unset($variantData['size']);
            // 'sku' => 'Piper Lou - Baby Body Suit - White - '.$size
            $product_data['variants'][] = $variantData;
        }
        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));
        $variantIds = array();
        foreach ($res->product->variants as $variant) {
            $variantIds[] = $variant->id;
        }

        $res = $this->callShopify("/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => array(
                    array(
                        'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[0],
                        'variant_ids' => $variantIds
                    )
                )
            )
        ));
        return $res->product->id;
    }
}
