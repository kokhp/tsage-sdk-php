<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

/**
 * Minimal cURL-based HTTP transport with:
 *  - Bearer auth
 *  - JSON encode/decode
 *  - multipart/form-data for uploads
 *  - 401/429 retry with exponential backoff
 *
 * Public so advanced users can inject a mock in tests.
 */
class HttpTransport
{
    public const USER_AGENT = 'tsage-sdk-php/0.1.0-alpha';

    /** @var string */
    private $apiKey;
    /** @var string */
    private $baseUrl;
    /** @var int */
    private $timeout;
    /** @var int */
    private $maxRetries;
    /** @var callable|null  function(string $method, string $url, array $options): array{status:int,body:string,headers:array} */
    private $executor;

    public function __construct(?string $apiKey = null, string $baseUrl = TsageClient::DEFAULT_BASE_URL, int $timeout = 60, int $maxRetries = 3, ?callable $executor = null)
    {
        $this->apiKey = $apiKey ?? '';
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->executor = $executor;
    }

    /**
     * @param array<string,mixed>|null $query
     * @param array<string,mixed>|null $json
     * @param array<string,array{file:string,filename?:string,content_type?:string}>|null $multipart
     * @return mixed
     */
    public function request(string $method, string $path, ?array $query = null, $json = null, ?array $multipart = null, bool $auth = true)
    {
        $url = $this->baseUrl . $path;
        if ($query) {
            $filtered = array_filter($query, static function ($v) { return $v !== null; });
            if (!empty($filtered)) {
                $url .= '?' . http_build_query($filtered);
            }
        }

        $headers = ['User-Agent: ' . self::USER_AGENT, 'Accept: application/json'];
        if ($auth) {
            if ($this->apiKey === '') {
                throw new AuthException('no_api_key_configured', 401);
            }
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $body = null;
        if ($multipart !== null) {
            $boundary = '----tsage' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
            $body = $this->buildMultipart($multipart, $boundary);
        } elseif ($json !== null) {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($this->prune($json), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $attempts = 0;
        while (true) {
            $attempts++;
            $response = $this->execute($method, $url, $headers, $body);
            $status = $response['status'];
            $requestId = $response['headers']['x-request-id'] ?? null;

            if ($status >= 200 && $status < 300) {
                $ct = $response['headers']['content-type'] ?? '';
                if (strpos($ct, 'application/json') !== false) {
                    $decoded = json_decode($response['body'], true);
                    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new TsageException('invalid JSON in response: ' . json_last_error_msg(), $status, $response['body'], $requestId);
                    }
                    return $decoded;
                }
                return $response['body'];
            }

            $parsed = json_decode($response['body'], true);
            if (($status === 401 || $status === 429) && $attempts <= $this->maxRetries) {
                usleep((int) (pow(2, $attempts - 1) * 500000));
                continue;
            }
            throw ErrorMap::forStatus($status, $parsed === null ? $response['body'] : $parsed, $requestId);
        }
    }

    /**
     * @param array<string,mixed> $obj
     * @return mixed
     */
    private function prune($obj)
    {
        if (is_array($obj)) {
            $out = [];
            foreach ($obj as $k => $v) {
                if ($v === null) {
                    continue;
                }
                $out[$k] = $this->prune($v);
            }
            return $out;
        }
        return $obj;
    }

    private function buildMultipart(array $parts, string $boundary): string
    {
        $lines = '';
        foreach ($parts as $name => $value) {
            $lines .= "--{$boundary}\r\n";
            if (is_array($value) && isset($value['file'])) {
                $filename = $value['filename'] ?? 'file';
                $contentType = $value['content_type'] ?? 'application/octet-stream';
                $lines .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
                $lines .= "Content-Type: {$contentType}\r\n\r\n";
                $lines .= $value['file'];
                $lines .= "\r\n";
            } else {
                $lines .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
                $lines .= (string) $value;
                $lines .= "\r\n";
            }
        }
        $lines .= "--{$boundary}--\r\n";
        return $lines;
    }

    /**
     * @param array<int,string> $headers
     * @return array{status:int,body:string,headers:array<string,string>}
     */
    private function execute(string $method, string $url, array $headers, ?string $body): array
    {
        if ($this->executor !== null) {
            return call_user_func($this->executor, $method, $url, [
                'headers' => $headers,
                'body' => $body,
            ]);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($_, $raw) use (&$responseHeaders) {
            if (strpos($raw, ':') !== false) {
                [$k, $v] = explode(':', $raw, 2);
                $responseHeaders[strtolower(trim($k))] = trim($v);
            }
            return strlen($raw);
        });

        $resBody = curl_exec($ch);
        if ($resBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new TsageException('curl transport error: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $resBody, 'headers' => $responseHeaders];
    }
}
