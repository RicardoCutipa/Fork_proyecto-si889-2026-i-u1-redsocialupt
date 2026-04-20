<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * POST /api/auth/google
     *
     * Autentica al usuario con Google OAuth.
     * Si es la primera vez, crea el usuario automáticamente.
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $this->validate($request, [
            'id_token' => 'required|string',
        ]);

        try {
            $result = $this->authService->googleAuth($request->input('id_token'));

            return response()->json($result, 200);
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 500;
            return response()->json(['error' => $e->getMessage()], $code);
        }
    }

    /**
     * POST /api/auth/logout
     *
     * Cierra sesión. El frontend descarta el JWT.
     */
    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    /**
     * GET /api/auth/me
     *
     * Retorna los datos del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser($request->auth->sub);

            return response()->json([
                'id'         => $user->id,
                'email'      => $user->email,
                'name'       => $user->name,
                'avatar_url' => $user->avatar_url,
                'role'       => $user->role,
                'is_active'  => $user->is_active,
                'created_at' => $user->created_at,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/auth/verify
     *
     * Verifica que el JWT es válido. Uso inter-servicio.
     */
    public function verify(Request $request): JsonResponse
    {
        return response()->json([
            'valid'   => true,
            'user_id' => $request->auth->sub,
            'email'   => $request->auth->email,
            'role'    => $request->auth->role,
        ], 200);
    }

    /**
     * GET /api/auth/admin/users
     *
     * Lista todos los usuarios (solo admin).
     */
    public function listUsers(Request $request): JsonResponse
    {
        if ($request->auth->role !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $users = $this->authService->listUsers();

        return response()->json($users, 200);
    }

    /**
     * PUT /api/auth/admin/users/{id}
     *
     * Activa o desactiva un usuario (solo admin).
     */
    public function toggleUser(Request $request, int $id): JsonResponse
    {
        if ($request->auth->role !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $user = $this->authService->toggleUser($id);

            return response()->json([
                'message'   => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
                'user'      => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
