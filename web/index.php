<?php

require_once('../vendor/autoload.php');

$app = new Slim\App(array(
    'settings' => $settings
));

$app->get('/', function ($request, $response) {
    return $response->withJson(array('success' => true));
});

$app->run();
