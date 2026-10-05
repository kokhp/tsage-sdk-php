<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Dialogue extends Resource
{
    public function generate(array $input): array
    {
        return $this->http->request('POST', '/v1/dialogue', null, [
            'turns' => $input['turns'],
            'output_format' => $input['output_format'] ?? 'mp3_44100_128',
        ]);
    }
}
