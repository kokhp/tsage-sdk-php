<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use TranslatorSage\Sdk\TsageClient;
use TranslatorSage\Sdk\HttpTransport;
use TranslatorSage\Sdk\AuthException;
use TranslatorSage\Sdk\Compat\ElevenLabsCompat;

final class TsageClientTest extends TestCase
{
    /** @var array<int,array{method:string,url:string,headers:array,body:string|null}> */
    private $calls;

    private function clientWithQueue(array $responses): TsageClient
    {
        $this->calls = [];
        $queue = $responses;
        $exec = function (string $method, string $url, array $opts) use (&$queue) {
            $this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $opts['headers'], 'body' => $opts['body']];
            $resp = array_shift($queue);
            return [
                'status' => $resp['status'],
                'body' => $resp['body'],
                'headers' => $resp['headers'] ?? ['content-type' => 'application/json'],
            ];
        };
        $http = new HttpTransport('tsa_test_abc', 'https://api.test', 60, 1, $exec);
        return new TsageClient(null, ['http' => $http]);
    }

    public function testTtsSynthesizeSendsBearerAndBody(): void
    {
        $client = $this->clientWithQueue([
            [
                'status' => 200,
                'body' => json_encode([
                    'audio' => base64_encode('fake-audio-bytes'),
                    'units_billed' => 5,
                    'cost_micros' => 500,
                    'balance_after_micros' => 1_999_500,
                ]),
            ],
        ]);
        $resp = $client->tts()->synthesize(['text' => 'Hello', 'voice_id' => 'default', 'lang' => 'en']);
        $this->assertSame(5, $resp['units_billed']);
        $this->assertSame('fake-audio-bytes', base64_decode($resp['audio']));

        $call = $this->calls[0];
        $this->assertSame('POST', $call['method']);
        $this->assertStringEndsWith('/v1/text-to-speech/default', $call['url']);
        $this->assertContains('Authorization: Bearer tsa_test_abc', $call['headers']);
        $decoded = json_decode($call['body'], true);
        $this->assertSame('Hello', $decoded['text']);
        $this->assertSame('tsage-tts-v1', $decoded['model_id']);
        $this->assertSame('en', $decoded['voice_settings']['lang']);
    }

    public function testTranslate(): void
    {
        $client = $this->clientWithQueue([
            ['status' => 200, 'body' => json_encode(['translated_text' => 'salut', 'target_lang' => 'fr'])],
        ]);
        $resp = $client->translate()->text(['text' => 'hi', 'target_lang' => 'fr']);
        $this->assertSame('salut', $resp['translated_text']);
    }

    public function testVoicesList(): void
    {
        $client = $this->clientWithQueue([
            ['status' => 200, 'body' => json_encode([['voice_id' => 'default', 'name' => 'Default']])],
        ]);
        $voices = $client->voices()->list();
        $this->assertCount(1, $voices);
        $this->assertSame('default', $voices[0]['voice_id']);
    }

    public function testAuthErrorAfterRetries(): void
    {
        $client = $this->clientWithQueue([
            ['status' => 401, 'body' => json_encode(['detail' => 'invalid key'])],
            ['status' => 401, 'body' => json_encode(['detail' => 'invalid key'])],
        ]);
        $this->expectException(AuthException::class);
        $client->consumers()->me();
    }

    public function testElevenLabsCompat(): void
    {
        $this->calls = [];
        $exec = function (string $method, string $url, array $opts) {
            $this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $opts['headers'], 'body' => $opts['body']];
            return [
                'status' => 200,
                'body' => json_encode(['audio' => 'AA==', 'units_billed' => 1]),
                'headers' => ['content-type' => 'application/json'],
            ];
        };
        $http = new HttpTransport('tsa_test_abc', 'https://api.test', 60, 1, $exec);
        // Inject inner client the ElevenLabs compat uses by crafting one manually.
        $compat = new ElevenLabsCompat(null, ['http' => $http]);
        $resp = $compat->textToSpeech->convert('default', ['text' => 'Hi', 'model_id' => 'tsage-tts-v1']);
        $this->assertSame(1, $resp['units_billed']);
        $this->assertStringEndsWith('/v1/text-to-speech/default', $this->calls[0]['url']);
    }
}
