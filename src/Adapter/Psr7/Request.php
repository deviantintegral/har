<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Adapter\Psr7;

use Deviantintegral\Har\Header;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use function GuzzleHttp\Psr7\stream_for;

class Request implements RequestInterface
{
    /**
     * @var \Deviantintegral\Har\Request
     */
    private $request;

    public function __construct(\Deviantintegral\Har\Request $request)
    {
        // Clone to preserve the immutability of this request.
        $this->request = clone $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return substr($this->request->getHttpVersion(), 5);
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        $request = clone $this->request;
        $request->setHttpVersion('HTTP/'.$version);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        $headers = $this->request->getHeaders();
        $return = [];
        foreach ($headers as $header) {
            $return[$header->getName()][] = $header->getValue();
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        foreach ($this->request->getHeaders() as $header) {
            if (strtolower($header->getName()) === strtolower($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            $headers = $this->getHeaders();
            foreach ($headers as $header => $value) {
                if (strtolower($header) === strtolower($name)) {
                    return $value;
                }
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        if ($this->hasHeader($name)) {
            return implode(', ', $this->getHeader($name));
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $request = clone $this->request;

        if (!\is_array($value)) {
            $value = [$value];
        }

        $index = 0;
        $headers = $request->getHeaders();
        if ($this->hasHeader($name)) {
            foreach ($headers as $header_index => $header) {
                if (strtolower($header->getName()) === strtolower($name)) {
                    if (isset($value[$index])) {
                        $header->setValue($value[$index++]);
                    } else {
                        unset($headers[$header_index]);
                    }
                }
            }
        }

        for (; $index < \count($value); ++$index) {
            $header = (new Header())
              ->setName($name)
              ->setValue($value[$index]);
            $headers[] = $header;
        }

        $request->setHeaders($headers);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        $request = clone $this->request;

        if (!\is_array($value)) {
            $value = [$value];
        }

        $headers = $request->getHeaders();
        foreach ($value as $line) {
            $headers[] = (new Header())
              ->setName($name)
              ->setValue($line);
        }
        $request->setHeaders($headers);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $request = clone $this->request;

        $headers = $request->getHeaders();
        foreach ($headers as $index => $header) {
            if ($header->getName() === $name) {
                unset($headers[$index]);
                break;
            }
        }

        $request->setHeaders($headers);

        return new static($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return stream_for($this->request->getPostData()->getText());
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        $request = clone $this->request;
        $request->getPostData()->setText($body->getContents());

        return new static($request);
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
     *
     * @return \Deviantintegral\Har\Request
     */
    public function getHarRequest(): \Deviantintegral\Har\Request
    {
        return clone $this->request;
    }
}
