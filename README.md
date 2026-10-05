# TranslatorSage PHP SDK

Official PHP SDK for the [TranslatorSage API](https://developers.translatorsage.com) — TTS, STT, dubbing, translate, voices, agents, and more.

- **PSR-4 autoload**, PHP 7.4+
- **Zero external deps**: `ext-curl` + `ext-json`
- **ElevenLabs drop-in** via `TranslatorSage\Sdk\Compat\ElevenLabsCompat`
- 401/429 retry with exponential backoff

## Install

```sh
composer require translatorsage/sdk
```

or during the alpha, pin to the GitHub repo:

```json
{
  "repositories": [{ "type": "vcs", "url": "https://github.com/kokhp/tsage-sdk-php" }],
  "require": { "translatorsage/sdk": "dev-main" }
}
```

## Auth

```php
use TranslatorSage\Sdk\TsageClient;

$client = new TsageClient(getenv('TSAGE_API_KEY'));
// or override the endpoint:
// $client = new TsageClient('tsa_live_...', ['base_url' => 'https://staging.translatorsage.com']);
```

## Quickstart

### Text to speech

```php
$resp = $client->tts()->synthesize([
    'text' => 'Hello from the TranslatorSage PHP SDK.',
    'voice_id' => 'default',
    'lang' => 'en',
]);
file_put_contents('hello.mp3', base64_decode($resp['audio']));
```

### Translate

```php
$t = $client->translate()->text([
    'text' => 'The sage speaks.',
    'target_lang' => 'ja',
]);
echo $t['translated_text'];
```

### Dubbing (poll until done)

```php
$job = $client->dubbing()->create([
    'source_url'   => 'https://cdn.example.com/clip.mp4',
    'target_lang'  => 'es',
    'num_speakers' => 2,
]);
$done = $client->dubbing()->wait($job['job_id'], 5.0);
$audio = $client->dubbing()->getAudio($done['job_id']);
```

### ElevenLabs drop-in

```php
use TranslatorSage\Sdk\Compat\ElevenLabsCompat;

$client = new ElevenLabsCompat(getenv('TSAGE_API_KEY'));
$audio = $client->textToSpeech->convert('default', [
    'text' => 'Hi', 'model_id' => 'tsage-tts-v1',
]);
$voices = $client->voices->getAll();
```

## Resource reference

| Namespace                     | Methods                                                                   |
|-------------------------------|---------------------------------------------------------------------------|
| `$client->consumers()`        | `signup`, `me`, `listApiKeys`, `createApiKey`, `revokeApiKey`, `usage`    |
| `$client->tts()`              | `synthesize`, `synthesizeBytes`                                           |
| `$client->stt()`              | `transcribe`                                                              |
| `$client->translate()`        | `text`                                                                    |
| `$client->dubbing()`          | `create`, `get`, `getAudio`, `wait`                                       |
| `$client->voices()`           | `list`, `add`, `promoteToPvc`, `delete`                                   |
| `$client->sfx()`              | `generate`                                                                |
| `$client->voiceDesign()`      | `design`                                                                  |
| `$client->voiceChanger()`     | `convert`                                                                 |
| `$client->dialogue()`         | `generate`                                                                |
| `$client->audioIsolation()`   | `isolate`                                                                 |
| `$client->align()`            | `align`                                                                   |
| `$client->diarize()`          | `diarize`                                                                 |
| `$client->agents()`           | `converse`                                                                |

## Testing

```sh
composer install
vendor/bin/phpunit
```

## Reference

Full OpenAPI spec: [developers.translatorsage.com](https://developers.translatorsage.com)

## License

Apache-2.0
