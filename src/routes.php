<?php

use App\Model\Shop;
use App\Model\User;

$app->get('/', function ($request, $response) {
    return $response->withRedirect('/products');
});

#
#   Uncomment to tu admin user in database
#
// $app->get('/init', function ($request, $response) {
//     $user = new \App\Model\User();
//     $user->email = 'admin@admin.com';
//     $user->password = 'password';
//     $user->role = 'admin';
//     $user->save();
//     var_dump($user);
// });
/*========================================
    User Routes
 =======================================*/
$app->group('/users', function () use ($app) {
    $app->get('', "UserController:index");
    $app->get('/create', "UserController:create");
    $app->post('', "UserController:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "UserController:show");
        $app->map(array("GET", "POST"), '/access', "UserController:access");
        $app->post('', "UserController:update");
        $app->map(array("GET", "POST"), '/delete', "UserController:delete");
        $app->map(array("GET", "POST"), '/settings', "UserController:settings");
    });
})->add(new App\Middleware\Authorization());

$app->group('/google', function() use ($app) {
    $app->get('/oauth', "GoogleAuthController:oauth");
    $app->post('/sheet', "ShopController:setSheet");
});

/*=========================================
    Auth Routes
=========================================*/
$app->group('/auth', function () use ($app) {
    $app->map(array('GET', 'POST'), '/login', 'AuthController:login');
    $app->any('/logout', 'AuthController:logout');
});

$app->get('/colors', 'ColorController:index');
$app->post('/colors', 'ColorController:create')->add(new \App\Middleware\Authorization());
$app->get('/colors/search', 'ColorController:search')->add(new \App\Middleware\Authorization());

$app->group('/templates', function() use ($app) {
    $app->get('', 'TemplatesController:index');

    $app->post('/children/update', 'SubTemplatesController:update');

    $app->group('/{id}', function() use ($app) {
        $app->get('', 'TemplatesController:show');
        $app->post('', 'TemplatesController:update');
        $app->post('/children', 'SubTemplatesController:create');
        $app->post('/children/{subId}/delete', 'SubTemplatesController:delete');
    });
});

/*=========================================
    Shop Routes
=========================================*/
$app->group('/shops', function () use ($app) {
    $app->get('', "ShopController:index");
    $app->get('/create', "ShopController:create");
    $app->post('', "ShopController:create");
    $app->group('/{id}', function () use ($app) {
        $app->get('', "ShopController:show");
        $app->post('', "ShopController:update");
        $app->map(array("GET", "POST"), '/delete', "ShopController:delete");
        $app->get('/settings', "ShopController:settings");
        $app->post('/settings/{templateId}', 'ShopController:update_settings');
    });
})->add(new App\Middleware\Authorization());

// TODO: Move this to separate file
/*========================================
    Product Upload and Review
========================================*/
$app->get('/products', 'ProductController:show_form')->add(new \App\Middleware\Authorization());
$app->post('/products', 'ProductController:create')->add(new \App\Middleware\Authorization());
$app->map(['GET', 'POST'], '/products/batch', 'ProductController:batch')->add(new \App\Middleware\Authorization());

$app->group('/queue', function() use ($app) {
    $app->get('', 'QueuesController:index');
    $app->get('/{id}', 'QueuesController:show');
    $app->post('/restart', 'ProductController:restart_queue');
})->add(new \App\Middleware\Authorization());
