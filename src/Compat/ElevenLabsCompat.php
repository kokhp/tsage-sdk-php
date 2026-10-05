<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;
use TranslatorSage\Sdk\TsageException;

/**
 * Drop-in replacement for the ElevenLabs PHP SDK (elevenlabs/elevenlabs-php).
 *
 * The upstream SDK exposes:
 *   $client->textToSpeech->convert($voiceId, ['text' => '...', 'model_id' => '...']);
 *   $client->speechToText->convert(['file' => '/path', 'model_id' => 'scribe_v1']);
 *   $client->voices->getAll();
 *   $client->voices->add(['name' => '...', 'files' => ['https://...']]);
 *   $client->dubbing->create(['source_url' => '...', 'target_lang' => '...']);
 *
 * Swap `ElevenLabs\Client` → `TranslatorSage\Sdk\Compat\ElevenLabsCompat` and update the API key.
 */
class ElevenLabsCompat
{
    private const MODEL_ID_TO_TIER = [
        'scribe_v1' => 'standard',
        'scribe_standard' => 'standard',
        'scribe_live' => 'live',
        'scribe_medical' => 'medical',
        'scribe_raw' => 'raw',
    ];

    /** @var TsageClient */
    private $inner;

    /** @var object */ public $textToSpeech;
    /** @var object */ public $speechToText;
    /** @var object */ public $voices;
    /** @var object */ public $dubbing;

    public function __construct(?string $apiKey = null, array $options = [])
    {
        $this->inner = new TsageClient($apiKey, $options);
        $this->textToSpeech = new _CompatTTS($this->inner);
        $this->speechToText = new _CompatSTT($this->inner);
        $this->voices = new _CompatVoices($this->inner);
        $this->dubbing = new _CompatDubbing($this->inner);
    }

    /** @internal */
    public static function _modelIdToTier(string $modelId): string
    {
        return self::MODEL_ID_TO_TIER[$modelId] ?? 'standard';
    }
}

/** @internal */
final class _CompatTTS
{
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

/** @internal */
final class _CompatSTT
{
    private $inner;
    public function __construct(TsageClient $inner) { $this->inner = $inner; }

    public function convert(array $input): array
    {
        $tier = ElevenLabsCompat::_modelIdToTier($input['model_id'] ?? '');
        $file = $input['file'];
        if (is_string($file) && preg_match('/^https?:\/\//i', $file)) {
            return $this->inner->stt()->transcribe(['audio_url' => $file, 'model' => $tier]);
        }
        return $this->inner->stt()->transcribe(['audio' => $file, 'model' => $tier]);
    }
}

/** @internal */
final class _CompatVoices
{
    private $inner;
    public function __construct(TsageClient $inner) { $this->inner = $inner; }

    public function getAll(): array
    {
        return ['voices' => $this->inner->voices()->list()];
    }

    public function search(): array
    {
        return $this->getAll();
    }

    public function add(array $input): array
    {
        if (empty($input['files'])) {
            throw new TsageException('voices.add requires files=[hosted-url]');
        }
        $first = $input['files'][0];
        if (!is_string($first) || !preg_match('/^https?:\/\//i', $first)) {
            throw new TsageException('TranslatorSage requires a hosted audio URL for voices.add');
        }
        return $this->inner->voices()->add([
            'name' => $input['name'],
            'reference_audio_url' => $first,
        ]);
    }

    public function delete(string $voiceId): array
    {
        return $this->inner->voices()->delete($voiceId);
    }
}

/** @internal */
final class _CompatDubbing
{
    private $inner;
    public function __construct(TsageClient $inner) { $this->inner = $inner; }

    public function create(array $input): array
    {
        return $this->inner->dubbing()->create($input);
    }

    public function get(string $dubbingId): array
    {
        return $this->inner->dubbing()->get($dubbingId);
    }

    public function getAudio(string $dubbingId): array
    {
        return $this->inner->dubbing()->getAudio($dubbingId);
    }
}
