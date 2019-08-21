<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

trait HttpVersionTrait
{
    /**
     * httpVersion [string] - Response HTTP Version.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $httpVersion;

    /**
     * @param string $httpVersion
     *
     * @return self
     */
    public function setHttpVersion(string $httpVersion): self
    {
        $this->httpVersion = $httpVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }
}
