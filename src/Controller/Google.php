<?php

namespace App\Controller;

use Google_Client;
use App\Model\Shop;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;

class Google
{
    /**
     * @var Google_Client
     */
    protected $client;

    /**
     * @var Messages
     */
    protected $flash;

    public function __construct(Google_Client $client, Messages $flash)
    {
        $this->client = $client;
        $this->flash = $flash;
    }

    public function oauth(ServerRequestInterface $request, ResponseInterface $response)
    {
        if (!$request->getQueryParam('code')) {
            $_SESSION['shop'] = $request->getQueryParam('shop_id');
            $this->client->setApprovalPrompt('force');
            return $response->withHeader('Location', $this->client->createAuthUrl());
        } else {
            $creds = $this->client->fetchAccessTokenWithAuthCode($request->getQueryParam('code'));
            $shop = Shop::find($_SESSION['shop']);
            error_log(json_encode($creds));
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
