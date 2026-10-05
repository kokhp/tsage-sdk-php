<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;
use TranslatorSage\Sdk\TsageException;

final class CompatVoices
{
    /** @var TsageClient */
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
