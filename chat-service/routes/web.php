<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () {
    return response()->json([
        'service' => 'chat-service',
        'status'  => 'running',
        'version' => '1.0.0',
    ]);
});

$router->group(['prefix' => 'api/chat', 'middleware' => 'jwt'], function () use ($router) {

    // ── Chat privado (RF-08) ──────────────────────────────────────────
    $router->post('/messages',          'MessageController@send');
    $router->get('/messages/{userId}',  'MessageController@conversation');
    $router->get('/inbox',              'MessageController@inbox');
});
