<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

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
