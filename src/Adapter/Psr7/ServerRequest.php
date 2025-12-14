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
    public function getServerParams(): array
    {
        return [];
    }

    public function getCookieParams(): array
    {
        $request = $this->getHarRequest();
        $cookieParams = [];
        foreach ($request->getCookies() as $cookie) {
            $cookieParams[$cookie->getName()] = $cookie->getValue();
        }

        return $cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $request = clone $this->getHarRequest();
        $harCookies = [];
        foreach ($cookies as $name => $value) {
            $harCookies[] = (new Cookie())
                ->setName($name)
                ->setValue($value);
        }
        $request->setCookies($harCookies);

        return new static($request);
    }

    public function getQueryParams(): array
    {
        $request = $this->getHarRequest();
        $queryParams = [];
        foreach ($request->getQueryString() as $param) {
            $queryParams[$param->getName()] = $param->getValue();
        }

        return $queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $request = clone $this->getHarRequest();
        $harParams = [];
        foreach ($query as $name => $value) {
            $harParams[] = (new Params())
                ->setName($name)
                ->setValue((string) $value);
        }
        $request->setQueryString($harParams);

        return new static($request);
    }

    public function getUploadedFiles(): array
    {
        return [];
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        throw new \LogicException('Uploaded files are not supported.');
    }

    public function getParsedBody()
    {
        $request = $this->getHarRequest();

        if (!$request->hasPostData()) {
            return null;
        }

        $postData = $request->getPostData();
        if ($postData->hasParams()) {
            $parsedBody = [];
            foreach ($postData->getParams() as $param) {
                $parsedBody[$param->getName()] = $param->getValue();
            }

            return $parsedBody;
        }

        if ($postData->hasText()) {
            // Try to parse as form data if content type suggests it
            $contentType = $postData->getMimeType();
            if ($contentType === 'application/x-www-form-urlencoded') {
                $parsedBody = [];
                parse_str($postData->getText(), $parsedBody);

                return $parsedBody;
            }
        }

        return null;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_array($data) && !is_object($data) && null !== $data) {
            throw new \InvalidArgumentException('Parsed body must be an array, object, or null.');
        }

        $request = clone $this->getHarRequest();

        if (is_array($data) || is_object($data)) {
            $postData = new PostData();
            $harParams = [];
            foreach ($data as $name => $value) {
                $harParams[] = (new Params())
                    ->setName($name)
                    ->setValue((string) $value);
            }
            $postData->setParams($harParams);
            $request->setPostData($postData);
        } elseif (null === $data) {
            // Clear post data
            $request->setPostData(new PostData());
        }

        return new static($request);
    }

    public function getAttributes(): array
    {
        return [];
    }

    public function getAttribute($name, $default = null)
    {
        return $default;
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        // Attributes are not part of HAR spec, return unchanged clone
        return new static($this->getHarRequest());
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        // Attributes are not part of HAR spec, return unchanged clone
        return new static($this->getHarRequest());
    }

    /**
     * Override parent methods to return ServerRequestInterface.
     */
    public function withMethod($method): ServerRequestInterface
    {
        $parent = parent::withMethod($method);

        return new static($parent->getHarRequest());
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $parent = parent::withUri($uri, $preserveHost);

        return new static($parent->getHarRequest());
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $parent = parent::withRequestTarget($requestTarget);

        return new static($parent->getHarRequest());
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $parent = parent::withBody($body);

        return new static($parent->getHarRequest());
    }

    public function withHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withHeader($name, $value);

        return new static($parent->getHarRequest());
    }

    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        $parent = parent::withAddedHeader($name, $value);

        return new static($parent->getHarRequest());
    }

    public function withoutHeader($name): ServerRequestInterface
    {
        $parent = parent::withoutHeader($name);

        return new static($parent->getHarRequest());
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        $parent = parent::withProtocolVersion($version);

        return new static($parent->getHarRequest());
    }
}
