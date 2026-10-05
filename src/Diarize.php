<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Diarize extends Resource
{
    public function diarize(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && @is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/diarize',
            ['num_speakers' => $input['num_speakers'] ?? null], null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'audio.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}
