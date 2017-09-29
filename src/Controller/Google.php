<?php

namespace App\Controller;

use Google_Client;
use App\Model\Shop;

class Google
{
    protected $client;

    protected $flash;

    public function __construct(Google_Client $client, $flash)
    {
        $this->client = $client;
        $this->flash = $flash;
    }

    public function oauth($request, $response)
    {
        if (!$request->getQueryParam('code')) {
            $_SESSION['shop'] = $request->getQueryParam('shop_id');
            return $response->withHeader('Location', $this->client->createAuthUrl());
        } else {
            $creds = $this->client->fetchAccessTokenWithAuthCode($request->getQueryParam('code'));
            $shop = Shop::find($_SESSION['shop']);
            $shop->google_access_token = $creds['access_token'];
            $shop->google_expires_in = $creds['expires_in'];
            $shop->google_refresh_token = $creds['refresh_token'];
            $shop->google_created_at = $creds['google_created_at'];

            $shop->update();
            $this->flash->addMessage('message', "Google OAuth successful!");
        }

        return $response->withRedirect('/shops/'.$_SESSION['shop']);
    }
}
