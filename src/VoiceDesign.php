<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class VoiceDesign extends Resource
{
    public function design(array $input): array
    {
        return $this->http->request('POST', '/v1/voice-design', null, [
            'description' => $input['description'],
            'text' => $input['text'] ?? null,
            'gender' => $input['gender'] ?? null,
            'age' => $input['age'] ?? null,
            'accent' => $input['accent'] ?? null,
        ]);
    }
}
