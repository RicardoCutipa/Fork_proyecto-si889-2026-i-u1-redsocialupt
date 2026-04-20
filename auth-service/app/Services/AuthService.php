<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Google\Client as GoogleClient;

class AuthService
{
    /**
     * Autentica al usuario con Google OAuth.
     *
     * Verifica el ID Token de Google, comprueba que el email sea
     * @virtual.upt.pe, y crea o encuentra al usuario en la BD.
     *
     * @param string $idToken ID Token de Google enviado desde el frontend
     * @return array Token JWT propio y datos del usuario
     * @throws \Exception Si el token es inválido o el dominio no es permitido
     */
    public function googleAuth(string $idToken): array
    {
        // Verificar el ID Token con Google
        $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            throw new \Exception('Token de Google inválido', 401);
        }

        $email    = $payload['email'];
        $googleId = $payload['sub'];
        $name     = $payload['name'] ?? '';
        $avatar   = $payload['picture'] ?? '';

        // Verificar dominio @virtual.upt.pe
        $domain = substr(strrchr($email, '@'), 1);
        if ($domain !== 'virtual.upt.pe') {
            throw new \Exception('Solo se permiten cuentas @virtual.upt.pe', 403);
        }

        // Crear o encontrar usuario
        $user = User::firstOrCreate(
            ['google_id' => $googleId],
            [
                'email'      => $email,
                'name'       => $name,
                'avatar_url' => $avatar,
                'role'       => 'user',
                'is_active'  => true,
            ]
        );

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            throw new \Exception('Tu cuenta ha sido desactivada', 403);
        }

        // Generar nuestro JWT
        $token = $this->generateJwt($user);

        return [
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'email'      => $user->email,
                'name'       => $user->name,
                'avatar_url' => $user->avatar_url,
                'role'       => $user->role,
            ],
        ];
    }

    /**
     * Obtiene los datos del usuario autenticado.
     *
     * @param int $userId
     * @return User
     * @throws \Exception Si el usuario no existe
     */
    public function getAuthenticatedUser(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new \Exception('Usuario no encontrado', 404);
        }

        return $user;
    }

    /**
     * Lista todos los usuarios (solo admin).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listUsers()
    {
        return User::all();
    }

    /**
     * Activa o desactiva un usuario (solo admin).
     *
     * @param int $userId
     * @return User
     * @throws \Exception Si el usuario no existe
     */
    public function toggleUser(int $userId): User
    {
        $user = User::find($userId);

        if (!$user) {
            throw new \Exception('Usuario no encontrado', 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $user;
    }

    /**
     * Genera un JWT firmado con los datos del usuario.
     *
     * @param User $user
     * @return string
     */
    private function generateJwt(User $user): string
    {
        $payload = [
            'sub'   => $user->id,
            'email' => $user->email,
            'role'  => $user->role,
            'iat'   => time(),
            'exp'   => time() + (env('JWT_EXPIRATION_MINUTES', 60) * 60),
        ];

        return JWT::encode(
            $payload,
            env('JWT_SECRET'),
            env('JWT_ALGORITHM', 'HS256')
        );
    }
}
