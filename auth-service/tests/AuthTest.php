<?php

namespace Tests;

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

    // ── Tests públicos ────────────────────────────────────────────────────

    public function testRootEndpoint(): void
    {
        $response = $this->get('/');
        $this->assertEquals(200, $response->status());
        $this->seeJson(['service' => 'auth-service']);
    }

    public function testGoogleAuthRequiresToken(): void
    {
        $response = $this->post('/api/auth/google', []);
        $this->assertEquals(422, $response->status());
    }

    // ── Tests JWT middleware ──────────────────────────────────────────────

    public function testMeWithoutJwt(): void
    {
        $response = $this->get('/api/auth/me');
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token no proporcionado']);
    }

    public function testMeWithInvalidJwt(): void
    {
        $response = $this->get('/api/auth/me', ['Authorization' => 'Bearer token-invalido']);
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token inválido']);
    }

    public function testExpiredToken(): void
    {
        $token    = $this->generateTestToken(['exp' => time() - 100]);
        $response = $this->get('/api/auth/verify', $this->authHeader($token));
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token expirado']);
    }

    // ── Tests endpoints protegidos ────────────────────────────────────────

    public function testVerifyWithValidJwt(): void
    {
        $token    = $this->generateTestToken();
        $response = $this->get('/api/auth/verify', $this->authHeader($token));
        $this->assertEquals(200, $response->status());
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
        $response = $this->post('/api/auth/logout', [], $this->authHeader($token));
        $this->assertEquals(200, $response->status());
        $this->seeJson(['message' => 'Sesión cerrada correctamente']);
    }

    public function testCompleteProfileRequiresFullName(): void
    {
        $token    = $this->generateTestToken();
        $response = $this->post('/api/auth/complete-profile', [], $this->authHeader($token));
        $this->assertEquals(422, $response->status());
    }

    public function testUpdateProfileWithoutJwt(): void
    {
        $response = $this->put('/api/auth/profile', ['bio' => 'Hola']);
        $this->assertEquals(401, $response->status());
    }

    // ── Tests admin ───────────────────────────────────────────────────────

    public function testListUsersAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $response = $this->get('/api/auth/admin/users', $this->authHeader($token));
        $this->assertEquals(403, $response->status());
        $this->seeJson(['error' => 'No autorizado']);
    }

    public function testToggleUserAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $response = $this->put('/api/auth/admin/users/1', [], $this->authHeader($token));
        $this->assertEquals(403, $response->status());
    }

    public function testUpdateAcademicAsNonAdmin(): void
    {
        $token    = $this->generateTestToken(['role' => 'user']);
        $response = $this->put('/api/auth/admin/users/1/academic', [], $this->authHeader($token));
        $this->assertEquals(403, $response->status());
    }
}
