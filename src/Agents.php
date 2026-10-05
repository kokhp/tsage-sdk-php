<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Agents extends Resource
{
    public function converse(string $agentId, array $input): array
    {
        return $this->http->request('POST', '/v1/agents/' . rawurlencode($agentId) . '/converse', null, [
            'message' => $input['message'],
            'hints' => $input['hints'] ?? null,
        ]);
    }
}
