<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Adapter\Psr7;

use Deviantintegral\Har\PostData;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends MessageBase implements RequestInterface
{
    /**
     * @var \Deviantintegral\Har\Request
     */
    private $request;

    public function __construct(\Deviantintegral\Har\Request $request)
    {
        parent::__construct($request);

        // Clone to preserve the immutability of this request.
        $this->request = clone $request;
    }

    public function getRequestTarget(): string
    {
        return (string) $this->request->getUrl();
    }

    public function withRequestTarget($requestTarget): RequestInterface
    {
        $url = new Uri($requestTarget);
        if (!$url->getScheme() || !$url->getHost()) {
            throw new \LogicException(sprintf('%s must be an absolute-form target to use with this adapter.', $requestTarget));
        }

        $request = clone $this->request;
        $request->setUrl($url);

        return new static($request);
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function withMethod($method): RequestInterface
    {
        if (!\is_string($method) || '' === $method) {
            throw new \InvalidArgumentException('Method must be a non-empty string.');
        }

        $request = clone $this->request;
        $request->setMethod($method);

        return new static($request);
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUrl();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $request = clone $this->request;
        $request->setUrl($uri);
        $return = new static($request);
        if (!$preserveHost && $host = $uri->getHost()) {
            $return = $return->withHeader('Host', $host);
        }

        return $return;
    }

    /**
     * Returns a clone of the underlying HAR request.
     */
    public function getHarRequest(): \Deviantintegral\Har\Request
    {
        return clone $this->request;
    }

    public function getBody(): StreamInterface
    {
        $body = '';
        if ($this->request->hasPostData()) {
            $body = $this->request->getPostData()->getText();
        }

        return Utils::streamFor($body);
    }

    public function withBody(StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        $request = clone $this->request;
        $postData = new PostData();
        if ($request->hasPostData()) {
            $postData = $request->getPostData();
        }
        $postData->setText($body->getContents());
        $request->setPostData($postData);

        return new static($request);
    }
}
