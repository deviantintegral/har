<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\BodySizeTrait;
use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\CookiesTrait;
use Deviantintegral\Har\SharedFields\HeadersTrait;
use Deviantintegral\Har\SharedFields\HttpVersionTrait;
use JMS\Serializer\Annotation as Serializer;
use Psr\Http\Message\RequestInterface;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#request
 */
final class Request implements MessageInterface
{
    use BodySizeTrait;
    use CommentTrait;
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
     * postData [object, optional] - Posted data info.
     *
     * @var \Deviantintegral\Har\PostData
     * @Serializer\Type("Deviantintegral\Har\PostData")
     */
    private $postData;

    /**
     * Construct a new Request from a PSR-7 Request.
     *
     * @return \Deviantintegral\Har\Request
     */
    public static function fromPsr7Request(RequestInterface $source): self
    {
        $request = (new Adapter\Psr7\Request(new static()))
          ->withBody($source->getBody())
          ->withMethod($source->getMethod())
          ->withProtocolVersion($source->getProtocolVersion())
          ->withUri($source->getUri());

        foreach ($source->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request->getHarRequest();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getUrl(): \Psr\Http\Message\UriInterface
    {
        return $this->url;
    }

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
     */
    public function setQueryString(array $queryString): self
    {
        $this->queryString = $queryString;

        return $this;
    }

    public function isResponseCached(): bool
    {
        return 0 === $this->bodySize;
    }

    public function hasPostData(): bool
    {
        return null !== $this->postData;
    }

    /**
     * @return \Deviantintegral\Har\PostData
     */
    public function getPostData(): PostData
    {
        return $this->postData;
    }

    /**
     * @param \Deviantintegral\Har\PostData $postData
     */
    public function setPostData(PostData $postData
    ): self {
        $this->postData = $postData;

        $this->setBodySize($postData->getBodySize());

        return $this;
    }
}
