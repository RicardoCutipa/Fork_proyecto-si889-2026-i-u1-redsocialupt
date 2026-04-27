<?php

namespace Tests;

use App\Models\User;
use Firebase\JWT\JWT;

class AuthTest extends TestCase
{
    // ── Helpers ──────────────────────────────────────────────────────────

    private function generateTestToken(array $overrides = []): string
    {
        $payload = array_merge([
            'sub'   => 1,
            'email' => 'test@virtual.upt.pe',
            'role'  => 'user',
            'iat'   => time(),
            'exp'   => time() + 3600,
        ], $overrides);

        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    private function generateAdminToken(): string
    {
        return $this->generateTestToken(['role' => 'admin', 'sub' => 99]);
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function createUser(array $overrides = []): User
    {
        $sequence = User::count() + 1;

        $user = new User(array_merge([
            'google_id' => 'google-' . $sequence,
            'email' => 'test' . $sequence . '@virtual.upt.pe',
            'name' => 'Usuario Test ' . $sequence,
            'role' => 'user',
            'is_active' => true,
        ], $overrides));

        $user->save();

        return $user->fresh();
    }

    // ── Tests públicos ────────────────────────────────────────────────────

    public function testRootEndpoint(): void
    {
        $this->get('/');
        $this->seeStatusCode(200);
        $this->seeJson(['service' => 'auth-service']);
    }

    public function testGoogleAuthRequiresToken(): void
    {
        $this->post('/api/auth/google', []);
        $this->seeStatusCode(422);
    }

    public function testCorsPreflightAllowsTrustedOrigin(): void
    {
        $this->call('OPTIONS', '/api/auth/google', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        $this->seeStatusCode(200);
    }

    // ── Tests JWT middleware ──────────────────────────────────────────────

    public function testMeWithoutJwt(): void
    {
        $this->get('/api/auth/me');
        $this->seeStatusCode(401);
        $this->seeJson(['error' => 'Token no proporcionado']);
    }

    public function testMeWithInvalidJwt(): void
    {
        $this->get('/api/auth/me', ['Authorization' => 'Bearer token-invalido']);
        $this->seeStatusCode(401);
        $this->seeJson(['error' => 'Token inválido']);
    }

    public function testExpiredToken(): void
    {
        $token    = $this->generateTestToken(['exp' => time() - 100]);
        $this->get('/api/auth/verify', $this->authHeader($token));
        $this->seeStatusCode(401);
        $this->seeJson(['error' => 'Token expirado']);
    }

    // ── Tests endpoints protegidos ────────────────────────────────────────

    public function testVerifyWithValidJwt(): void
    {
        $token    = $this->generateTestToken();
        $this->get('/api/auth/verify', $this->authHeader($token));
        $this->seeStatusCode(200);
        $this->seeJson([
            'valid'   => true,
            'user_id' => 1,
            'email'   => 'test@virtual.upt.pe',
            'role'    => 'user',
        ]);
    }

    public function testLogout(): void
    {
        $token    = $this->generateTestToken();
        $this->post('/api/auth/logout', [], $this->authHeader($token));
        $this->seeStatusCode(200);
        $this->seeJson(['message' => 'Sesión cerrada correctamente']);
    }

    public function testCompleteProfileRequiresFullName(): void
    {
        $user = $this->createUser();
        $token = $this->generateTestToken(['sub' => $user->id]);
        $this->post('/api/auth/complete-profile', [], $this->authHeader($token));
        $this->seeStatusCode(422);
    }

    public function testUpdateProfileWithoutJwt(): void
    {
        $this->put('/api/auth/profile', ['bio' => 'Hola']);
        $this->seeStatusCode(401);
    }

    public function testMeWithExistingUser(): void
    {
        $user = $this->createUser([
            'full_name' => 'Prueba Auth',
            'is_profile_complete' => true,
        ]);

        $token = $this->generateTestToken([
            'sub' => $user->id,
            'email' => $user->email,
        ]);

        $this->get('/api/auth/me', $this->authHeader($token));
        $this->seeStatusCode(200);
        $this->seeJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function testBlockedUserCannotAccessProfile(): void
    {
        $user = $this->createUser([
            'is_active' => false,
            'blocked_reason' => 'Incumplio las reglas',
        ]);

        $token = $this->generateTestToken([
            'sub' => $user->id,
            'email' => $user->email,
        ]);

        $this->get('/api/auth/me', $this->authHeader($token));
        $this->seeStatusCode(403);
        $this->seeJson([
            'error' => 'Tu cuenta ha sido bloqueada',
            'code' => 'ACCOUNT_BLOCKED',
            'reason' => 'Incumplio las reglas',
        ]);
    }

    // ── Tests admin ───────────────────────────────────────────────────────

    public function testListUsersAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $this->get('/api/auth/admin/users', $this->authHeader($token));
        $this->seeStatusCode(403);
        $this->seeJson(['error' => 'No autorizado']);
    }

    public function testToggleUserAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $this->put('/api/auth/admin/users/1', [], $this->authHeader($token));
        $this->seeStatusCode(403);
    }

    public function testUpdateAcademicAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $this->put('/api/auth/admin/users/1/academic', [], $this->authHeader($token));
        $this->seeStatusCode(403);
    }

    public function testAdminCannotDeactivateSelf(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $token = $this->generateTestToken([
            'sub' => $admin->id,
            'role' => 'admin',
            'email' => $admin->email,
        ]);

        $this->put('/api/auth/admin/users/' . $admin->id, ['blocked_reason' => 'Prueba'], $this->authHeader($token));
        $this->seeStatusCode(422);
        $this->seeJson(['error' => 'No puedes desactivar tu propia cuenta']);
    }

    public function testAdminCanPromoteAnotherUser(): void
    {
        $admin = $this->createUser(['role' => 'admin']);
        $target = $this->createUser();
        $token = $this->generateTestToken([
            'sub' => $admin->id,
            'role' => 'admin',
            'email' => $admin->email,
        ]);

        $this->put('/api/auth/admin/users/' . $target->id . '/role', ['role' => 'admin'], $this->authHeader($token));
        $this->seeStatusCode(200);
        $this->seeJson([
            'message' => 'Usuario promovido a admin',
            'user' => ['role' => 'admin'],
        ]);
    }
}
