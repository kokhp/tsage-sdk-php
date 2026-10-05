<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;

final class CompatTTS
{
    /** @var TsageClient */
    private $inner;

    public function __construct(TsageClient $inner) { $this->inner = $inner; }

    public function convert(string $voiceId, array $input): array
    {
        return $this->inner->tts()->synthesize([
            'voice_id' => $voiceId,
            'text' => $input['text'],
            'model_id' => $input['model_id'] ?? 'tsage-tts-v1',
            'voice_settings' => $input['voice_settings'] ?? null,
            'output_format' => $input['output_format'] ?? 'mp3_44100_128',
        ]);
    }
}
