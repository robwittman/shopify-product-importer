<?php

namespace App\Template;

class Wholesale extends AbstractTemplate implements TemplateInterface
{
    protected $requiresMatrix = true;

    protected $isWholesale = true;

    public function execute()
    {
        $queue = $this->queue;
        global $s3;

        // Ignore crew settings
        unset($this->matrix['Crew']);
        $image_data = array();
        $images = array();

        $data = $queue->data;

        $image_data = $this->getImages($queue->file_name);
        $designId = null;

        $post = $data['post'];
        $details = $this->matrix[$queue->sub_template_id];
        foreach ($image_data as $name) {
            $chunks = explode('/', $name);
            $fileName = $chunks[count($chunks) -1];
            $pieces = explode('-', basename($fileName, '.jpg'));
            $images[str_replace('_', ' ', trim($pieces[1], '_'))] = $name;
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
        foreach ($images as $color => $src) {
            foreach($details['sizes'] as $size) {
                $varData = array(
                    'title' => "{$size} \/ {$color}",
                    'price' => ($size == '2XL') ? $details['price'] + $details['modifier'] : $details['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'weight' => $details['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => "deny"
                );

                $variantData['size'] = $size;
                $variantData['color'] = $color;
                $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
                unset($variantData['size']);
                unset($variantData['color']);
                if($color == $post['default_color'] && $size == 'S') {
                    array_unshift($product_data['variants'], $varData);
                } else {
                    $product_data['variants'][] = $varData;
                }
            }
        }

        $res = $this->callShopify('/admin/products.json', 'POST', array('product' => $product_data));
        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            if(!isset($variantMap[$variant->option2])) {
                $variantMap[$variant->option2] = array();
            }
            $variantMap[$variant->option2][] = $variant->id;
        }
        foreach($variantMap as $color => $ids) {
            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$color],
                'variant_ids' => $ids
            );
            if($color == $post['default_color']) {
                $data['position'] = 1;
            }
            $imageUpdate[] = $data;
        }
        $res = $this->callShopify("/admin/products/{$res->product->id}.json", "PUT", array(
            'product' => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));
        return $res->product->id;
    }
}
