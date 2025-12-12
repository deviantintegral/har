<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Adapter\Psr7;

use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    private $serverParams = [];

    /**
     * @var array
     */
    private $cookieParams = [];

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var array|object|null
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $uploadedFiles = [];

    /**
     * @var array
     */
    private $attributes = [];

    public function __construct(\Deviantintegral\Har\Request $request)
    {
        parent::__construct($request);

        // Initialize query params from HAR request
        $this->queryParams = [];
        foreach ($request->getQueryString() as $param) {
            $this->queryParams[$param->getName()] = $param->getValue();
        }

        // Initialize cookie params from HAR request
        $this->cookieParams = [];
        foreach ($request->getCookies() as $cookie) {
            $this->cookieParams[$cookie->getName()] = $cookie->getValue();
        }

        // Initialize parsed body from HAR post data
        if ($request->hasPostData()) {
            $postData = $request->getPostData();
            if ($postData->hasParams()) {
                $this->parsedBody = [];
                foreach ($postData->getParams() as $param) {
                    $this->parsedBody[$param->getName()] = $param->getValue();
                }
            } elseif ($postData->hasText()) {
                // Try to parse as form data if content type suggests it
                $contentType = $postData->getMimeType();
                if ($contentType === 'application/x-www-form-urlencoded') {
                    parse_str($postData->getText(), $this->parsedBody);
                }
            }
        }
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Set server parameters.
     *
     * This is not part of PSR-7 but allows setting server params
     * for testing and internal use. Returns a new instance.
     */
    public function withServerParams(array $serverParams): self
    {
        $new = clone $this;
        $new->serverParams = $serverParams;

        return $new;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!\is_array($data) && !\is_object($data) && null !== $data) {
            throw new \InvalidArgumentException('Parsed body must be an array, object, or null.');
        }

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        if (!\array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Clone this ServerRequest with a modified HAR request.
     *
     * This helper method clones the current ServerRequest and updates its
     * underlying HAR request, preserving all ServerRequest-specific state.
     */
    private function cloneWithHarRequest(\Deviantintegral\Har\Request $request): self
    {
        $new = clone $this;

        // Use reflection to update both the Request's $request property
        // and MessageBase's $message property
        $requestReflection = new \ReflectionClass(Request::class);
        $requestProperty = $requestReflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($new, $request);

        $messageReflection = new \ReflectionClass(MessageBase::class);
        $messageProperty = $messageReflection->getProperty('message');
        $messageProperty->setAccessible(true);
        $messageProperty->setValue($new, $request);

        return $new;
    }

    /**
     * Override parent methods to preserve ServerRequest state.
     */
    public function withMethod($method): ServerRequestInterface
    {
        $parent = parent::withMethod($method);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $parent = parent::withUri($uri, $preserveHost);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $parent = parent::withRequestTarget($requestTarget);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $parent = parent::withBody($body);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withHeader($name, $value);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withAddedHeader($name, $value);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withoutHeader($name): ServerRequestInterface
    {
        $parent = parent::withoutHeader($name);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        $parent = parent::withProtocolVersion($version);

        return $this->cloneWithHarRequest($parent->getHarRequest());
    }
}
