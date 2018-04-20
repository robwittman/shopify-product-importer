<?php

namespace App\Template;

class GreyCollection extends AbstractTemplate implements TemplateInterface
{
    public function execute()
    {
        $queue = $this->queue;
        $results = array();
        $variants = array(
            'Hoodie' => array(
                'Small' => array('price' => '32.99', 'weight' => '16.1'),
                'Medium' => array('price' => '32.99', 'weight' => '17.5'),
                'Large' => array('price' => '32.99', 'weight' => '18.8'),
                'XL' => array('price' => '32.99', 'weight' => '21.2'),
                '2XL' => array('price' => '34.99', 'weight' => '22.9'),
                '3XL' => array('price' => '36.99', 'weight' => '24.1'),
                '4XL' => array('price' => '36.99', 'weight' => '24.5')
            ),
            'Long Sleeve' => array(
                'Small' => array('price' => '24.99', 'weight' => '7.6'),
                'Medium' => array('price' => '24.99', 'weight' => '8.8'),
                'Large' => array('price' => '24.99', 'weight' => '10.0'),
                'XL' => array('price' => '24.99', 'weight' => '10.3'),
                '2XL' => array('price' => '26.99', 'weight' => '12.4'),
                '3XL' => array('price' => '26.99', 'weight' => '12.6'),
                '4XL' => array('price' => '26.99', 'weight' => '13.6')
            ),
            'Tee' => array(
                'Small' => array('price' => '22.99', 'weight' => '5.6'),
                'Medium' => array('price' => '22.99', 'weight' => '6.3'),
                'Large' => array('price' => '22.99', 'weight' => '7.2'),
                'XL' => array('price' => '22.99', 'weight' => '8.0'),
                '2XL' => array('price' => '24.99', 'weight' => '8.7'),
                '3XL' => array('price' => '26.99', 'weight' => '9.8'),
                '4XL' => array('price' => '29.99', 'weight' => '10.2')
            ),
            'Crew' => array(
                'Small' => array('price' => '29.99', 'weight' => '5.6'),
                'Medium' => array('price' => '29.99', 'weight' => '6.3'),
                'Large' => array('price' => '29.99', 'weight' => '7.2'),
                'XL' => array('price' => '29.99', 'weight' => '8.0'),
                '2XL' => array('price' => '31.99', 'weight' => '8.7'),
                '3XL' => array('price' => '33.99', 'weight' => '9.8'),
                '4XL' => array('price' => '35.99', 'weight' => '10.2')
            ),
            'Tanks' => array(
                'Small' => array('price' => '24.99', 'weight' => '2.6'),
                'Medium' => array('price' => '24.99', 'weight' => '3.1'),
                'Large' => array('price' => '24.99', 'weight' => '3.2'),
                'XL' => array('price' => '24.99', 'weight' => '3.2'),
                '2XL' => array('price' => '26.99', 'weight' => '3.6')
            )
        );


        $data = $queue->data;
        $post = $data['post'];
        $image_data = getImages($queue->file_name);
        $imageUrls = [];

        switch($this->shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
                $variants = array(
                    'Hoodie' => array(
                        'Small' => array('price' => '20.00', 'weight' => '16.1'),
                        'Medium' => array('price' => '20.00', 'weight' => '17.5'),
                        'Large' => array('price' => '20.00', 'weight' => '18.8'),
                        'XL' => array('price' => '20.00', 'weight' => '21.2'),
                        '2XL' => array('price' => '22.00', 'weight' => '22.9'),
                        '3XL' => array('price' => '24.00', 'weight' => '24.1'),
                        '4XL' => array('price' => '26.00', 'weight' => '24.5')
                    ),
                    'Long Sleeve' => array(
                        'Small' => array('price' => '12.50', 'weight' => '7.6'),
                        'Medium' => array('price' => '12.50', 'weight' => '8.8'),
                        'Large' => array('price' => '12.50', 'weight' => '10.0'),
                        'XL' => array('price' => '12.50', 'weight' => '10.3'),
                        '2XL' => array('price' => '14.50', 'weight' => '12.4'),
                        '3XL' => array('price' => '16.50', 'weight' => '12.6'),
                        '4XL' => array('price' => '18.50', 'weight' => '13.6')
                    ),
                    'Tee' => array(
                        'Small' => array('price' => '11', 'weight' => '5.6'),
                        'Medium' => array('price' => '11', 'weight' => '6.3'),
                        'Large' => array('price' => '11', 'weight' => '7.2'),
                        'XL' => array('price' => '11', 'weight' => '8.0'),
                        '2XL' => array('price' => '13', 'weight' => '8.7'),
                        '3XL' => array('price' => '13', 'weight' => '9.8'),
                        '4XL' => array('price' => '13', 'weight' => '10.2')
                    ),
                    'Crew' => array(
                        'Small' => array('price' => '29.99', 'weight' => '5.6'),
                        'Medium' => array('price' => '29.99', 'weight' => '6.3'),
                        'Large' => array('price' => '29.99', 'weight' => '7.2'),
                        'XL' => array('price' => '29.99', 'weight' => '8.0'),
                        '2XL' => array('price' => '31.99', 'weight' => '8.7'),
                        '3XL' => array('price' => '33.99', 'weight' => '9.8'),
                        '4XL' => array('price' => '35.99', 'weight' => '10.2')
                    ),
                    'Tanks' => array(
                        'Small' => array('price' => '24.99', 'weight' => '2.6'),
                        'Medium' => array('price' => '24.99', 'weight' => '3.1'),
                        'Large' => array('price' => '24.99', 'weight' => '3.2'),
                        'XL' => array('price' => '24.99', 'weight' => '3.2'),
                        '2XL' => array('price' => '26.99', 'weight' => '3.6')
                    )
                );

        }
        $sku = $this->generateSku();
        $results = array(
            'product_name' => $queue->title,
            'shopify_product_admin_url' => null,
            'front_print_file_url' => $queue->front_print_url,
            'back_print_file_url' => $queue->back_print_url,
            'variants' => array()
        );

        foreach ($image_data as $name) {
            $productData = pathinfo($name)['filename'];
            $specs = explode('-', $productData);
            $style = $specs[0];
            if ($style == 'BellaFront') {
                $style = 'Tank';
            }
            $imageUrls[$style] = $name;
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
        foreach ($variants as $style => $sizes) {
            switch ($style) {
                case 'Hoodie':
                    $fulfillerCode = '18500';
                    break;
                case 'Long Sleeve':
                    $fulfillerCode = '2400';
                    break;
                case 'Tee':
                    $fulfillerCode = 'NL3600';
                    break;
                case 'Crew':
                    $style = 'Crewneck';
                    $fulfillerCode = '18000';
                    break;
                case 'Tanks':
                    $style = 'Tank';
                    $fulfillerCode = 'NL1533';
                    break;
            }
            foreach ($sizes as $size => $options) {
                $variantSku = $this->getVariantSku($sku, ($style == 'Crewneck' ? 'Crew' : $style), 'Grey');
                $results['variants'][] = array(
                    'garment_name' => $style,
                    'product_fulfiller_code' => $fulfillerCode,
                    'garment_color' => "Charcoal",
                    'product_sku' => $variantSku,
                );
                $variantData = array(
                    'title' => $size . ' / ' . $color . ' / ' . $style,
                    'price' => $options['price'],
                    'option1' => $size,
                    'option2' => 'Grey',
                    'option3' => $style,
                    'weight' => $options['weight'],
                    'weight_unit' => 'oz',
                    'requires_shipping' => true,
                    'inventory_management' => null,
                    'inventory_policy' => 'deny'
                );
                $variantData['size'] = $size;
                $variantData['style'] = $style;
                $variantData['sku'] = $this->generateLiquidSku($skuTemplate, $product_data, $variantData);
                unset($variantData['size']);
                unset($variantData['style']);
                $product_data['variants'][] = $variantData;
            }
        }
        $res = $this->callShopify('/admin/products.json', 'POST', array(
            'product' => $product_data
        ));
        $results['shopify_product_admin_url'] = "https://{$shop->myshopify_domain}/admin/products/{$res->product->id}";
        $imageUpdate = array();
        $variantMap = array(
            'Hoodie' => array(),
            'Long Sleeve' => array(),
            'Tee' => array(),
            'Crewneck' => array(),
            'Tank' => array()
        );
        foreach ($res->product->variants as $variant) {
            $style = $variant->option3;
            $variantMap[$style][] = $variant->id;
        }

        foreach ($variantMap as $style => $ids) {
            if ($style == 'Long Sleeve') {
                $style = 'LS';
            } else if ($style == 'Tank') {
                $style == 'Tanks';
            } elseif ($style == 'Crewneck') {
                $style = 'Crew';
            }
            $data = array(
                'src' => "https://s3.amazonaws.com/shopify-product-importer/".$imageUrls[$style],
                'variant_ids' => $ids
            );
            $imageUpdate[] = $data;
        }
        $res = $this->callShopify("/admin/products/{$res->product->id}.json", "PUT", array(
            "product" => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));
        if (isset($queue->log_to_google) && $queue->log_to_google && $this->shop->google_access_token) {
            if ($this->shop->google_access_token) {
                $this->client->setAccessToken($this->shop->google_access_token);
            }
            // logResults($this->client, $shop->google_sheet_slug, $post['print_type'], $results, $shop->id);
        } else {
            error_log("No google sync...");
        }
        return $res->product->id;
    }
}
