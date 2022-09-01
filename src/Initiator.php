<?php

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

/**
 * @see https://developers.google.com/web/tools/chrome-devtools/network/reference#requests
 */
final class Initiator
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     *
     * parser:
     *   Chrome's HTML parser.
     * redirect:
     *   An HTTP redirect.
     * script:
     *   A JavaScript function.
     * other:
     *   Some other process or action, such as navigating to a page via a link
     *   or entering a URL in the address bar.
     */
    private $type;

    /**
     * URL of the entry that initiated this request.
     *
     * @var \Psr\Http\Message\UriInterface
     *
     * @Serializer\Type("Psr\Http\Message\UriInterface")
     */
    private $url;

    /**
     * Line number that initiated this request.
     *
     * @var int
     *
     * @Serializer\Type("integer")
     */
    private $lineNumber;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?\Psr\Http\Message\UriInterface
    {
        return $this->url;
    }

    public function hasUrl(): bool
    {
        return null !== $this->url;
    }

    public function setUrl(\Psr\Http\Message\UriInterface $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    public function hasLineNumber(): bool
    {
        return null !== $this->lineNumber;
    }

    public function setLineNumber(int $lineNumber): self
    {
        $this->lineNumber = $lineNumber;

        return $this;
    }
}
