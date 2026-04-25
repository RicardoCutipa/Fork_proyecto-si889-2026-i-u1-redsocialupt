<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'America/Lima'));

/*
|--------------------------------------------------------------------------
| Crear la aplicación
|--------------------------------------------------------------------------
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Registrar Singletons
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Laravel\Lumen\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Laravel\Lumen\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Registrar Middleware Global
|--------------------------------------------------------------------------
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Registrar Middleware de Ruta
|--------------------------------------------------------------------------
*/

$app->routeMiddleware([
    'jwt' => App\Http\Middleware\JwtMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Registrar Rutas
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
