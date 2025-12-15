<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

interface MessageInterface
{
    public function getBodySize(): int;

    /**
     * @return Cookie[]
     */
    public function getCookies(): array;

    public function getHeadersSize(): int;

    /**
     * @return Header[]
     */
    public function getHeaders(): array;

    /**
     * @param array<Header> $headers
     */
    public function setHeaders(array $headers): static;

    public function getHttpVersion(): string;

    public function setHttpVersion(string $version): static;
}
