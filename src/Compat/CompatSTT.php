<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;

final class CompatSTT
{
    /** @var TsageClient */
    private $inner;

    public function __construct(TsageClient $inner) { $this->inner = $inner; }

    public function convert(array $input): array
    {
        $tier = ElevenLabsCompat::modelIdToTier($input['model_id'] ?? '');
        $file = $input['file'];
        if (is_string($file) && preg_match('/^https?:\/\//i', $file)) {
            return $this->inner->stt()->transcribe(['audio_url' => $file, 'model' => $tier]);
        }
        return $this->inner->stt()->transcribe(['audio' => $file, 'model' => $tier]);
    }
}
