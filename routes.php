<?php

use App\Model\Shop;
use App\Model\User;
use App\Model\Template;

$app->get('/', function($request, $response) {
    return $response->withRedirect('/app');
});

/*========================================
    User Routes
 =======================================*/
$app->group('/users', function () use ($app) {
    $app->get('', "controller.users:index");
    $app->get('/create', "controller.users:create");
    $app->post('', "controller.users:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "controller.users:show");
        $app->post('/access', "controller.users:access");
        $app->post('', "controller.users:update");
        $app->delete('/delete', "controller.users:delete");
    });
});

$app->group('/google', function() use ($app) {
    $app->get('/oauth', "controller.google:oauth");
    $app->post('/sheet', "controller.shops:setSheet");
});

/*=========================================
    Auth Routes
=========================================*/
$app->group('/auth', function () use ($app) {
    $app->post('/login', 'controller.auth:login');
});

/*=========================================
    Shop Routes
=========================================*/
$app->group('/shops', function () use ($app) {
    $app->get('', "controller.shops:index");
    $app->post('', "controller.shops:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "controller.shops:show");
        $app->post('', "controller.shops:update");
        $app->delete('/delete', "controller.shops:delete");
    });
});

// TODO: Move this to separate file
/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', 'controller.products:show_form');
$app->post('/products', 'controller.products:create');
$app->group('/queue', function() use ($app) {
    $app->get('', 'controller.products:queue');
    $app->post('/restart', 'controller.products:restart_queue');
});

$app->get('/templates', function($request, $response) {
    return $response->withJson(array(
        'templates' => array(
            new Template('single_product', 'Single Product'),
            new Template('grey_collection', 'Grey Collection'),
            new Template('stemless', 'Stemless'),
            new Template('hats', 'Hats'),
            new Template('masculine_hats', 'Masculine Hats'),
            new Template('drinkware', 'Drinkware'),
            new Template('uv_drinkware', 'UV Drinkware'),
            new Template('uv_with_bottles', 'UV With Bottles'),
            new Template('baby_body_suit', 'Baby Body Suit'),
            new Template('raglans', 'Raglans'),
            new Template('front_back_pocket', 'Front Back Pocket'),
            new Template('christmas', 'Christmas')
        )
    ));
});

$app->post('/files', 'controller.files:create');
$app->get('/files/{fileName}', 'controller.files:get');
