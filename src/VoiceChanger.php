<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class VoiceChanger extends Resource
{
    public function convert(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && @is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/voice-changer',
            ['target_voice_id' => $input['target_voice_id']],
            null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'input.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}
