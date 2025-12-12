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
     * Sentinel value to indicate that parsed body should be extracted from HAR.
     */
    private const EXTRACT_FROM_HAR = '__EXTRACT_FROM_HAR__';

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

    public function __construct(
        \Deviantintegral\Har\Request $request,
        array $serverParams = [],
        array $cookieParams = null,
        array $queryParams = null,
        $parsedBody = self::EXTRACT_FROM_HAR,
        array $uploadedFiles = [],
        array $attributes = []
    ) {
        parent::__construct($request);

        $this->serverParams = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->attributes = $attributes;

        // Initialize query params from HAR request if not provided
        if (null === $queryParams) {
            $this->queryParams = [];
            foreach ($request->getQueryString() as $param) {
                $this->queryParams[$param->getName()] = $param->getValue();
            }
        } else {
            $this->queryParams = $queryParams;
        }

        // Initialize cookie params from HAR request if not provided
        if (null === $cookieParams) {
            $this->cookieParams = [];
            foreach ($request->getCookies() as $cookie) {
                $this->cookieParams[$cookie->getName()] = $cookie->getValue();
            }
        } else {
            $this->cookieParams = $cookieParams;
        }

        // Initialize parsed body from HAR post data if not provided
        if (self::EXTRACT_FROM_HAR === $parsedBody && $request->hasPostData()) {
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
        } else {
            $this->parsedBody = $parsedBody;
        }
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $cookies,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $query,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $uploadedFiles,
            $this->attributes
        );
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

        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $data,
            $this->uploadedFiles,
            $this->attributes
        );
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
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $attributes
        );
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $attributes = $this->attributes;
        unset($attributes[$name]);

        return new self(
            $this->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $attributes
        );
    }

    /**
     * Override parent methods to preserve ServerRequest state.
     */
    public function withMethod($method): ServerRequestInterface
    {
        $parent = parent::withMethod($method);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $parent = parent::withUri($uri, $preserveHost);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $parent = parent::withRequestTarget($requestTarget);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $parent = parent::withBody($body);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withHeader($name, $value);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withAddedHeader($name, $value);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withoutHeader($name): ServerRequestInterface
    {
        $parent = parent::withoutHeader($name);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        $parent = parent::withProtocolVersion($version);

        return new self(
            $parent->getHarRequest(),
            $this->serverParams,
            $this->cookieParams,
            $this->queryParams,
            $this->parsedBody,
            $this->uploadedFiles,
            $this->attributes
        );
    }
}
