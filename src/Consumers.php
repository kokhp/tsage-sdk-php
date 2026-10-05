<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Consumers extends Resource
{
    public function signup(array $input): array
    {
        return $this->http->request('POST', '/v1/consumers/signup', null, [
            'email' => $input['email'],
            'referral_code' => $input['referral_code'] ?? null,
            'signup_ip' => $input['signup_ip'] ?? null,
            'device_fingerprint' => $input['device_fingerprint'] ?? null,
        ], null, false);
    }

    public function me(): array
    {
        return $this->http->request('GET', '/v1/consumers/me');
    }

    public function listApiKeys(): array
    {
        return $this->http->request('GET', '/v1/consumers/me/api-keys');
    }

    public function createApiKey(array $input = []): array
    {
        return $this->http->request('POST', '/v1/consumers/me/api-keys', null, [
            'name' => $input['name'] ?? null,
            'concurrent_limit' => $input['concurrent_limit'] ?? null,
        ]);
    }

    public function revokeApiKey(string $apiKeyId): array
    {
        return $this->http->request('DELETE', '/v1/consumers/me/api-keys/' . rawurlencode($apiKeyId));
    }

    public function usage(): array
    {
        return $this->http->request('GET', '/v1/consumers/me/usage');
    }
}
