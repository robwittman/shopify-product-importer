<?php

require_once 'bootstrap.php';

use App\Model\Shop;

$shops = Shop::all();

foreach ($shops as $shop) {
    $data = callShopify($shop, "/admin/shop.json");
    $shop->domain = $data->shop->domain;
    $shop->name = $data->shop->name;
    $shop->save();
}
