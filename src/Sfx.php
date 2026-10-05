<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Sfx extends Resource
{
    public function generate(array $input): array
    {
        return $this->http->request('POST', '/v1/sound-effects', null, [
            'text' => $input['text'],
            'duration_seconds' => $input['duration_seconds'] ?? null,
            'prompt_influence' => $input['prompt_influence'] ?? null,
        ]);
    }
}
