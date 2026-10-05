<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use TranslatorSage\Sdk\TsageClient;

$client = new TsageClient(getenv('TSAGE_API_KEY') ?: null);

// 1. Text to speech
$resp = $client->tts()->synthesize([
    'text' => 'Hello from the TranslatorSage PHP SDK.',
    'voice_id' => 'default',
    'lang' => 'en',
]);
file_put_contents('hello.mp3', base64_decode($resp['audio']));
printf("Wrote hello.mp3 (%d units)\n", $resp['units_billed']);

// 2. Translation
$t = $client->translate()->text(['text' => 'The sage speaks.', 'target_lang' => 'ja']);
printf("Translated: %s\n", $t['translated_text']);

// 3. Voices
$voices = $client->voices()->list();
printf("Voices available: %d\n", count($voices));
