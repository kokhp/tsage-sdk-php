<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Align extends Resource
{
    public function align(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && @is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/align',
            ['transcript' => $input['transcript']], null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'audio.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}
