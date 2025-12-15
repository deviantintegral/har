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
use Psr\Http\Message\ServerRequestInterface;

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
     * @Serializer\Type("string")
     */
    private string $method;

    /**
     * @var \Psr\Http\Message\UriInterface
     *
     * @Serializer\Type("Psr\Http\Message\UriInterface")
     */
    private PsrHttpMessageRIINTERFACE $URL;

    /**
     * List of query parameter objects.
     *
     * @var Params[]
     *
     * @Serializer\Type("array<Deviantintegral\Har\Params>")
     */
    private ?array $queryString = null;

    /**
     * postData [object, optional] - Posted data info.
     *
     * @Serializer\Type("Deviantintegral\Har\PostData")
     */
    private ?PostData $postData = null;

    /**
     * Construct a new Request from a PSR-7 Request.
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

    /**
     * Construct a new Request from a PSR-7 ServerRequest.
     */
    public static function fromPsr7ServerRequest(ServerRequestInterface $source): self
    {
        // Start with the basic request conversion (ServerRequest extends Request)
        $harRequest = static::fromPsr7Request($source);

        // Extract and set cookies from ServerRequest
        $cookies = [];
        foreach ($source->getCookieParams() as $name => $value) {
            $cookie = (new Cookie())
                ->setName($name)
                ->setValue($value);
            $cookies[] = $cookie;
        }
        if (!empty($cookies)) {
            $harRequest->setCookies($cookies);
        }

        // Extract and set query parameters from ServerRequest
        $queryParams = [];
        foreach ($source->getQueryParams() as $name => $value) {
            $param = (new Params())
                ->setName($name)
                ->setValue((string) $value);
            $queryParams[] = $param;
        }
        if (!empty($queryParams)) {
            $harRequest->setQueryString($queryParams);
        }

        return $harRequest;
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
     * @return Params[]
     */
    public function getQueryString(): array
    {
        return $this->queryString ?? [];
    }

    /**
     * @param Params[] $queryString
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

    public function getPostData(): PostData
    {
        return $this->postData;
    }

    public function setPostData(PostData $postData,
    ): self {
        $this->postData = $postData;

        $this->setBodySize($postData->getBodySize());

        return $this;
    }
}
