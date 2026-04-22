<?php

namespace App\Services;

use GuzzleHttp\Client;

class UserDirectoryService
{
    /**
     * Lista usuarios del auth-service con filtros opcionales (RF-07).
     * Consulta GET /api/auth/admin/users del auth-service internamente.
     * Para simplificar, el directorio consulta el auth-service vía HTTP.
     */
    public function listUsers(string $jwt, ?string $faculty = null, ?string $career = null)
    {
        $users = $this->fetchUsersFromAuth($jwt);

        if ($faculty) {
            $users = array_filter($users, fn($u) => ($u['faculty'] ?? '') === $faculty);
        }
        if ($career) {
            $users = array_filter($users, fn($u) => ($u['career'] ?? '') === $career);
        }

        return array_values($users);
    }

    /**
     * Buscar usuarios por nombre (RF-07).
     */
    public function search(string $jwt, string $query)
    {
        $users = $this->fetchUsersFromAuth($jwt);
        $query = strtolower($query);

        return array_values(array_filter($users, function ($u) use ($query) {
            $name     = strtolower($u['name'] ?? '');
            $fullName = strtolower($u['full_name'] ?? '');
            return str_contains($name, $query) || str_contains($fullName, $query);
        }));
    }

    /**
     * Obtiene la lista de usuarios desde auth-service.
     */
    private function fetchUsersFromAuth(string $jwt): array
    {
        try {
            $client   = new Client(['base_uri' => env('AUTH_SERVICE_URL', 'http://auth-service:8000')]);
            $response = $client->get('/api/auth/admin/users', [
                'headers' => ['Authorization' => 'Bearer ' . $jwt],
                'timeout' => 5,
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
