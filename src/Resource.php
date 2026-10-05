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
