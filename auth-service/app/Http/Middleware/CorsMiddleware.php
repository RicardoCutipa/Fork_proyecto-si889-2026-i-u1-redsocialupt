<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Agrega cabeceras CORS a todas las respuestas.
     * Responde inmediatamente a las solicitudes preflight OPTIONS.
     */
    public function handle($request, Closure $next)
    {
        // Responder directamente al preflight sin pasar por rutas
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
    }
}
