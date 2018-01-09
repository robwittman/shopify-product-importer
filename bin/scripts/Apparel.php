<?php

use App\Result\FrontPrint;

function processQueue($queue, Google_Client $client) {

    $vendor = 'Canvus Print';


    global $s3;
    $matrix = json_decode(file_get_contents(DIR.'/src/matrix.json'), true);
    if (!$matrix) {
        return "Unable to open matrix file";
    }
    // Ignore crew settings
    unset($matrix['Crew']);
    $image_data = array();
    $images = array();
    $queue->started_at = date('Y-m-d H:i:s');
    $data = json_decode($queue->data, true);

    if (isset($queue->file_name)) {
        $image_data = getImages($s3, $queue->file_name);
        $post = $data['post'];
        $results = array(
            'product_name' => $post['product_title'],
            'shopify_product_admin_url' => null,
            'front_print_file_url' => $post['front_print_url'],
            'back_print_file_url' => $post['back_print_url'],
            'variants' => array()
        );
        $shop = \App\Model\Shop::find($queue->shop);
        foreach ($image_data as $name) {
            if (pathinfo($name, PATHINFO_EXTENSION) != "jpg") {
                continue;
            }

            $chunks = explode('/', $name);
            if (strtolower(substr(basename($name, ".jpg"), -4)) == "pink") {
                $images[$garment]["Pink"] = $name;
            } else {
                $garment = $chunks[2];
                if(!in_array($garment, array(
                    'Hoodie','LS','Tanks','Tees'
                ))) {
                    continue;
                }
                $color = explode("-", basename($name, ".jpg"))[1];
                $images[$garment][$color] = $name;
            }
        }
        if ($shop->google_access_token) {
            $client->setAccessToken($shop->google_access_token);
        }

        switch($shop->myshopify_domain) {
            case 'plcwholesale.myshopify.com':
            case 'importer-testing.myshopify.com':
                $matrix = json_decode(file_get_contents(DIR.'/src/wholesale.json'), true);
                if (!$matrix) {
                    return "Unable to open matrix file";
                }
                unset($matrix['Crew']);
            case 'piper-lou-collection.myshopify.com':
                $html = "<meta charset='utf-8' />
<h5>Shipping &amp; Returns</h5>
<p>We want you to<span> </span><strong>LOVE</strong><span> </span>your Piper Lou items! They will ship out within 4-10 days from your order. If you're not 100% satisfied within the first 30 days of receiving your product, let us know and we'll make it right.</p>
<ul>
<li>Hassle free return/exchange policy! </li>
<li>Please contact us at<span> </span><strong>info@piperloucollection.com</strong><span> </span>with any questions. </li>
</ul>
<h5>Product Description</h5>
<p><span>You are going to <strong>LOVE</strong> this design! We offer apparel in Short Sleeve shirts, Long Sleeve Shirts, Tank tops, and Hoodies. If you want information on sizing, please view the sizing chart below. </span></p>
<p><span>Apparel is designed, printed, and shipped in the USA. 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 🇺🇲 </span></p>
<p><a href='https://www.piperloucollection.com/pages/sizing-chart'>View our sizing chart</a></p>";
                break;
            case 'hopecaregive.myshopify.com':
                $html = '<p><img src="https://cdn.shopify.com/s/files/1/1255/4519/files/16128476_220904601702830_291172195_n.jpg?9775130656601803865"></p><p>Designed, printed, and shipped in the USA!</p>';
                break;
            case 'game-slave.myshopify.com':
                $html = '<p><img src="https://cdn.shopify.com/s/files/1/1066/2470/files/TC_Best_seller.jpg?v=1486047696"></p><p>Designed, printed, and shipped in the USA!</p>';
                break;
            default:
                $html = '<p></p>';
        }

        if ($shop->description) {
            $html = $shop->description;
        }
        $sku = generateSku($shop, $post['product_title']);
        $results['product_name'] = $post['product_title'];
        $results['front_print_file_url'] = $post['front_print_url'];
        $results['back_print_file_url'] = $post['back_print_url'];
        $tags = explode(',', trim($post['tags']));
        $tags[] = 'apparel';
        $tags = implode(',', $tags);
        $product_data = array(
            'title'         => $post['product_title'],
            'body_html'     => $html,
            'tags'          => $tags,
            'vendor'        => $vendor,
            'product_type'  => 'Apparel',
            'options' => array(
                array(
                    'name' => "Size"
                ),
                array(
                    'name' => "Color"
                ),
                array(
                    'name' => "Style"
                )
            ),
            'variants'      => array(),
            'images'        => array()
        );

        $ignore = array(
            'Hoodie' => array(
                // 'Navy' => array('4XL'),
                // 'Royal' => array('4XL'),
                'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                // 'Charcoal' => array('4XL'),
                // 'Black' => array('4XL'),
            ),
            'Tee' => array(
                // 'Black' => array('4XL'),
                // 'Navy' => array('4XL'),
                // 'Royal' => array('4XL'),
                'Purple' => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                // 'Charcoal' => array('4XL'),
            ),
            'Tank'=> array(
                'Pink' => array(
                    'Small',
                    'Medium',
                    'Large',
                    'XL',
                    '2XL'
                )
            ),
            'Long Sleeve' => array(
                'Black'         => array('4XL'),
                'Navy'          => array('4XL'),
                'Royal Blue'    => array('4XL'),
                'Purple'        => array('Small','Medium','Large','XL','2XL','3XL','4XL'),
                'Grey'          => array('4XL'),
            )
        );

        foreach($images as $garment => $img) {
            if($garment == 'Tanks') {
                $fulfillerCode = 'NL1533';
                $garment = 'Tank';
            } else if($garment == 'Tees') {
                $garment = 'Tee';
                $fulfillerCode = 'NL3600';

            } else if($garment == "LS") {
                $garment = 'Long Sleeve';
                $fulfillerCode = '2400';
            } else if($garment == 'Hoodie') {
                $fulfillerCode = '18500';
            }
            foreach ($img as $color => $src) {
                $garmentColor = $color;
                if($color == "Royal") {
                    $color = "Royal Blue";
                } else if($color == "Charcoal") {
                    if ($fulfillerCode == 'NL1533') {
                        $garmentColor = 'Dark Grey';
                    } else {
                        $garmentColor = "Heavy Metal";
                    }
                    $color = "Grey";
                } else if($color == "Grey") {
                    $color = "Charcoal";
                }
                $variantSku = getVariantSku($sku, $garment, $color);
                $results['variants'][] = array(
                    'garment_name' => $garment,
                    'product_fulfiller_code' => $fulfillerCode,
                    'garment_color' => $garmentColor,
                    'product_sku' => $variantSku,
                );
                $variantSettings = $matrix[$garment];
                foreach($variantSettings['sizes'] as $size => $sizeSettings) {
                    if (isset($ignore[$garment]) &&
                    isset($ignore[$garment][$color])) {
                        if(is_array($ignore[$garment][$color])) {
                          if(in_array($size, $ignore[$garment][$color])) {
                             continue;
                           }
                        } else {
                            continue;
                        }
                    }
                    $varData = array(
                        'title' => "{$garment} \/ {$size} \/ {$color}",
                        'price' => $sizeSettings['price'],
                        'grams' => $sizeSettings['grams'],
                        'option1' => getSku($size),
                        'option2' => $color,
                        'option3' => $garment,
                        'weight' => $sizeSettings['weight'],
                        'weight_unit' => $sizeSettings['weight_unit'],
                        'requires_shipping' => true,
                        'inventory_management' => null,
                        'inventory_policy' => "deny",
                        'sku' => $variantSku
                    );

                    if($garment == $post['default_product'] && $color == $post['default_color'] && $size == 'Small') {
                        array_unshift($product_data['variants'], $varData);
                    } else {
                        $product_data['variants'][] = $varData;
                    }
                }
            }
        }

        $res = callShopify($shop, '/admin/products.json', 'POST', array('product' => $product_data));
        $results['shopify_product_admin_url'] = "https://{$shop->myshopify_domain}/admin/products/{$res->product->id}";
        $variantMap = array();
        $imageUpdate = array();

        foreach ($res->product->variants as $variant) {
            if(!isset($variantMap[$variant->option2])) {
                $variantMap[$variant->option2] = array();
            }
            if (!isset($variantMap[$variant->option2][$variant->option3])) {
                $variantMap[$variant->option2][$variant->option3] = array();
            }

            if($variant->option2 == "Royal Blue") {
                $variant->option2 = "Royal";
            } elseif ($variant->option2 == "Grey") {
                $variant->option2 = "Charcoal";
            }
            $variantMap[$variant->option2][$variant->option3][] = $variant->id;
        }

        foreach($variantMap as $color => $garments) {
            foreach($garments as $garment => $ids) {
                if($garment == "Tee") {
                    $search = "Tees";
                } elseif($garment == "Long Sleeve") {
                    $search = "LS";
                } elseif($garment == "Tank") {
                    $search = "Tanks";
                } else {
                    $search = $garment;
                }

                $data = array(
                    'src' => "https://s3.amazonaws.com/shopify-product-importer/".$images[$search][$color],
                    'variant_ids' => $ids
                );
                if($garment == $post['default_product'] && $color == $post['default_color']) {
                    $data['position'] = 1;
                }
                $imageUpdate[] = $data;
            }
        }

        $res = callShopify($shop, "/admin/products/{$res->product->id}.json", "PUT", array(
            'product' => array(
                'id' => $res->product->id,
                'images' => $imageUpdate
            )
        ));
        if (isset($queue->log_to_google) && $queue->log_to_google && $shop->google_access_token) {
            logResults($client, $shop->google_sheet_slug, $post['print_type'], $results);
        } else {
            error_log("No google sync...");
        }
        $queue->finish(array($res->product->id));
        return array($res->product->id);
    }
    return true;
}
