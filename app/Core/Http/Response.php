<?php

declare(strict_types=1);

namespace Josix\Core\Http;

class Response
{
    public function __construct(
        private string $content,
        private int $statusCode = 200,
        private array $headers = [],
    ) {}

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo $this->content;
    }
}
