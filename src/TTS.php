<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class TTS extends Resource
{
    /**
     * POST /v1/text-to-speech/{voice_id}
     *
     * Keys: text (required), voice_id (default "default"), model_id,
     *       voice_settings, output_format, lang (shortcut).
     *
     * Returns array including base64 'audio', units_billed, cost_micros.
     */
    public function synthesize(array $input): array
    {
        $voiceId = $input['voice_id'] ?? 'default';
        $voiceSettings = $input['voice_settings'] ?? null;
        if (isset($input['lang'])) {
            $voiceSettings = $voiceSettings ?? [];
            $voiceSettings['lang'] = $input['lang'];
        }
        return $this->http->request('POST', '/v1/text-to-speech/' . rawurlencode($voiceId), null, [
            'text' => $input['text'],
            'model_id' => $input['model_id'] ?? 'tsage-tts-v1',
            'voice_settings' => $voiceSettings,
            'output_format' => $input['output_format'] ?? 'mp3_44100_128',
        ]);
    }

    public function synthesizeBytes(array $input): string
    {
        $resp = $this->synthesize($input);
        if (!isset($resp['audio'])) {
            throw new TsageException("TTS response had no 'audio' field");
        }
        return base64_decode($resp['audio']);
    }
}
