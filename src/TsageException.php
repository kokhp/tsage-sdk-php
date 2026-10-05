<?php

declare(strict_types=1);

namespace TranslatorSage\Sdk;

class TsageException extends \RuntimeException
{
    /** @var int|null */
    public $statusCode;
    /** @var mixed */
    public $body;
    /** @var string|null */
    public $requestId;

    public function __construct(string $message, ?int $statusCode = null, $body = null, ?string $requestId = null)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->requestId = $requestId;
    }
}
