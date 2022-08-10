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

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        return (string) $this->request->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        $url = new Uri($requestTarget);
        if (!$url->getScheme() || !$url->getHost()) {
            throw new \LogicException(sprintf('%s must be an absolute-form target to use with this adapter.', $requestTarget));
        }

        $request = clone $this->request;
        $request->setUrl($url);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        if (!\is_string($method) || '' === $method) {
            throw new \InvalidArgumentException('Method must be a non-empty string.');
        }

        $request = clone $this->request;
        $request->setMethod($method);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->request->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
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

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        $body = '';
        if ($this->request->hasPostData()) {
            $body = $this->request->getPostData()->getText();
        }

        return Utils::streamFor($body);
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
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
