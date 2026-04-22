<?php

namespace Tests;

use Firebase\JWT\JWT;

class ProfileTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────

    private function generateToken(array $overrides = []): string
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

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ── Health check ──────────────────────────────────────────────────

    public function testRootEndpoint(): void
    {
        $response = $this->get('/');
        $this->assertEquals(200, $response->status());
        $this->seeJson(['service' => 'profile-social-service']);
    }

    // ── JWT middleware ────────────────────────────────────────────────

    public function testFriendsRequireJwt(): void
    {
        $response = $this->get('/api/social/friends');
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token no proporcionado']);
    }

    public function testFriendsWithInvalidJwt(): void
    {
        $response = $this->get('/api/social/friends', ['Authorization' => 'Bearer invalid']);
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token inválido']);
    }

    public function testExpiredToken(): void
    {
        $token    = $this->generateToken(['exp' => time() - 100]);
        $response = $this->get('/api/social/friends', $this->authHeader($token));
        $this->assertEquals(401, $response->status());
        $this->seeJson(['error' => 'Token expirado']);
    }

    // ── Friendship endpoints ──────────────────────────────────────────

    public function testSendRequestRequiresReceiverId(): void
    {
        $token    = $this->generateToken();
        $response = $this->post('/api/social/friends/request', [], $this->authHeader($token));
        $this->assertEquals(422, $response->status());
    }

    public function testDirectoryRequiresJwt(): void
    {
        $response = $this->get('/api/social/directory');
        $this->assertEquals(401, $response->status());
    }

    public function testSearchRequiresQuery(): void
    {
        $token    = $this->generateToken();
        $response = $this->get('/api/social/directory/search', $this->authHeader($token));
        $this->assertEquals(422, $response->status());
    }

    public function testPendingEndpointWithJwt(): void
    {
        $token    = $this->generateToken();
        $response = $this->get('/api/social/friends/pending', $this->authHeader($token));
        // Debería funcionar (200) aunque esté vacío — solo verifica que la ruta existe
        $this->assertEquals(200, $response->status());
    }

    public function testFriendsListWithJwt(): void
    {
        $token    = $this->generateToken();
        $response = $this->get('/api/social/friends', $this->authHeader($token));
        $this->assertEquals(200, $response->status());
    }
}
