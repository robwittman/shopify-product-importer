<?php

require_once('../vendor/autoload.php');

$app = new Slim\App(array(
    'settings' => $settings
));
$container = $app->getContainer();
$container['view'] = function($c) {
    $view = new \Slim\Views\Twig('../views');
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath(), '/'));
    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    return $view;
};

$app->get('/', function ($request, $response) {
    return $this->view->render($response, 'profile.html', [
        'name' => 'Rob Wittman'
    ]);
});

$app->run();
