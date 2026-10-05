<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Voices extends Resource
{
    public function list(): array
    {
        return $this->http->request('GET', '/v1/voices');
    }

    public function add(array $input): array
    {
        return $this->http->request('POST', '/v1/voices/add', null, [
            'name' => $input['name'],
            'reference_audio_url' => $input['reference_audio_url'],
            'lang_support' => $input['lang_support'] ?? null,
            'consent_doc_url' => $input['consent_doc_url'] ?? null,
        ]);
    }

    public function promoteToPvc(string $voiceId): array
    {
        return $this->http->request('POST', '/v1/voices/' . rawurlencode($voiceId) . '/professional');
    }

    public function delete(string $voiceId): array
    {
        return $this->http->request('DELETE', '/v1/voices/' . rawurlencode($voiceId));
    }
}
