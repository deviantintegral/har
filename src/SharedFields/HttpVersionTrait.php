<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

trait HttpVersionTrait
{
    /**
     * httpVersion [string] - Response HTTP Version.
     *
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $httpVersion;

    public function setHttpVersion(string $httpVersion): self
    {
        $this->httpVersion = $httpVersion;

        return $this;
    }

    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }
}
