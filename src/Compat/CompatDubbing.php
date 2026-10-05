<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Compat;

use TranslatorSage\Sdk\TsageClient;

final class CompatDubbing
{
    /** @var TsageClient */
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
