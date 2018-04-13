<?php

require_once 'bootstrap.php';

use App\Model\Shop;

$shops = Shop::all();

foreach ($shops as $shop) {
    try {
        if (!is_null($shop->google_access_token)) {
            $creds = $client->refreshToken($shop->google_refresh_token);
            $shop->google_access_token = $creds['access_token'];
            $shop->google_refresh_token = $creds['refresh_token'];
            $shop->save();
        }
    } catch (\Exception $e) {
        error_log($e->getMessage());
    }
}
