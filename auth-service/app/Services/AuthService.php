<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Google\Client as GoogleClient;

class AuthService
{
    private const ONLINE_WINDOW_SECONDS = 120;

    /**
     * Autentica con Google OAuth.
     * Verifica el ID Token, comprueba @virtual.upt.pe, crea o encuentra el usuario.
     */
    public function googleAuth(string $idToken): array
    {
        $client  = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            throw new \Exception('Token de Google inválido', 401);
        }

        $email    = $payload['email'];
        $googleId = $payload['sub'];
        $name     = $payload['name'] ?? '';
        $avatar   = $payload['picture'] ?? '';

        // Validar dominio
        if (substr(strrchr($email, '@'), 1) !== 'virtual.upt.pe') {
            throw new \Exception('Solo se permiten cuentas @virtual.upt.pe', 403);
        }

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

        if (!$user->is_active) {
            throw new \Exception('Tu cuenta ha sido desactivada', 403);
        }

        $user = $this->markPresence($user);

        return [
            'token'               => $this->generateJwt($user),
            'is_profile_complete' => $user->is_profile_complete,
            'user'                => $this->formatUser($user),
        ];
    }

    /**
     * Completa el perfil en el primer acceso (RF-01).
     */
    public function completeProfile(int $userId, array $data): array
    {
        $user = $this->findOrFail($userId);

        $user->update([
            'full_name'      => $data['full_name'],
            'user_type'      => $data['user_type']      ?? 'student',
            'faculty'        => $data['faculty']         ?? null,
            'career'         => $data['career']          ?? null,
            'academic_cycle' => $data['academic_cycle']  ?? null,
            'student_code'   => $data['student_code']    ?? null,
            'is_profile_complete' => true,
        ]);

        $user = $this->markPresence($user);

        return ['user' => $this->formatUser($user), 'token' => $this->generateJwt($user)];
    }

    /**
     * Actualiza avatar y bio del perfil (RF-06).
     */
    public function updateProfile(int $userId, array $data): array
    {
        $user = $this->findOrFail($userId);

        $user->update(array_filter([
            'avatar_url'     => $data['avatar_url'] ?? null,
            'banner_url'     => $data['banner_url'] ?? null,
            'bio'            => $data['bio']        ?? null,
            'academic_cycle' => $data['academic_cycle'] ?? null,
        ], fn($v) => $v !== null));

        $user = $this->markPresence($user);

        return ['user' => $this->formatUser($user), 'token' => $this->generateJwt($user)];
    }

    /**
     * Retorna los datos del usuario autenticado.
     */
    public function getAuthenticatedUser(int $userId): User
    {
        return $this->findOrFail($userId);
    }

    public function getAuthenticatedUserProfile(int $userId): array
    {
        $user = $this->markPresence($this->findOrFail($userId));
        return $this->formatUser($user);
    }

    public function touchPresence(int $userId): array
    {
        $user = $this->markPresence($this->findOrFail($userId));
        return $this->formatUser($user);
    }

    /**
     * Lista todos los usuarios (RF-09 — solo admin).
     */
    public function listUsers()
    {
        return User::orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtiene el perfil público de un usuario por su ID.
     */
    public function getUserById(int $userId): array
    {
        $user = $this->findOrFail($userId);
        return $this->formatUser($user);
    }

    /**
     * Lista pública de usuarios (sin paginación compleja por simplicidad).
     */
    public function listUsersPublic(?string $query = null, ?string $faculty = null, ?string $career = null, ?int $limit = null): array
    {
        $usersQuery = User::query()->where('is_active', true);

        $query = trim((string) $query);
        if ($query !== '') {
            $usersQuery->where(function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('career', 'like', $like)
                    ->orWhere('faculty', 'like', $like)
                    ->orWhere('student_code', 'like', $like);
            });
        }

        if ($faculty) {
            $usersQuery->where('faculty', $faculty);
        }

        if ($career) {
            $usersQuery->where('career', $career);
        }

        $usersQuery->orderBy('created_at', 'desc');

        if ($limit && $limit > 0) {
            $usersQuery->limit($limit);
        }

        $users = $usersQuery->get();
        $formatted = [];
        foreach ($users as $user) {
            $formatted[] = $this->formatUser($user);
        }
        return $formatted;
    }

    /**
     * Activa o desactiva un usuario (RF-09 — solo admin).
     */
    public function toggleUser(int $userId): User
    {
        $user = $this->findOrFail($userId);
        $user->is_active = !$user->is_active;
        $user->save();

        return $user;
    }

    /**
     * Actualiza datos académicos de un usuario (RF-09 — solo admin).
     */
    public function updateAcademic(int $userId, array $data): User
    {
        $user = $this->findOrFail($userId);

        $user->update(array_filter([
            'faculty'        => $data['faculty']        ?? null,
            'career'         => $data['career']         ?? null,
            'academic_cycle' => $data['academic_cycle'] ?? null,
            'student_code'   => $data['student_code']   ?? null,
            'user_type'      => $data['user_type']      ?? null,
        ], fn($v) => $v !== null));

        return $user->fresh();
    }

    /**
     * Actualiza el rol de un usuario (solo admin).
     */
    public function updateRole(int $targetUserId, string $role, int $actorUserId): User
    {
        $user = $this->findOrFail($targetUserId);

        if (!in_array($role, ['user', 'admin'], true)) {
            throw new \Exception('Rol invalido', 422);
        }

        if ($user->id === $actorUserId && $role !== 'admin') {
            throw new \Exception('No puedes quitarte tus propios permisos de administrador', 422);
        }

        if ($user->role === 'admin' && $role !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                throw new \Exception('Debe existir al menos un administrador activo', 422);
            }
        }

        $user->role = $role;
        $user->save();

        return $user->fresh();
    }

    // ─── Privados ─────────────────────────────────────────

    private function findOrFail(int $userId): User
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('Usuario no encontrado', 404);
        }
        return $user;
    }

    private function markPresence(User $user): User
    {
        $user->forceFill([
            'last_seen_at' => Carbon::now(),
        ])->save();

        return $user->fresh();
    }

    private function isUserOnline(User $user): bool
    {
        if (!$user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->greaterThanOrEqualTo(Carbon::now()->subSeconds(self::ONLINE_WINDOW_SECONDS));
    }

    private function generateJwt(User $user): string
    {
        $payload = [
            'sub'        => $user->id,
            'email'      => $user->email,
            'name'       => $user->full_name ?: $user->name,
            'full_name'  => $user->full_name,
            'school'     => $user->career,
            'career'     => $user->career,
            'faculty'    => $user->faculty,
            'role'       => $user->role,
            'avatar_url' => $user->avatar_url,
            'iat'        => time(),
            'exp'        => time() + (env('JWT_EXPIRATION_MINUTES', 60) * 60),
        ];

        return JWT::encode($payload, env('JWT_SECRET'), env('JWT_ALGORITHM', 'HS256'));
    }

    private function formatUser(User $user): array
    {
        return [
            'id'                  => $user->id,
            'email'               => $user->email,
            'name'                => $user->name,
            'full_name'           => $user->full_name,
            'avatar_url'          => $user->avatar_url,
            'banner_url'          => $user->banner_url,
            'faculty'             => $user->faculty,
            'career'              => $user->career,
            'school'              => $user->career,
            'student_code'        => $user->student_code,
            'academic_cycle'      => $user->academic_cycle,
            'bio'                 => $user->bio,
            'last_seen_at'        => $user->last_seen_at?->toIso8601String(),
            'is_online'           => $this->isUserOnline($user),
            'user_type'           => $user->user_type,
            'role'                => $user->role,
            'is_profile_complete' => $user->is_profile_complete,
        ];
    }
}
