<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\BodyTrait;
use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\ContentTrait;
use Deviantintegral\Har\SharedFields\CookiesTrait;
use Deviantintegral\Har\SharedFields\HeadersTrait;
use Deviantintegral\Har\SharedFields\HttpVersionTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#request
 */
final class Request
{
    use BodyTrait;
    use CommentTrait;
    use ContentTrait;
    use CookiesTrait;
    use HeadersTrait;
    use HttpVersionTrait;

    /**
     * method [string] - Request method (GET, POST, ...).
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $method;

    /**
     * @var \Psr\Http\Message\UriInterface
     * @Serializer\Type("Psr\Http\Message\UriInterface")
     */
    private $url;

    /**
     * List of query parameter objects.
     *
     * @var \Deviantintegral\Har\Params[]
     * @Serializer\Type("array<Deviantintegral\Har\Params>")
     */
    private $queryString;

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return Request
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return \Psr\Http\Message\UriInterface
     */
    public function getUrl(): \Psr\Http\Message\UriInterface
    {
        return $this->url;
    }

    /**
     * @param \Psr\Http\Message\UriInterface $url
     *
     * @return Request
     */
    public function setUrl(\Psr\Http\Message\UriInterface $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Params[]
     */
    public function getQueryString(): array
    {
        return $this->queryString;
    }

    /**
     * @param \Deviantintegral\Har\Params[] $queryString
     *
     * @return Request
     */
    public function setQueryString(array $queryString): self
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * @return bool
     */
    public function responseIsCached(): bool
    {
        return 0 === $this->bodySize;
    }
}
