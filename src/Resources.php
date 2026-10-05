<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

abstract class Resource
{
    /** @var HttpTransport */
    protected $http;

    public function __construct(HttpTransport $http)
    {
        $this->http = $http;
    }
}

class Consumers extends Resource
{
    public function signup(array $input): array
    {
        return $this->http->request('POST', '/v1/consumers/signup', null, [
            'email' => $input['email'],
            'referral_code' => $input['referral_code'] ?? null,
            'signup_ip' => $input['signup_ip'] ?? null,
            'device_fingerprint' => $input['device_fingerprint'] ?? null,
        ], null, false);
    }

    public function me(): array
    {
        return $this->http->request('GET', '/v1/consumers/me');
    }

    public function listApiKeys(): array
    {
        return $this->http->request('GET', '/v1/consumers/me/api-keys');
    }

    public function createApiKey(array $input = []): array
    {
        return $this->http->request('POST', '/v1/consumers/me/api-keys', null, [
            'name' => $input['name'] ?? null,
            'concurrent_limit' => $input['concurrent_limit'] ?? null,
        ]);
    }

    public function revokeApiKey(string $apiKeyId): array
    {
        return $this->http->request('DELETE', '/v1/consumers/me/api-keys/' . rawurlencode($apiKeyId));
    }

    public function usage(): array
    {
        return $this->http->request('GET', '/v1/consumers/me/usage');
    }
}

class TTS extends Resource
{
    /**
     * POST /v1/text-to-speech/{voice_id}
     *
     * Keys: text (required), voice_id (default "default"), model_id,
     *       voice_settings, output_format, lang (shortcut).
     *
     * Returns array including base64 'audio', units_billed, cost_micros.
     */
    public function synthesize(array $input): array
    {
        $voiceId = $input['voice_id'] ?? 'default';
        $voiceSettings = $input['voice_settings'] ?? null;
        if (isset($input['lang'])) {
            $voiceSettings = $voiceSettings ?? [];
            $voiceSettings['lang'] = $input['lang'];
        }
        return $this->http->request('POST', '/v1/text-to-speech/' . rawurlencode($voiceId), null, [
            'text' => $input['text'],
            'model_id' => $input['model_id'] ?? 'tsage-tts-v1',
            'voice_settings' => $voiceSettings,
            'output_format' => $input['output_format'] ?? 'mp3_44100_128',
        ]);
    }

    public function synthesizeBytes(array $input): string
    {
        $resp = $this->synthesize($input);
        if (!isset($resp['audio'])) {
            throw new TsageException("TTS response had no 'audio' field");
        }
        return base64_decode($resp['audio']);
    }
}

class STT extends Resource
{
    public function transcribe(array $input): array
    {
        $audio = $input['audio'] ?? null;
        $audioUrl = $input['audio_url'] ?? null;
        $model = $input['model'] ?? 'standard';
        $filename = $input['filename'] ?? 'audio.wav';
        $durationHint = $input['duration_hint_seconds'] ?? null;

        if ($audio === null && $audioUrl === null) {
            throw new \InvalidArgumentException("transcribe() requires 'audio' or 'audio_url'");
        }
        if ($audio === null && $audioUrl !== null) {
            $audio = file_get_contents($audioUrl);
            if ($audio === false) {
                throw new TsageException('could not fetch audio_url: ' . $audioUrl);
            }
            $parsed = parse_url($audioUrl, PHP_URL_PATH);
            if ($parsed && strpos(basename($parsed), '.') !== false) {
                $filename = basename($parsed);
            }
        }
        if (is_string($audio) && strlen($audio) < 4096 && is_file($audio)) {
            // treat as path
            $audio = file_get_contents($audio);
        }

        return $this->http->request('POST', '/v1/speech-to-text',
            ['model' => $model, 'duration_hint_seconds' => $durationHint],
            null,
            ['audio' => ['file' => (string) $audio, 'filename' => $filename, 'content_type' => 'application/octet-stream']]
        );
    }
}

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

class Dubbing extends Resource
{
    public function create(array $input): array
    {
        return $this->http->request('POST', '/v1/dubbing', null, [
            'source_url' => $input['source_url'] ?? ($input['video_url'] ?? null),
            'source_lang' => $input['source_lang'] ?? null,
            'target_lang' => $input['target_lang'],
            'num_speakers' => $input['num_speakers'] ?? null,
            'watermark' => $input['watermark'] ?? true,
            'name' => $input['name'] ?? null,
        ]);
    }

    public function get(string $jobId): array
    {
        return $this->http->request('GET', '/v1/dubbing/' . rawurlencode($jobId));
    }

    public function getAudio(string $jobId): array
    {
        return $this->http->request('GET', '/v1/dubbing/' . rawurlencode($jobId) . '/audio');
    }

    public function wait(string $jobId, float $pollIntervalSeconds = 5.0, float $timeoutSeconds = 3600.0): array
    {
        $deadline = microtime(true) + $timeoutSeconds;
        while (true) {
            $job = $this->get($jobId);
            if (in_array($job['status'] ?? '', ['succeeded', 'failed'], true)) {
                return $job;
            }
            if (microtime(true) > $deadline) {
                throw new TsageException('timed out waiting for dubbing job ' . $jobId);
            }
            usleep((int) ($pollIntervalSeconds * 1_000_000));
        }
    }
}

class Voices extends Resource
{
    public function list(): array
    {
        return $this->http->request('GET', '/v1/voices');
    }

    public function add(array $input): array
    {
        return $this->http->request('POST', '/v1/voices/add', null, [
            'name' => $input['name'],
            'reference_audio_url' => $input['reference_audio_url'],
            'lang_support' => $input['lang_support'] ?? null,
            'consent_doc_url' => $input['consent_doc_url'] ?? null,
        ]);
    }

    public function promoteToPvc(string $voiceId): array
    {
        return $this->http->request('POST', '/v1/voices/' . rawurlencode($voiceId) . '/professional');
    }

    public function delete(string $voiceId): array
    {
        return $this->http->request('DELETE', '/v1/voices/' . rawurlencode($voiceId));
    }
}

class Sfx extends Resource
{
    public function generate(array $input): array
    {
        return $this->http->request('POST', '/v1/sound-effects', null, [
            'text' => $input['text'],
            'duration_seconds' => $input['duration_seconds'] ?? null,
            'prompt_influence' => $input['prompt_influence'] ?? null,
        ]);
    }
}

class VoiceDesign extends Resource
{
    public function design(array $input): array
    {
        return $this->http->request('POST', '/v1/voice-design', null, [
            'description' => $input['description'],
            'text' => $input['text'] ?? null,
            'gender' => $input['gender'] ?? null,
            'age' => $input['age'] ?? null,
            'accent' => $input['accent'] ?? null,
        ]);
    }
}

class VoiceChanger extends Resource
{
    public function convert(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/voice-changer',
            ['target_voice_id' => $input['target_voice_id']],
            null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'input.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}

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

class AudioIsolation extends Resource
{
    public function isolate(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/audio-isolation', null, null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'audio.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}

class Align extends Resource
{
    public function align(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/align',
            ['transcript' => $input['transcript']], null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'audio.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}

class Diarize extends Resource
{
    public function diarize(array $input): array
    {
        $audio = $input['audio'];
        if (is_string($audio) && strlen($audio) < 4096 && is_file($audio)) {
            $audio = file_get_contents($audio);
        }
        return $this->http->request('POST', '/v1/diarize',
            ['num_speakers' => $input['num_speakers'] ?? null], null,
            ['audio' => ['file' => (string) $audio, 'filename' => $input['filename'] ?? 'audio.wav', 'content_type' => 'application/octet-stream']]
        );
    }
}

class Agents extends Resource
{
    public function converse(string $agentId, array $input): array
    {
        return $this->http->request('POST', '/v1/agents/' . rawurlencode($agentId) . '/converse', null, [
            'message' => $input['message'],
            'hints' => $input['hints'] ?? null,
        ]);
    }
}
