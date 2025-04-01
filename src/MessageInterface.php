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

    public function setHeaders(array $headers);

    public function getHttpVersion(): string;

    public function setHttpVersion(string $version);
}
