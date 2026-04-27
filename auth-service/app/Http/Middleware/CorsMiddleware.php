<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    private const DEFAULT_ALLOWED_ORIGINS = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:8080',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8080',
    ];

    /**
     * Agrega cabeceras CORS a todas las respuestas.
     * Responde inmediatamente a las solicitudes preflight OPTIONS.
     */
    public function handle($request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = $this->allowedOrigins();
        $isAllowedOrigin = $origin && in_array($origin, $allowedOrigins, true);

        // Responder directamente al preflight sin pasar por rutas
        if ($request->isMethod('OPTIONS')) {
            if (!$isAllowedOrigin) {
                return response()->json(['error' => 'Origen no permitido'], 403);
            }

            return response('', 204)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Vary', 'Origin');
        }

        $response = $next($request);

        if ($isAllowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    private function allowedOrigins(): array
    {
        $origins = env('CORS_ALLOWED_ORIGINS');
        if (is_string($origins) && trim($origins) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $origins))));
        }

        return self::DEFAULT_ALLOWED_ORIGINS;
    }
}
