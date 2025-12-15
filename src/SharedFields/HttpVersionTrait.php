<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait HttpVersionTrait
{
    /**
     * httpVersion [string] - Response HTTP Version.
     */
    #[Serializer\Type('string')]
    private string $httpVersion;

    public function setHttpVersion(string $httpVersion): static
    {
        $this->httpVersion = $httpVersion;

        return $this;
    }

    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }
}
