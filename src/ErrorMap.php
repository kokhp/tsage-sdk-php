<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

final class ErrorMap
{
    public static function forStatus(int $status, $body, ?string $requestId): TsageException
    {
        $msg = 'TranslatorSage API error';
        if (is_array($body)) {
            $msg = $body['detail'] ?? $body['error'] ?? $body['message'] ?? $msg;
            if (is_array($msg)) {
                $msg = json_encode($msg);
            }
        } elseif (is_string($body) && $body !== '') {
            $msg = $body;
        }
        switch (true) {
            case $status === 401: return new AuthException($msg, $status, $body, $requestId);
            case $status === 403: return new ForbiddenException($msg, $status, $body, $requestId);
            case $status === 404: return new NotFoundException($msg, $status, $body, $requestId);
            case $status === 422: return new ValidationException($msg, $status, $body, $requestId);
            case $status === 429: return new RateLimitException($msg, $status, $body, $requestId);
            case $status >= 500 && $status < 600: return new ServerException($msg, $status, $body, $requestId);
            default: return new TsageException($msg, $status, $body, $requestId);
        }
    }
}
