<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\BodyTrait;
use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\ContentTrait;
use Deviantintegral\Har\SharedFields\CookiesTrait;
use Deviantintegral\Har\SharedFields\HeadersTrait;
use Deviantintegral\Har\SharedFields\HttpVersionTrait;
use JMS\Serializer\Annotation as Serializer;

final class Response
{
    use BodyTrait;
    use CommentTrait;
    use ContentTrait;
    use CookiesTrait;
    use HeadersTrait;
    use HttpVersionTrait;

    /**
     * status [number] - Response status.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $status;

    /**
     * statusText [string] - Response status description.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $statusText;

    /**
     * redirectURL [string] - Redirection target URL from the Location response
     * header.
     *
     * @var \Psr\Http\Message\UriInterface
     * @Serializer\Type("Psr\Http\Message\UriInterface")
     */
    private $redirectURL;

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * @param string $statusText
     *
     * @return self
     */
    public function setStatusText(string $statusText): self
    {
        $this->statusText = $statusText;

        return $this;
    }

    /**
     * @return \Psr\Http\Message\UriInterface
     */
    public function getRedirectURL(): \Psr\Http\Message\UriInterface
    {
        return $this->redirectURL;
    }

    /**
     * @param \Psr\Http\Message\UriInterface $redirectURL
     *
     * @return self
     */
    public function setRedirectURL(\Psr\Http\Message\UriInterface $redirectURL
    ): self {
        $this->redirectURL = $redirectURL;

        return $this;
    }
}
