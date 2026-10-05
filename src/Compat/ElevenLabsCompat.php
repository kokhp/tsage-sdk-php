<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;

/**
 * Drop-in replacement for the ElevenLabs PHP SDK.
 *
 * Usage:
 *   use TranslatorSage\Sdk\Compat\ElevenLabsCompat;
 *   $client = new ElevenLabsCompat('tsa_live_...');
 *   $client->textToSpeech->convert('default', ['text' => 'Hi', 'model_id' => 'tsage-tts-v1']);
 */
class ElevenLabsCompat
{
    public const MODEL_ID_TO_TIER = [
        'scribe_v1' => 'standard',
        'scribe_standard' => 'standard',
        'scribe_live' => 'live',
        'scribe_medical' => 'medical',
        'scribe_raw' => 'raw',
    ];

    /** @var TsageClient */
    private $inner;

    /** @var CompatTTS */ public $textToSpeech;
    /** @var CompatSTT */ public $speechToText;
    /** @var CompatVoices */ public $voices;
    /** @var CompatDubbing */ public $dubbing;

    public function __construct(?string $apiKey = null, array $options = [])
    {
        $this->inner = new TsageClient($apiKey, $options);
        $this->textToSpeech = new CompatTTS($this->inner);
        $this->speechToText = new CompatSTT($this->inner);
        $this->voices = new CompatVoices($this->inner);
        $this->dubbing = new CompatDubbing($this->inner);
    }

    public static function modelIdToTier(string $modelId): string
    {
        return self::MODEL_ID_TO_TIER[$modelId] ?? 'standard';
    }
}
