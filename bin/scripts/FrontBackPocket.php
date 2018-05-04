<?php
use App\Model\Queue;
use App\Model\Shop;
use App\Model\Template;
use App\Model\Setting;

function createFrontBackPocket(Queue $queue, Shop $shop, Template $template, Setting $setting = null)
{
    $prices = array(
        'Tee' => array(
            'small' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '14',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '16',
                'weight' => '0.0',
            )
        ),
        'Long Sleeve' => array(
            'small' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'medium' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'large' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            'XL' => array(
                'price' => '16',
                'weight' => '0.0',
            ),
            '2XL' => array(
                'price' => '18',
                'weight' => '0.0',
            )
        )
    );

    global $s3;
    $data = $queue->data;
    $post = $data['post'];
    $image_data = getImages($s3, $queue->file_name);
    $imageUrls = [];

    foreach ($image_data as $name) {
        $productData = pathinfo($name)['filename'];
        $specs = explode('_-_', $productData);
        $color = $specs[1];
        $imageUrls[$color] = $name;
    }
    $product_data = getProductSettings($shop, $queue, $template, $setting);
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
    $skuTemplate = getSkuTemplate($template, $setting, $queue);
    foreach ($prices as $style => $sizes) {
        foreach ($sizes as $size => $options) {
            foreach ($imageUrls as $color => $url) {
                $color = str_replace('_', ' ', $color);
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / ' . $style,
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => $color,
                    'option3' => $style,
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['size'] = $size;
                $variantData['color'] = $color;
                $variantData['style'] = $style;
                $variantData['sku'] = generateLiquidSku($skuTemplate, $product_data, $shop, $variantData, $post, $data['file_name'], $queue);
                unset($variantData['size']);
                unset($variantData['color']);
                unset($variantData['style']);
                $product_data['variants'][] = $variantData;
            }
        }
    }
    $res = callShopify($shop, '/admin/products.json', 'POST', array(
        'product' => $product_data
    ));
    $imageUpdate = array();
    $variantMap = array();

    foreach ($res->product->variants as $variant) {
        $size = $variant->option1;
        $color = str_replace(' ', '_', $variant->option2);
        if (!isset($variantMap[$color])) {
            $variantMap[$color] = array();
        }
        $variantMap[$color][] = $variant->id;
    }
    foreach ($variantMap as $color => $ids) {
        $data = array(
            'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$color],
            'variant_ids' => $ids
        );
        $imageUpdate[] = $data;
    }
    $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
        "product" => array(
            'id' => $res->product->id,
            'images' => $imageUpdate
        )
    ));

    return array($res->product->id);

}
