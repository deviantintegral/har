<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\BodySizeTrait;
use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\CookiesTrait;
use Deviantintegral\Har\SharedFields\HeadersTrait;
use Deviantintegral\Har\SharedFields\HttpVersionTrait;
use JMS\Serializer\Annotation as Serializer;
use Psr\Http\Message\ResponseInterface;

final class Response implements MessageInterface
{
    use BodySizeTrait;
    use CommentTrait;
    use CookiesTrait;
    use HeadersTrait;
    use HttpVersionTrait;

    /**
     * status [number] - Response status.
     *
     * @var int
     *
     * @Serializer\Type("integer")
     */
    private $status;

    /**
     * statusText [string] - Response status description.
     *
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $statusText;

    /**
     * content [object] - Details about the response body.
     *
     * @var \Deviantintegral\Har\Content
     *
     * @Serializer\Type("Deviantintegral\Har\Content")
     */
    private $content;

    /**
     * redirectURL [string] - Redirection target URL from the Location response
     * header.
     *
     * @var \Psr\Http\Message\UriInterface
     *
     * @Serializer\Type("Psr\Http\Message\UriInterface")
     */
    private $redirectURL;

    public static function fromPsr7Response(ResponseInterface $source): self
    {
        $response = (new Adapter\Psr7\Response(new static()))
          ->withProtocolVersion($source->getProtocolVersion())
          ->withBody($source->getBody())
          ->withStatus($source->getStatusCode(), $source->getReasonPhrase());

        foreach ($source->getHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response->getHarResponse();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusText(): string
    {
        return $this->statusText;
    }

    public function setStatusText(string $statusText): self
    {
        $this->statusText = $statusText;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @param \Deviantintegral\Har\Content $content
     */
    public function setContent(Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getRedirectURL(): \Psr\Http\Message\UriInterface
    {
        return $this->redirectURL;
    }

    public function setRedirectURL(\Psr\Http\Message\UriInterface $redirectURL
    ): self {
        $this->redirectURL = $redirectURL;

        return $this;
    }
}
