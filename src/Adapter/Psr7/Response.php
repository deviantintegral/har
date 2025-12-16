<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Adapter\Psr7;

use Deviantintegral\Har\Content;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response extends MessageBase implements ResponseInterface
{
    private \Deviantintegral\Har\Response $response;

    /**
     * Response constructor.
     *
     * @phpstan-param \Deviantintegral\Har\Response $response
     *
     * @phpstan-ignore method.childParameterType
     */
    public function __construct(\Deviantintegral\Har\Response $response)
    {
        parent::__construct($response);
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatus();
    }

    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        $response = clone $this->response;
        $response->setStatus($code)
            ->setStatusText($reasonPhrase);

        return new static($response);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getStatusText();
    }

    public function getBody(): StreamInterface
    {
        return Utils::streamFor($this->response->getContent()->getText());
    }

    public function withBody(StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        $response = clone $this->response;

        // We don't have any information about $body so we create a new
        // content object with default values.
        $content = (new Content())
          ->setText($body->getContents());
        $response->setContent($content);

        return new static($response);
    }

    public function getHarResponse(): \Deviantintegral\Har\Response
    {
        return clone $this->response;
    }
}
