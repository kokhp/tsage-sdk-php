<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class Translate extends Resource
{
    public function text(array $input): array
    {
        return $this->http->request('POST', '/v1/translate', null, [
            'text' => $input['text'],
            'target_lang' => $input['target_lang'],
            'source_lang' => $input['source_lang'] ?? null,
        ]);
    }
}
