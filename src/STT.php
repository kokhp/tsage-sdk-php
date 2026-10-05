<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class STT extends Resource
{
    public function transcribe(array $input): array
    {
        $audio = $input['audio'] ?? null;
        $audioUrl = $input['audio_url'] ?? null;
        $model = $input['model'] ?? 'standard';
        $filename = $input['filename'] ?? 'audio.wav';
        $durationHint = $input['duration_hint_seconds'] ?? null;

        if ($audio === null && $audioUrl === null) {
            throw new \InvalidArgumentException("transcribe() requires 'audio' or 'audio_url'");
        }
        if ($audio === null && $audioUrl !== null) {
            $audio = file_get_contents($audioUrl);
            if ($audio === false) {
                throw new TsageException('could not fetch audio_url: ' . $audioUrl);
            }
            $parsed = parse_url($audioUrl, PHP_URL_PATH);
            if ($parsed && strpos(basename($parsed), '.') !== false) {
                $filename = basename($parsed);
            }
        }
        if (is_string($audio) && strlen($audio) < 4096 && @is_file($audio)) {
            // treat as path
            $audio = file_get_contents($audio);
        }

        return $this->http->request('POST', '/v1/speech-to-text',
            ['model' => $model, 'duration_hint_seconds' => $durationHint],
            null,
            ['audio' => ['file' => (string) $audio, 'filename' => $filename, 'content_type' => 'application/octet-stream']]
        );
    }
}
